<?php

namespace App\Http\Controllers;

use App\Http\Requests\Requisicion\BulkDestroyRequest;
use App\Http\Requests\Requisicion\RequisicionIndexRequest;
use App\Http\Requests\Requisicion\RequisicionStoreRequest;
use App\Http\Requests\Requisicion\RequisicionUpdateRequest;
use App\Http\Resources\RequisicionResource;
use App\Models\Concepto;
use App\Models\Corporativo;
use App\Models\Empleado;
use App\Models\Proveedor;
use App\Models\Requisicion;
use App\Models\Sucursal;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequisicionController extends Controller
{
    /**
     * INDEX
     * - filtros / paginación / orden
     * - catálogos para SearchableSelect
     * - conteos por tabs
     */
    public function index(RequisicionIndexRequest $request): Response
    {
        $q = $request->validated();

        $perPage = (int)($q['perPage'] ?? 15);
        if (!in_array($perPage, [10, 15, 25, 50, 100], true)) $perPage = 15;

        $sort = $q['sort'] ?? 'id';
        $dir  = $q['dir'] ?? 'desc';

        $allowedSort = ['id','folio','tipo','status','monto_total','fecha_captura','fecha_pago'];
        if (!in_array($sort, $allowedSort, true)) $sort = 'id';
        if (!in_array($dir, ['asc','desc'], true)) $dir = 'desc';

        $query = Requisicion::query()
            ->with([
                'comprador:id,nombre',
                'sucursal:id,nombre,codigo,corporativo_id,activo',
                'solicitante:id,nombre,apellido_paterno,apellido_materno,sucursal_id,puesto,activo',
                'concepto:id,nombre,activo',
                // Proveedor NO tiene "activo"
                'proveedor:id,nombre_comercial',
            ])
            ->search($q['q'] ?? null)
            ->statusTab($q['tab'] ?? 'TODAS')
            ->when(($q['status'] ?? '') !== '', fn($qq) => $qq->where('status', $q['status']))
            ->when(($q['tipo'] ?? '') !== '', fn($qq) => $qq->where('tipo', $q['tipo']))
            ->when(($q['comprador_corp_id'] ?? '') !== '', fn($qq) => $qq->where('comprador_corp_id', (int)$q['comprador_corp_id']))
            ->when(($q['sucursal_id'] ?? '') !== '', fn($qq) => $qq->where('sucursal_id', (int)$q['sucursal_id']))
            ->when(($q['solicitante_id'] ?? '') !== '', fn($qq) => $qq->where('solicitante_id', (int)$q['solicitante_id']))
            ->dateRangeCaptura($q['fecha_from'] ?? null, $q['fecha_to'] ?? null);

        $requisiciones = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();

        // Catálogos para filtros / modales
        $corporativos = Corporativo::query()
            ->select('id','nombre','activo')
            ->orderBy('nombre')
            ->get();

        $sucursales = Sucursal::query()
            ->select('id','nombre','codigo','corporativo_id','activo')
            ->orderBy('nombre')
            ->get();

        $empleados = Empleado::query()
            ->select('id','nombre','apellido_paterno','apellido_materno','sucursal_id','puesto','activo')
            ->orderBy('nombre')
            ->get();

        $conceptos = Concepto::query()
            ->select('id','nombre','activo')
            ->orderBy('nombre')
            ->get();

        // Proveedor NO tiene "activo"
        $proveedores = Proveedor::query()
            ->select('id','nombre_comercial')
            ->orderBy('nombre_comercial')
            ->limit(500)
            ->get();

        // Conteos por tabs (misma base de filtros, sin tab; opcionalmente respeta status exacto si viene)
        $baseCounts = Requisicion::query()
            ->search($q['q'] ?? null)
            ->when(($q['status'] ?? '') !== '', fn($qq) => $qq->where('status', $q['status']))
            ->when(($q['tipo'] ?? '') !== '', fn($qq) => $qq->where('tipo', $q['tipo']))
            ->when(($q['comprador_corp_id'] ?? '') !== '', fn($qq) => $qq->where('comprador_corp_id', (int)$q['comprador_corp_id']))
            ->when(($q['sucursal_id'] ?? '') !== '', fn($qq) => $qq->where('sucursal_id', (int)$q['sucursal_id']))
            ->when(($q['solicitante_id'] ?? '') !== '', fn($qq) => $qq->where('solicitante_id', (int)$q['solicitante_id']))
            ->dateRangeCaptura($q['fecha_from'] ?? null, $q['fecha_to'] ?? null);

        $counts = [
            'pendientes' => (clone $baseCounts)->statusTab('PENDIENTES')->count(),
            'aprobadas'  => (clone $baseCounts)->statusTab('APROBADAS')->count(),
            'rechazadas' => (clone $baseCounts)->statusTab('RECHAZADAS')->count(),
            'todas'      => (clone $baseCounts)->count(),
        ];

        return Inertia::render('Requisiciones/Index', [
            'filters' => [
                'q' => $q['q'] ?? '',
                'tab' => $q['tab'] ?? 'TODAS',
                'status' => $q['status'] ?? '',
                'tipo' => $q['tipo'] ?? '',
                'comprador_corp_id' => $q['comprador_corp_id'] ?? '',
                'sucursal_id' => $q['sucursal_id'] ?? '',
                'solicitante_id' => $q['solicitante_id'] ?? '',
                'fecha_from' => $q['fecha_from'] ?? '',
                'fecha_to' => $q['fecha_to'] ?? '',
                'perPage' => $perPage,
                'sort' => $sort,
                'dir' => $dir,
            ],
            'counts' => $counts,
            'requisiciones' => RequisicionResource::collection($requisiciones),

            'catalogos' => [
                'corporativos' => $corporativos,
                'sucursales' => $sucursales,
                'empleados' => $empleados->map(fn($e) => [
                    'id' => $e->id,
                    'nombre' => trim($e->nombre.' '.$e->apellido_paterno.' '.($e->apellido_materno ?? '')),
                    'sucursal_id' => $e->sucursal_id,
                    'puesto' => $e->puesto,
                    'activo' => $e->activo,
                ]),
                'conceptos' => $conceptos,

                // Proveedor NO tiene "activo"
                'proveedores' => $proveedores->map(fn($p) => [
                    'id' => $p->id,
                    'nombre' => $p->nombre_comercial,
                ]),
            ],
        ]);
    }

    /**
     * CREATE (si tu UI navega a /requisiciones/create)
     */
    public function create(Request $request): Response
    {
        return Inertia::render('Requisiciones/Create', [
            // Si ya estás usando catálogos en Index, pásalos igual aquí
            'catalogos' => [
                'corporativos' => \App\Models\Corporativo::query()
                    ->select('id', 'nombre', 'codigo', 'activo')
                    ->orderBy('nombre')
                    ->get(),

                'sucursales' => \App\Models\Sucursal::query()
                    ->select('id', 'nombre', 'codigo', 'corporativo_id', 'activo')
                    ->orderBy('nombre')
                    ->get(),

                'empleados' => \App\Models\Empleado::query()
                    ->select('id', 'nombre', 'puesto', 'activo')
                    ->orderBy('nombre')
                    ->get(),
            ],
        ]);
    }

    /**
     * SHOW
     */
    public function show(Requisicion $requisicion): RedirectResponse
    {
        return redirect()
            ->route('requisiciones.index')
            ->with('info', "Detalle de requisición {$requisicion->folio} pendiente (vista Show).");
    }

    /**
     * PRINT
     * Devuelve HTML imprimible simple.
     */
    public function print(Requisicion $requisicion)
    {
        $requisicion->load([
            'comprador:id,nombre',
            'sucursal:id,nombre',
            'solicitante:id,nombre,apellido_paterno,apellido_materno',
            'concepto:id,nombre',
            'proveedor:id,nombre_comercial',
        ]);

        $html = '
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Requisición '.$requisicion->folio.'</title>
  <style>
    body{font-family:Arial,Helvetica,sans-serif;margin:24px;color:#111}
    .card{border:1px solid #ddd;border-radius:12px;padding:16px}
    h1{font-size:18px;margin:0 0 8px}
    .muted{color:#555;font-size:12px}
    table{width:100%;border-collapse:collapse;margin-top:12px}
    td{padding:8px;border-top:1px solid #eee;font-size:13px;vertical-align:top}
    .right{text-align:right}
  </style>
</head>
<body>
  <div class="card">
    <h1>Requisición '.$requisicion->folio.'</h1>
    <div class="muted">Generado por MR-Lana ERP · '.now()->format('Y-m-d H:i').'</div>

    <table>
      <tr><td><b>Tipo</b></td><td class="right">'.e($requisicion->tipo).'</td></tr>
      <tr><td><b>Estatus</b></td><td class="right">'.e($requisicion->status).'</td></tr>
      <tr><td><b>Corporativo</b></td><td class="right">'.e(optional($requisicion->comprador)->nombre ?? '—').'</td></tr>
      <tr><td><b>Sucursal</b></td><td class="right">'.e(optional($requisicion->sucursal)->nombre ?? '—').'</td></tr>
      <tr><td><b>Solicitante</b></td><td class="right">'.e(trim((optional($requisicion->solicitante)->nombre ?? '')." ".(optional($requisicion->solicitante)->apellido_paterno ?? '')." ".(optional($requisicion->solicitante)->apellido_materno ?? '')) ?: '—').'</td></tr>
      <tr><td><b>Proveedor</b></td><td class="right">'.e(optional($requisicion->proveedor)->nombre_comercial ?? '—').'</td></tr>
      <tr><td><b>Concepto</b></td><td class="right">'.e(optional($requisicion->concepto)->nombre ?? '—').'</td></tr>
      <tr><td><b>Monto total</b></td><td class="right"><b>$'.number_format((float)$requisicion->monto_total, 2).'</b></td></tr>
    </table>

    <script>window.onload=()=>{ try{ window.print(); }catch(e){} }</script>
  </div>
</body>
</html>';

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /**
     * STORE
     */
    public function store(RequisicionStoreRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['creada_por_user_id'] = $request->user()->id;

        Requisicion::create($data);

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición creada.');
    }

    /**
     * UPDATE
     */
    public function update(RequisicionUpdateRequest $request, Requisicion $requisicion): RedirectResponse
    {
        $requisicion->update($request->validated());

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición actualizada.');
    }

    /**
     * DESTROY
     */
    public function destroy(Requisicion $requisicion): RedirectResponse
    {
        $requisicion->delete();

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisición eliminada.');
    }

    /**
     * BULK DESTROY
     */
    public function bulkDestroy(BulkDestroyRequest $request): RedirectResponse
    {
        $ids = $request->validated()['ids'];

        Requisicion::query()->whereIn('id', $ids)->delete();

        return redirect()
            ->route('requisiciones.index')
            ->with('success', 'Requisiciones eliminadas.');
    }

    /**
     * PAGAR (GET)
     */
    public function pagar(Requisicion $requisicion): RedirectResponse
    {
        return redirect()
            ->route('requisiciones.index')
            ->with('info', "Pantalla de pago para {$requisicion->folio} pendiente (vista Pagar).");
    }

    /**
     * PAGAR (POST)
     * Marca como PAGADA + fecha_pago.
     */
    public function storePago(Request $request, Requisicion $requisicion): RedirectResponse
    {
        $data = $request->validate([
            'fecha_pago' => ['required','date'],
        ]);

        $requisicion->update([
            'status' => 'PAGADA',
            'fecha_pago' => $data['fecha_pago'],
        ]);

        return redirect()
            ->route('requisiciones.index')
            ->with('success', "Requisición {$requisicion->folio} marcada como PAGADA.");
    }

    /**
     * COMPROBAR (GET)
     */
    public function comprobar(Requisicion $requisicion): RedirectResponse
    {
        return redirect()
            ->route('requisiciones.index')
            ->with('info', "Pantalla de comprobación para {$requisicion->folio} pendiente (vista Comprobar).");
    }

    /**
     * COMPROBANTES (POST)
     * Nota: tu migración usa tabla "comprobantes". Aquí solo dejo placeholder sin Storage para no inventar columnas.
     */
    public function storeComprobante(Request $request, Requisicion $requisicion): RedirectResponse
    {
        $request->validate([
            // Cuando implementes comprobantes reales, cambia esto.
            'files' => ['required','array','min:1'],
            'files.*' => ['file','mimes:pdf,jpg,jpeg,png','max:10240'],
        ]);

        // Si por ahora solo quieres marcar el estatus:
        $requisicion->update(['status' => 'COMPROBADA']);

        return redirect()
            ->route('requisiciones.index')
            ->with('success', "Comprobantes cargados. Requisición {$requisicion->folio} marcada como COMPROBADA.");
    }
    
}
