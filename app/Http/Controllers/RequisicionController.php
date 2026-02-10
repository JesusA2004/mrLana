<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\BulkDestroyRequest;
use App\Http\Requests\Requisicion\RequisicionIndexRequest;
use App\Http\Requests\Requisicion\RequisicionStoreRequest;
use App\Http\Requests\Requisicion\RequisicionUpdateRequest;
use App\Http\Resources\RequisicionResource;
use App\Mail\RequisicionEnviadaMail;
use App\Models\Concepto;
use App\Models\Corporativo;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class RequisicionController extends Controller {

    public function index(RequisicionIndexRequest $request): Response {
        $user = $request->user();
        $rol  = strtoupper((string)($user->rol ?? 'COLABORADOR'));
        $q = $request->validated();
        // PerPage controlado (misma lógica que el front)
        $perPage = (int)($q['perPage'] ?? 10);
        if ($perPage <= 0) $perPage = 10;
        if ($perPage > 100) $perPage = 100;
        // Orden controlado (allowlist)
        $sort = (string)($q['sort'] ?? 'created_at');
        $dir  = strtolower((string)($q['dir'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSort = ['id', 'created_at', 'folio', 'monto_total', 'fecha_solicitud', 'status'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'created_at';
        }
        $query = Requisicion::query()
            ->with([
                'sucursal:id,nombre,codigo,corporativo_id',
                'solicitante:id,nombre,apellido_paterno,apellido_materno',
                'proveedor:id,razon_social,rfc,clabe,banco,status',
                'concepto:id,nombre',
                'comprador:id,nombre',
            ])
            ->when($rol === 'COLABORADOR', function ($qq) use ($user) {
                if ($user->empleado_id) {
                    $qq->where('solicitante_id', $user->empleado_id);
                } else {
                    $qq->whereRaw('1=0');
                }
            });
        // q: busca en folio/observaciones y también en relaciones comunes
        if (!empty($q['q'])) {
            $needle = trim((string)$q['q']);
            $query->where(function ($sub) use ($needle) {
                $sub->where('folio', 'like', "%{$needle}%")
                    ->orWhere('observaciones', 'like', "%{$needle}%")
                    ->orWhereHas('proveedor', fn($p) => $p->where('razon_social', 'like', "%{$needle}%"))
                    ->orWhereHas('concepto', fn($c) => $c->where('nombre', 'like', "%{$needle}%"))
                    ->orWhereHas('comprador', fn($c) => $c->where('nombre', 'like', "%{$needle}%"))
                    ->orWhereHas('sucursal', fn($s) => $s->where('nombre', 'like', "%{$needle}%"));
            });
        }
        if (!empty($q['status'])) {
            $query->where('status', $q['status']);
        }
        if (!empty($q['comprador_corp_id'])) {
            $query->where('comprador_corp_id', (int)$q['comprador_corp_id']);
        }
        if (!empty($q['sucursal_id'])) {
            $query->where('sucursal_id', (int)$q['sucursal_id']);
        }
        // Admin/Contador pueden filtrar solicitante, colaborador se fuerza arriba
        if ($rol !== 'COLABORADOR' && !empty($q['solicitante_id'])) {
            $query->where('solicitante_id', (int)$q['solicitante_id']);
        }
        if (!empty($q['concepto_id'])) {
            $query->where('concepto_id', (int)$q['concepto_id']);
        }
        if (!empty($q['proveedor_id'])) {
            $query->where('proveedor_id', (int)$q['proveedor_id']);
        }
        // Rango de fechas (fecha_solicitud)
        if (!empty($q['fecha_from'])) {
            $query->whereDate('fecha_solicitud', '>=', $q['fecha_from']);
        }
        if (!empty($q['fecha_to'])) {
            $query->whereDate('fecha_solicitud', '<=', $q['fecha_to']);
        }

        $requisiciones = $query
            ->orderBy($sort, $dir)
            ->orderBy('id', 'desc') // fallback estable
            ->paginate($perPage)
            ->withQueryString();
        return Inertia::render('Requisiciones/Index', [
            'requisiciones' => RequisicionResource::collection($requisiciones),
            'catalogos' => $this->catalogos($user),
            // Devolver todos los filtros para hidratar state (front)
            'filters' => [
                'q' => $q['q'] ?? '',
                'status' => $q['status'] ?? '',
                'comprador_corp_id' => $q['comprador_corp_id'] ?? '',
                'sucursal_id' => $q['sucursal_id'] ?? '',
                'solicitante_id' => $q['solicitante_id'] ?? '',
                'concepto_id' => $q['concepto_id'] ?? '',
                'proveedor_id' => $q['proveedor_id'] ?? '',
                'fecha_from' => $q['fecha_from'] ?? '',
                'fecha_to' => $q['fecha_to'] ?? '',
                'perPage' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function show(\Illuminate\Http\Request $request,Requisicion $requisicion) {
        $user = $request->user();

        // Seguridad básica: COLABORADOR solo ve sus requis
        $rol = strtoupper((string) ($user->rol ?? ''));
        if ($rol === 'COLABORADOR') {
            abort_unless(
                $user->empleado_id && (int) $requisicion->solicitante_id === (int) $user->empleado_id,
                403
            );
        }

        // Eager loads: SOLO columnas reales según tus migrations
        $with = [
            'sucursal:id,nombre,codigo,corporativo_id,activo',
            'solicitante:id,nombre,apellido_paterno,apellido_materno,puesto,activo',
            'proveedor:id,razon_social,rfc,clabe,banco,status',
            'concepto:id,nombre,activo',
            'comprador:id,nombre,logo_path',
            'detalles',
            'detalles.sucursal:id,nombre,codigo',
        ];

        if (method_exists($requisicion, 'creadaPor')) {
            $with[] = 'creadaPor:id,name,email';
        }

        if (method_exists($requisicion, 'comprobantes')) {
            // NO existe archivo/ruta en tu tabla comprobantes
            $with[] = 'comprobantes:id,requisicion_id,tipo_doc,subtotal,total,user_carga_id,created_at';
        }

        $requisicion->load($with);

        // Detalles normalizados para UI
        $detalles = collect($requisicion->detalles ?? [])->map(function ($d) {
            $cantidad = (float) ($d->cantidad ?? 0);
            $precio   = (float) ($d->precio_unitario ?? 0);
            $subtotal = (float) ($d->subtotal ?? ($cantidad * $precio));
            $iva      = (float) ($d->iva ?? 0);
            $total    = (float) ($d->total ?? ($subtotal + $iva));

            return [
                'id'             => $d->id,
                'sucursal'        => $d->sucursal ? [
                    'id'     => $d->sucursal->id,
                    'nombre' => $d->sucursal->nombre,
                    'codigo' => $d->sucursal->codigo,
                ] : null,
                'cantidad'       => $cantidad,
                'descripcion'    => (string) ($d->descripcion ?? ''),
                'precio_unitario'=> $precio,
                'genera_iva'     => (bool) ($d->genera_iva ?? false),
                'subtotal'       => $subtotal,
                'iva'            => $iva,
                'total'          => $total,
            ];
        })->values();

        // Comprobantes normalizados (sin url porque tu tabla NO tiene archivo)
        $comprobantes = collect();
        if (isset($requisicion->comprobantes)) {
            $ids = collect($requisicion->comprobantes)
                ->pluck('user_carga_id')
                ->filter()
                ->unique()
                ->values();

            $usersById = $ids->isEmpty()
                ? collect()
                : \App\Models\User::select('id', 'name')->whereIn('id', $ids)->get()->keyBy('id');

            $comprobantes = collect($requisicion->comprobantes)->map(function ($c) use ($usersById) {
                $u = $usersById->get($c->user_carga_id);

                return [
                    'id'          => $c->id,
                    'tipo_doc'    => (string) ($c->tipo_doc ?? 'OTRO'),
                    'subtotal'    => (float) ($c->subtotal ?? 0),
                    'total'       => (float) ($c->total ?? 0),
                    'user_carga'  => $c->user_carga_id ? [
                        'id'   => (int) $c->user_carga_id,
                        'name' => (string) ($u?->name ?? ('Usuario #' . (int) $c->user_carga_id)),
                    ] : null,
                    'created_at'  => optional($c->created_at)->toISOString(),
                    'url'         => null, // tu tabla no trae archivo/ruta
                    'label'       => 'Comprobante #' . $c->id,
                ];
            })->values();
        }

        // PDF block (si existe ruta)
        $pdf = [
            'can_print' => true,
            'print_url' => null,
            'filename'  => ($requisicion->folio ?? 'requisicion') . '.pdf',
        ];
        try {
            $pdf['print_url'] = route('requisiciones.print', $requisicion->id);
        } catch (\Throwable $e) {
            // No pasa nada si no existe la ruta
        }

        return \Inertia\Inertia::render('Requisiciones/Show', [
            // Importante: mandamos array plano para que Vue no se confunda con {data:{}}
            'requisicion'  => (new \App\Http\Resources\RequisicionResource($requisicion))->resolve(),
            'detalles'     => $detalles,
            'comprobantes' => $comprobantes,
            'pdf'          => $pdf,
        ]);
    }

    public function create(Request $request): Response {
        $user = $request->user();
        $plantilla = null;
        $plantillaId = $request->query('plantilla');
        if ($plantillaId) {
            $plantilla = \App\Models\Plantilla::query()
                ->with(['detalles'])
                ->find($plantillaId);
            $rol = strtoupper((string)($user->rol ?? 'COLABORADOR'));
            if ($plantilla && $rol === 'COLABORADOR' && (int)$plantilla->user_id !== (int)$user->id) {
                abort(403);
            }
        }
        return Inertia::render('Requisiciones/Create', [
            'catalogos' => $this->catalogos($user),
            'plantilla' => $plantilla,
        ]);
    }

    public function store(RequisicionStoreRequest $request): RedirectResponse {
        $user = $request->user();
        $rol  = strtoupper((string)($user->rol ?? 'COLABORADOR'));
        $data = $request->validated();
        $accion = strtoupper((string)($data['accion'] ?? 'BORRADOR'));
        unset($data['accion']);
        // COLABORADOR: forzar solicitante y bloquear fecha_autorizacion
        if ($rol === 'COLABORADOR') {
            if (!$user->empleado_id) {
                return back()->withErrors(['solicitante_id' => 'Tu usuario no tiene empleado ligado.']);
            }
            $data['solicitante_id'] = (int)$user->empleado_id;
            $data['fecha_autorizacion'] = null;
        }
        // Validación catálogo: corporativo/sucursal activos y coherentes
        $corpId = (int)($data['comprador_corp_id'] ?? 0);
        $sucursalId = (int)($data['sucursal_id'] ?? 0);
        $corporativo = Corporativo::select('id', 'activo')->find($corpId);
        if (!$corporativo || $corporativo->activo === false) {
            return back()->withErrors(['comprador_corp_id' => 'El corporativo seleccionado no está activo o no existe.']);
        }
        $sucursal = Sucursal::select('id', 'corporativo_id', 'activo')->find($sucursalId);
        if (!$sucursal || $sucursal->activo === false) {
            return back()->withErrors(['sucursal_id' => 'La sucursal seleccionada no está activa o no existe.']);
        }
        if ((int)$sucursal->corporativo_id !== $corpId) {
            return back()->withErrors(['sucursal_id' => 'La sucursal no pertenece al corporativo seleccionado.']);
        }
        // Fuente de verdad
        $data['comprador_corp_id'] = (int)$sucursal->corporativo_id;
        $concepto = Concepto::select('id', 'activo')->find((int)$data['concepto_id']);
        if (!$concepto || $concepto->activo === false) {
            return back()->withErrors(['concepto_id' => 'El concepto seleccionado no está activo o no existe.']);
        }
        if (!empty($data['proveedor_id'])) {
            $prov = Proveedor::select('id', 'status')->find((int)$data['proveedor_id']);
            if (!$prov || strtoupper((string)$prov->status) !== 'ACTIVO') {
                return back()->withErrors(['proveedor_id' => 'El proveedor seleccionado no está activo o no existe.']);
            }
        }
        $detalles = $data['detalles'] ?? [];
        unset($data['detalles']);
        // NO permitir requisición vacía
        if (!is_array($detalles) || count($detalles) < 1) {
            return back()->withErrors(['detalles' => 'Agrega al menos un item.']);
        }
        // Fechas (columna dateTime)
        $data['fecha_solicitud'] = \Carbon\Carbon::createFromFormat('Y-m-d', $data['fecha_solicitud'])->startOfDay();
        if (!empty($data['fecha_autorizacion'])) {
            $data['fecha_autorizacion'] = \Carbon\Carbon::createFromFormat('Y-m-d', $data['fecha_autorizacion'])->startOfDay();
        } else {
            $data['fecha_autorizacion'] = null;
        }
        $data['creada_por_user_id'] = (int)$user->id;
        // Status válido según tu ENUM (adiós PENDIENTE)
        $data['status'] = ($accion === 'ENVIAR') ? 'CAPTURADA' : 'BORRADOR';
        // Folio obligatorio
        $data['folio'] = $this->makeFolio();
        // Recalcular montos en servidor
        $ivaRate = 0.16;
        $cleanDetalles = [];
        $montoSubtotal = 0;
        $montoTotal    = 0;
        $hasGeneraIvaColumn = Schema::hasColumn('detalles', 'genera_iva');
        foreach ($detalles as $i => $d) {
            $cantidad = (float)($d['cantidad'] ?? 0);
            $precio   = (float)($d['precio_unitario'] ?? 0);
            $desc     = trim((string)($d['descripcion'] ?? ''));
            if ($cantidad <= 0 || $desc === '') {
                return back()->withErrors([
                    "detalles.{$i}.descripcion" => 'Cada item debe tener descripción y cantidad > 0.'
                ]);
            }
            $generaIva = (bool)($d['genera_iva'] ?? true);
            $subtotal = round($cantidad * $precio, 2);
            $iva      = $generaIva ? round($subtotal * $ivaRate, 2) : 0.00;
            $total    = round($subtotal + $iva, 2);
            $montoSubtotal += $subtotal;
            $montoTotal    += $total;
            $row = [
                'sucursal_id'     => !empty($d['sucursal_id']) ? (int)$d['sucursal_id'] : null,
                'cantidad'        => $cantidad,
                'descripcion'     => $desc,
                'precio_unitario' => $precio,
                'subtotal'        => $subtotal,
                'iva'             => $iva,
                'total'           => $total,
            ];
            // Evita "Unknown column genera_iva"
            if ($hasGeneraIvaColumn) {
                $row['genera_iva'] = $generaIva;
            }
            $cleanDetalles[] = $row;
        }
        $data['monto_subtotal'] = round($montoSubtotal, 2);
        $data['monto_total']    = round($montoTotal, 2);
        $requisicion = DB::transaction(function () use ($data, $cleanDetalles) {
            $requisicion = Requisicion::create($data);
            if (method_exists($requisicion, 'detalles')) {
                $requisicion->detalles()->createMany($cleanDetalles);
            }
            return $requisicion;
        });
        if ($accion === 'ENVIAR') {
            try {
                Mail::to(['mrlanaweb@outlook.com', 'jesus.arizmendi@mr-lana.com'])
                    ->send(new RequisicionEnviadaMail(
                        $requisicion->fresh(['detalles', 'sucursal', 'solicitante', 'concepto', 'proveedor', 'comprador'])
                    ));
            } catch (\Throwable $e) {
                report($e);
                return redirect()
                    ->route('requisiciones.index')
                    ->with('warning', 'Requisición guardada, pero no se pudo enviar el correo. Revisa Mail.');
            }
        }
        return redirect()
            ->route('requisiciones.index')
            ->with('success', $accion === 'ENVIAR'
                ? 'Requisición enviada y guardada correctamente.'
                : 'Requisición guardada como borrador.');
    }

    public function update(RequisicionUpdateRequest $request, Requisicion $requisicion): RedirectResponse {
        $data = $request->validated();
        $detalles = $data['detalles'] ?? null;
        unset($data['detalles']);
        $requisicion->update($data);
        if (is_array($detalles)) {
            $requisicion->detalles()->delete();
            $requisicion->detalles()->createMany($detalles);
        }
        return redirect()->route('requisiciones.index')->with('success', 'Requisición actualizada.');
    }

    public function destroy(Request $request, Requisicion $requisicion): RedirectResponse {
        $rol = strtoupper((string)($request->user()->rol ?? 'COLABORADOR'));
        if ($rol === 'COLABORADOR') {
            $empleadoId = $request->user()->empleado_id;
            abort_unless($empleadoId && (int)$requisicion->solicitante_id === (int)$empleadoId, 403);
            abort_unless($requisicion->status === 'BORRADOR', 403);
        } elseif (!in_array($rol, ['ADMIN', 'CONTADOR'], true)) {
            abort(403);
        }
        $requisicion->update(['status' => 'ELIMINADA']);
        return redirect()->route('requisiciones.index')->with('success', 'Requisición eliminada.');
    }

    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse {
        $rol = strtoupper((string)($request->user()->rol ?? 'COLABORADOR'));
        abort_unless(in_array($rol, ['ADMIN', 'CONTADOR'], true), 403);
        $ids = $request->validated()['ids'];
        Requisicion::query()
            ->whereIn('id', $ids)
            ->update(['status' => 'ELIMINADA']);
        return redirect()->route('requisiciones.index')->with('success', 'Requisiciones eliminadas.');
    }

    private function catalogos($user): array {
        $rol = strtoupper((string)($user->rol ?? 'COLABORADOR'));
        $corporativos = Corporativo::select('id', 'nombre', 'activo')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $sucursales = Sucursal::select('id', 'nombre', 'codigo', 'corporativo_id', 'activo')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $conceptos = Concepto::select('id', 'nombre', 'activo')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
        $proveedores = Proveedor::select('id', 'razon_social', 'rfc', 'clabe', 'banco', 'status')
            ->where('status', 'ACTIVO')
            ->orderBy('razon_social')
            ->limit(1000)
            ->get();
        $empleadosQ = Empleado::select('id', 'nombre', 'apellido_paterno', 'apellido_materno', 'sucursal_id', 'activo')
            ->where('activo', true)
            ->orderBy('nombre');
        if ($rol === 'COLABORADOR' && $user->empleado_id) {
            $empleadosQ->where('id', $user->empleado_id);
        }
        $empleados = $empleadosQ->get()
            ->map(fn($e) => [
                'id'          => $e->id,
                'nombre'      => trim($e->nombre . ' ' . $e->apellido_paterno . ' ' . ($e->apellido_materno ?? '')),
                'sucursal_id' => $e->sucursal_id,
                'activo'      => $e->activo,
            ]);
        return [
            'corporativos' => $corporativos,
            'sucursales'   => $sucursales,
            'empleados'    => $empleados,
            'conceptos'    => $conceptos,
            'proveedores'  => $proveedores,
        ];
    }

    private function makeFolio(): string {
        do {
            $folio = 'REQ-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (Requisicion::where('folio', $folio)->exists());
        return $folio;
    }

}
