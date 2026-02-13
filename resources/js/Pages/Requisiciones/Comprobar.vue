<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3'
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import DatePickerShadcn from '@/Components/ui/DatePickerShadcn.vue'
import SearchableSelect from '@/Components/ui/SearchableSelect.vue'

import {
  ArrowLeft,
  Upload,
  FileText,
  ExternalLink,
  X,
  Trash2,
  BadgeCheck,
  BadgeX,
  Search,
  Plus,
  Pencil,
  ReceiptText,
} from 'lucide-vue-next'

import type { RequisicionComprobarPageProps } from './Comprobar.types'
import { useRequisicionComprobar } from './useRequisicionComprobar'

declare const route: any

const props = defineProps<RequisicionComprobarPageProps>()

const {
  // core
  req,
  rows,
  money,
  fmtLong,

  // roles/permisos
  role,
  canDelete,
  canReview,
  canFolios,

  // upload form
  form,
  onPickFile,
  doSubmit,
  canSubmit,
  inputBase,

  // pendiente & monto
  pendienteCents,
  centsToFixed,
  onMontoInput,
  montoOverLimit,

  // drag/drop + file ui
  fileKey,
  dragActive,
  pickedName,
  hasPicked,
  clearFile,
  onDropFile,
  onDragEnter,
  onDragOver,
  onDragLeave,

  // preview comprobante a subir
  uploadPreview,
  openUploadPreviewInNewTab,
  removeUploadPreview,

  // tipo_doc dropdown
  tipoOpen,
  tipoWrap,
  tipoOptions,
  tipoSelected,
  setTipo,

  // lista/preview de comprobantes ya subidos
  preview,
  previewWrapRef,
  openPreview,
  closePreview,
  previewTitle,
  estatusLabel,
  estatusPillClass,
  tipoDocLabel,

  // acciones revisión
  approve,
  reject,

  // eliminar comprobante
  destroyComprobante,

  // folios
  foliosOpen,
  toggleFolios,
  folioSelectedId,
  foliosOptions,
  addFolio,
  editFolio,
} = useRequisicionComprobar(props)
</script>

<template>
  <Head title="Comprobar requisición" />

  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between gap-3 min-w-0">
        <div class="flex items-center gap-3 min-w-0">
          <Link
            :href="route('requisiciones.index')"
            class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-200 bg-white
                   hover:bg-slate-50 dark:border-white/10 dark:bg-neutral-900 dark:hover:bg-white/10
                   transition active:scale-[0.98]"
            title="Volver"
          >
            <ArrowLeft class="h-5 w-5 text-slate-800 dark:text-neutral-100" />
          </Link>

          <div class="min-w-0">
            <div class="text-lg sm:text-xl font-black text-slate-900 dark:text-neutral-100 truncate">
              Comprobar
            </div>
          </div>
        </div>

        <!-- Folios (solo ADMIN/CONTADOR) -->
        <div v-if="canFolios" class="shrink-0">
          <button
            type="button"
            class="inline-flex items-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-black
                   border border-slate-200 bg-white hover:bg-slate-50
                   dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                   transition active:scale-[0.98]"
            @click="toggleFolios"
          >
            <Search class="h-4 w-4" />
            Folios
          </button>
        </div>
      </div>
    </template>

    <!-- NO scroll horizontal -->
    <div class="w-full min-w-0 flex-1 overflow-x-hidden">
      <div
        class="mx-auto w-full min-w-0 max-w-[1900px]
               px-3 sm:px-5 md:px-6 lg:px-8 xl:px-8 2xl:px-10
               py-4 sm:py-6 space-y-4"
      >
        <!-- Panel Folios (inline, misma vista) -->
        <div v-if="canFolios && foliosOpen"
             class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-200/70 dark:border-white/10">
            <div class="flex items-center justify-between gap-3">
              <div class="min-w-0">
                <div class="text-sm font-black text-slate-900 dark:text-neutral-100">
                  Folios (búsqueda manual)
                </div>
                <div class="text-xs text-slate-500 dark:text-neutral-300">
                  Busca si un folio ya fue registrado, agrega uno nuevo o edítalo (solo ADMIN).
                </div>
              </div>

              <button
                type="button"
                class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-slate-200 bg-white
                       hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                       transition active:scale-[0.98]"
                title="Cerrar"
                @click="toggleFolios"
              >
                <X class="h-4 w-4" />
              </button>
            </div>
          </div>

          <div class="p-5">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end">
              <div class="lg:col-span-8 min-w-0">
                <SearchableSelect
                  v-model="folioSelectedId"
                  :options="foliosOptions"
                  label="Buscar folio"
                  placeholder="Selecciona un folio…"
                  search-placeholder="Escribe para filtrar…"
                  label-key="folio"
                  secondary-key="monto_total"
                  value-key="id"
                  rounded="2xl"
                  z-index-class="z-[200]"
                />
              </div>

              <div class="lg:col-span-4 flex items-center justify-end gap-2">
                <button
                  type="button"
                  class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-black
                         bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md
                         transition active:scale-[0.98]"
                  @click="addFolio"
                  title="Agregar folio"
                >
                  <Plus class="h-4 w-4" />
                  Agregar
                </button>

                <button
                  v-if="role === 'ADMIN'"
                  type="button"
                  :disabled="!folioSelectedId"
                  class="inline-flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-black
                         border border-slate-200 bg-white hover:bg-slate-50
                         dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                         transition active:scale-[0.98]
                         disabled:opacity-60 disabled:cursor-not-allowed"
                  @click="editFolio"
                  title="Editar folio (solo admin)"
                >
                  <Pencil class="h-4 w-4" />
                  Editar
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Folio requisición -->
        <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/80 dark:bg-neutral-900/60 backdrop-blur shadow-sm px-5 py-3">
          <div class="flex items-center justify-between gap-3">
            <div class="text-[12px] font-black text-slate-500 dark:text-neutral-300">
              Folio:
              <span class="text-slate-900 dark:text-neutral-100">{{ req?.folio || '—' }}</span>
            </div>

            <div class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-black
                        border-slate-200 bg-slate-50 text-slate-700 dark:border-white/10 dark:bg-white/5 dark:text-neutral-200">
              <ReceiptText class="h-4 w-4" />
              Pendiente: <span class="text-slate-900 dark:text-neutral-100">{{ money(pendienteCents / 100) }}</span>
            </div>
          </div>
        </div>

        <!-- GRID: info + preview + relación -->
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-4 items-start min-w-0">
          <!-- Info cards -->
          <div class="xl:col-span-8 2xl:col-span-7 min-w-0">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 items-stretch min-w-0">
              <div class="h-full min-w-0 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
                <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS DE LA REQUISICIÓN</div>
                <div class="mt-3 grid gap-2 text-sm">
                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Solicitante:</span>
                    <span class="font-semibold"> {{ req?.solicitante_nombre || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Concepto:</span>
                    <span class="font-semibold"> {{ req?.concepto || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Cantidad a comprobar:</span>
                    <span class="font-black"> {{ money(req?.monto_total) }}</span>
                  </div>
                </div>
              </div>

              <div class="h-full min-w-0 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm p-5">
                <div class="text-xs font-black text-slate-500 dark:text-neutral-300">DATOS PARA FACTURACIÓN</div>
                <div class="mt-3 grid gap-2 text-sm">
                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Nombre:</span>
                    <span class="font-semibold"> {{ req?.razon_social || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">RFC:</span>
                    <span class="font-semibold"> {{ req?.rfc || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Dirección:</span>
                    <span class="font-semibold"> {{ req?.direccion || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Teléfono:</span>
                    <span class="font-semibold"> {{ req?.telefono || '—' }}</span>
                  </div>

                  <div class="text-slate-900 dark:text-neutral-100 break-words">
                    <span class="font-black text-slate-700 dark:text-neutral-200">Email:</span>
                    <span class="font-semibold break-all"> {{ req?.correo || '—' }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Preview de archivo ya subido (sticky en xl) -->
          <div ref="previewWrapRef" class="xl:col-span-4 2xl:col-span-5 min-w-0 xl:row-span-2">
            <div class="xl:sticky xl:top-6">
              <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-200/70 dark:border-white/10">
                  <div class="flex items-start justify-between gap-3 min-w-0">
                    <div class="min-w-0">
                      <div class="text-xs font-black text-slate-500 dark:text-neutral-300">VISTA PREVIA</div>
                      <div class="text-sm font-black text-slate-900 dark:text-neutral-100 truncate" :title="previewTitle">
                        {{ previewTitle }}
                      </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                      <a
                        v-if="preview?.url"
                        :href="preview.url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="inline-flex items-center gap-2 rounded-2xl px-3 py-2 text-xs font-black
                               border border-slate-200 bg-white hover:bg-slate-50
                               dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                               transition active:scale-[0.98]"
                        title="Abrir en otra pestaña"
                      >
                        <ExternalLink class="h-4 w-4" />
                        Abrir
                      </a>

                      <button
                        v-if="preview"
                        type="button"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-slate-200 bg-white
                               hover:bg-slate-50 hover:shadow-sm dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                               transition active:scale-[0.98]"
                        title="Cerrar vista previa"
                        @click="closePreview"
                      >
                        <X class="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                </div>

                <div class="p-4">
                  <div
                    class="rounded-3xl border border-slate-200/60 dark:border-white/10 bg-slate-50/60 dark:bg-white/5 overflow-hidden"
                    :class="preview ? 'p-0' : 'p-4'"
                  >
                    <div v-if="!preview" class="text-sm text-slate-600 dark:text-neutral-300">
                      Da clic en el nombre del archivo en la lista para previsualizar aquí mismo.
                    </div>

                    <div v-else class="w-full">
                      <div class="w-full h-[70vh] sm:h-[75vh] xl:h-[calc(100vh-260px)] max-h-[820px] xl:max-h-none">
                        <iframe
                          v-if="preview.kind === 'pdf'"
                          :src="preview.url"
                          class="w-full h-full block"
                          style="border:0;"
                          title="Vista previa PDF"
                        />
                        <div v-else-if="preview.kind === 'image'" class="w-full h-full flex items-center justify-center">
                          <img :src="preview.url" alt="Vista previa" class="w-full h-full object-contain" />
                        </div>
                        <div v-else class="w-full h-full flex items-center justify-center p-6 text-center">
                          <div class="text-sm text-slate-600 dark:text-neutral-300">
                            No puedo previsualizar este tipo de archivo aquí. Usa “Abrir”.
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <div v-if="preview?.url" class="mt-3 text-[12px] text-slate-500 dark:text-neutral-400">
                    Tip: si el preview no carga por políticas del navegador, abre en otra pestaña.
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Relación + form -->
          <div class="xl:col-span-8 2xl:col-span-7 min-w-0">
            <div class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/85 dark:bg-neutral-900/70 backdrop-blur shadow-sm overflow-hidden">
              <div class="px-5 py-4 border-b border-slate-200/70 dark:border-white/10">
                <div class="text-lg font-black text-slate-900 dark:text-neutral-100">
                  Relación de comprobaciones de esta requisición
                </div>
                <div class="text-sm text-slate-500 dark:text-neutral-300">
                  Cada comprobante se revisa de forma independiente. Rechazar uno no tumba la requisición.
                </div>
              </div>

              <!-- Tabla XL+ -->
              <div class="hidden xl:block">
                <table class="w-full table-auto">
                  <thead class="bg-slate-50/80 dark:bg-neutral-950/40">
                    <tr class="text-left text-[12px] font-black text-slate-600 dark:text-neutral-300">
                      <th class="px-5 py-3 w-[90px]">ID</th>
                      <th class="px-5 py-3 w-[190px]">Fecha</th>
                      <th class="px-5 py-3 w-[160px]">Tipo</th>
                      <th class="px-5 py-3 w-[160px]">Monto</th>
                      <th class="px-5 py-3">Archivo</th>
                      <th class="px-5 py-3 w-[260px]">Estatus</th>
                      <th class="px-5 py-3 w-[190px] text-right">Acciones</th>
                    </tr>
                  </thead>

                  <tbody>
                    <tr
                      v-for="c in rows"
                      :key="c.id"
                      class="border-t border-slate-200/70 dark:border-white/10 hover:bg-slate-50/70 dark:hover:bg-white/5 transition"
                    >
                      <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100 align-top">
                        {{ c.id }}
                      </td>

                      <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100 align-top">
                        {{ fmtLong(c.fecha_emision) }}
                      </td>

                      <td class="px-5 py-3 text-sm text-slate-800 dark:text-neutral-100 align-top">
                        {{ tipoDocLabel(c.tipo_doc) }}
                      </td>

                      <td class="px-5 py-3 text-sm font-black text-slate-900 dark:text-neutral-100 align-top">
                        {{ money(c.monto) }}
                      </td>

                      <td class="px-5 py-3 text-sm align-top min-w-0">
                        <div class="flex items-center gap-2 min-w-0">
                          <button
                            v-if="c.archivo?.url"
                            type="button"
                            class="inline-flex items-center gap-2 font-black text-indigo-700 hover:text-indigo-800
                                   dark:text-indigo-300 dark:hover:text-indigo-200 min-w-0"
                            title="Previsualizar aquí"
                            @click="openPreview(c)"
                          >
                            <FileText class="h-4 w-4 shrink-0" />
                            <span class="truncate max-w-[320px]">{{ c.archivo.label || 'Ver archivo' }}</span>
                          </button>

                          <a
                            v-if="c.archivo?.url"
                            :href="c.archivo.url"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="inline-flex items-center justify-center h-8 w-8 rounded-2xl border border-slate-200 bg-white
                                   hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition shrink-0"
                            title="Abrir en otra pestaña"
                          >
                            <ExternalLink class="h-4 w-4" />
                          </a>

                          <span v-if="!c.archivo?.url" class="text-slate-500 dark:text-neutral-400">—</span>
                        </div>
                      </td>

                      <td class="px-5 py-3 align-top">
                        <div class="flex items-center gap-2">
                          <div
                            class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-black"
                            :class="estatusPillClass(c.estatus)"
                          >
                            <span
                              class="h-2 w-2 rounded-full"
                              :class="c.estatus === 'APROBADO'
                                ? 'bg-emerald-500'
                                : c.estatus === 'RECHAZADO'
                                  ? 'bg-rose-500'
                                  : 'bg-slate-400'"
                            />
                            {{ estatusLabel(c.estatus) }}
                          </div>

                          <button
                            v-if="canDelete"
                            type="button"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-slate-200 bg-white
                                   hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition"
                            title="Eliminar de base de datos"
                            @click="destroyComprobante(c.id)"
                          >
                            <Trash2 class="h-4 w-4 text-rose-600 dark:text-rose-200" />
                          </button>
                        </div>

                        <div
                          v-if="c.estatus === 'RECHAZADO' && c.comentario_revision"
                          class="mt-2 text-[12px] font-semibold text-rose-700 dark:text-rose-200 break-words"
                        >
                          Motivo: {{ c.comentario_revision }}
                        </div>
                      </td>

                      <td class="px-5 py-3 align-top">
                        <div class="flex items-center justify-end gap-2">
                          <button
                            v-if="canReview"
                            type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-emerald-200 bg-emerald-50
                                   hover:bg-emerald-100 hover:shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10
                                   dark:hover:bg-emerald-500/15 transition active:scale-[0.98]"
                            title="Aprobar"
                            @click="approve(c.id)"
                          >
                            <BadgeCheck class="h-4 w-4 text-emerald-700 dark:text-emerald-200" />
                          </button>

                          <button
                            v-if="canReview"
                            type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-rose-200 bg-rose-50
                                   hover:bg-rose-100 hover:shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10
                                   dark:hover:bg-rose-500/15 transition active:scale-[0.98]"
                            title="Rechazar"
                            @click="reject(c.id)"
                          >
                            <BadgeX class="h-4 w-4 text-rose-700 dark:text-rose-200" />
                          </button>

                          <span v-if="!canReview" class="text-xs text-slate-500 dark:text-neutral-400">
                            —
                          </span>
                        </div>
                      </td>
                    </tr>

                    <tr v-if="rows.length === 0">
                      <td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500 dark:text-neutral-400">
                        Aún no hay comprobantes cargados.
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Cards (< xl) -->
              <div class="xl:hidden p-4 space-y-3">
                <div
                  v-for="c in rows"
                  :key="c.id"
                  class="rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/90 dark:bg-neutral-950/30 p-4 shadow-sm min-w-0"
                >
                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-sm font-black text-slate-900 dark:text-neutral-100">
                        Comprobante con ID: {{ c.id }}
                      </div>
                      <div class="text-[12px] text-slate-500 dark:text-neutral-400">
                        {{ fmtLong(c.fecha_emision) }} · {{ tipoDocLabel(c.tipo_doc) }}
                      </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0">
                      <div
                        class="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-[12px] font-black"
                        :class="estatusPillClass(c.estatus)"
                      >
                        <span
                          class="h-2 w-2 rounded-full"
                          :class="c.estatus === 'APROBADO'
                            ? 'bg-emerald-500'
                            : c.estatus === 'RECHAZADO'
                              ? 'bg-rose-500'
                              : 'bg-slate-400'"
                        />
                        {{ estatusLabel(c.estatus) }}
                      </div>

                      <!-- Eliminar visible en responsive -->
                      <button
                        v-if="canDelete"
                        type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white
                               hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition"
                        title="Eliminar de base de datos"
                        @click="destroyComprobante(c.id)"
                      >
                        <Trash2 class="h-4 w-4 text-rose-600 dark:text-rose-200" />
                      </button>
                    </div>
                  </div>

                  <div class="mt-3 grid grid-cols-2 gap-3 text-sm">
                    <div class="rounded-2xl border border-slate-200/60 dark:border-white/10 bg-slate-50/70 dark:bg-white/5 p-3">
                      <div class="text-[11px] font-black text-slate-600 dark:text-neutral-300">Monto</div>
                      <div class="mt-1 font-black text-slate-900 dark:text-neutral-100">{{ money(c.monto) }}</div>
                    </div>

                    <div class="rounded-2xl border border-slate-200/60 dark:border-white/10 bg-slate-50/70 dark:bg-white/5 p-3 min-w-0">
                      <div class="text-[11px] font-black text-slate-600 dark:text-neutral-300">Archivo</div>
                      <div class="mt-1 min-w-0">
                        <button
                          v-if="c.archivo?.url"
                          type="button"
                          class="inline-flex items-center gap-2 font-black text-indigo-700 hover:text-indigo-800
                                 dark:text-indigo-300 dark:hover:text-indigo-200 min-w-0"
                          @click="openPreview(c)"
                        >
                          <FileText class="h-4 w-4 shrink-0" />
                          <span class="truncate">{{ c.archivo.label || 'Ver archivo' }}</span>
                        </button>
                        <span v-else class="text-slate-500 dark:text-neutral-400">—</span>
                      </div>
                    </div>
                  </div>

                  <div
                    v-if="c.estatus === 'RECHAZADO' && c.comentario_revision"
                    class="mt-3 text-[12px] font-semibold text-rose-700 dark:text-rose-200 break-words"
                  >
                    Motivo: {{ c.comentario_revision }}
                  </div>

                  <div v-if="canReview" class="mt-4 flex items-center justify-end gap-2">
                    <button
                      type="button"
                      class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-emerald-200 bg-emerald-50
                             hover:bg-emerald-100 hover:shadow-sm dark:border-emerald-500/20 dark:bg-emerald-500/10
                             dark:hover:bg-emerald-500/15 transition active:scale-[0.98]"
                      title="Aprobar"
                      @click="approve(c.id)"
                    >
                      <BadgeCheck class="h-4 w-4 text-emerald-700 dark:text-emerald-200" />
                    </button>

                    <button
                      type="button"
                      class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-rose-200 bg-rose-50
                             hover:bg-rose-100 hover:shadow-sm dark:border-rose-500/20 dark:bg-rose-500/10
                             dark:hover:bg-rose-500/15 transition active:scale-[0.98]"
                      title="Rechazar"
                      @click="reject(c.id)"
                    >
                      <BadgeX class="h-4 w-4 text-rose-700 dark:text-rose-200" />
                    </button>
                  </div>
                </div>

                <div v-if="rows.length === 0" class="px-2 py-10 text-center text-sm text-slate-500 dark:text-neutral-400">
                  Aún no hay comprobantes cargados.
                </div>
              </div>

              <!-- Form de carga -->
              <div class="p-5 border-t border-slate-200/70 dark:border-white/10">
                <div class="space-y-4">
                  <!-- Archivo -->
                  <div class="min-w-0">
                    <div class="flex items-end justify-between gap-3">
                      <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">
                        Carga el comprobante
                      </label>

                      <div class="text-[12px] font-black text-slate-500 dark:text-neutral-300">
                        Pendiente:
                        <span class="text-slate-900 dark:text-neutral-100">{{ money(pendienteCents / 100) }}</span>
                      </div>
                    </div>

                    <div
                      class="mt-1 rounded-3xl border bg-white/80 dark:bg-neutral-950/40 p-3 select-none
                             transition duration-200 hover:shadow-sm hover:-translate-y-[1px] min-w-0"
                      :class="dragActive
                        ? 'border-indigo-400/60 ring-2 ring-indigo-500/20 dark:border-indigo-400/40'
                        : 'border-slate-200/70 dark:border-white/10'"
                      @dragenter="onDragEnter"
                      @dragover="onDragOver"
                      @dragleave="onDragLeave"
                      @drop="onDropFile"
                    >
                      <div class="flex items-center gap-3 min-w-0">
                        <input
                          :key="fileKey"
                          id="comprobante-file"
                          type="file"
                          class="sr-only"
                          @change="onPickFile"
                        />

                        <label
                          for="comprobante-file"
                          class="inline-flex items-center justify-center gap-2 rounded-2xl px-6 py-3 text-sm font-black
                                 bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-md
                                 transition active:scale-[0.98] cursor-pointer shrink-0"
                        >
                          <Upload class="h-4 w-4" />
                          Seleccionar archivo
                        </label>

                        <div class="min-w-0 flex-1">
                          <div
                            class="text-sm font-black truncate"
                            :class="hasPicked ? 'text-slate-900 dark:text-neutral-100' : 'text-slate-500 dark:text-neutral-400'"
                            :title="pickedName"
                          >
                            {{ pickedName }}
                          </div>
                          <div class="text-[12px] text-slate-500 dark:text-neutral-400">
                            {{ dragActive ? 'Suelta aquí para adjuntar.' : (hasPicked ? 'Listo para subir.' : 'Adjunta el comprobante correspondiente.') }}
                          </div>
                        </div>

                        <button
                          v-if="hasPicked"
                          type="button"
                          class="inline-flex items-center justify-center h-10 w-10 rounded-2xl border border-slate-200 bg-white
                                 hover:bg-slate-50 hover:shadow-sm dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15
                                 transition active:scale-[0.98] shrink-0"
                          title="Quitar archivo"
                          @click="clearFile"
                        >
                          <X class="h-4 w-4" />
                        </button>
                      </div>
                    </div>

                    <div v-if="form.errors.archivo" class="mt-1 text-xs font-bold text-rose-600">
                      {{ form.errors.archivo }}
                    </div>

                    <!-- PREVIEW del archivo a subir -->
                    <div v-if="uploadPreview" class="mt-3 rounded-3xl border border-slate-200/70 dark:border-white/10 bg-white/80 dark:bg-neutral-950/30 overflow-hidden">
                      <div class="px-4 py-3 border-b border-slate-200/70 dark:border-white/10 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                          <div class="text-xs font-black text-slate-500 dark:text-neutral-300">PREVISUALIZACIÓN ANTES DE SUBIR</div>
                          <div class="text-sm font-black text-slate-900 dark:text-neutral-100 truncate" :title="uploadPreview.label">
                            {{ uploadPreview.label }}
                          </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                          <button
                            type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-slate-200 bg-white
                                   hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition"
                            title="Abrir en otra pestaña"
                            @click="openUploadPreviewInNewTab"
                          >
                            <ExternalLink class="h-4 w-4" />
                          </button>
                          <button
                            type="button"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-2xl border border-slate-200 bg-white
                                   hover:bg-slate-50 dark:border-white/10 dark:bg-white/10 dark:hover:bg-white/15 transition"
                            title="Cerrar preview"
                            @click="removeUploadPreview"
                          >
                            <X class="h-4 w-4" />
                          </button>
                        </div>
                      </div>

                      <div class="p-3">
                        <div class="rounded-2xl border border-slate-200/60 dark:border-white/10 bg-slate-50/60 dark:bg-white/5 overflow-hidden">
                          <div class="h-[320px] sm:h-[380px] md:h-[420px]">
                            <iframe
                              v-if="uploadPreview.kind === 'pdf'"
                              :src="uploadPreview.url"
                              class="w-full h-full block"
                              style="border:0;"
                              title="Preview PDF (antes de subir)"
                            />
                            <div v-else-if="uploadPreview.kind === 'image'" class="w-full h-full flex items-center justify-center">
                              <img :src="uploadPreview.url" class="w-full h-full object-contain" alt="Preview imagen" />
                            </div>
                            <div v-else class="w-full h-full flex items-center justify-center p-6 text-center">
                              <div class="text-sm text-slate-600 dark:text-neutral-300">
                                Este archivo no se puede previsualizar aquí. Ábrelo en otra pestaña.
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>

                  <!-- Monto + tipo + fecha + submit -->
                  <div class="grid grid-cols-1 lg:grid-cols-12 gap-3 items-end min-w-0">
                    <div class="lg:col-span-3 min-w-0">
                      <div class="flex items-end justify-between gap-3">
                        <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">
                          Monto por comprobar
                        </label>
                        <div class="text-[11px] font-black text-slate-500 dark:text-neutral-300">
                          Máx: {{ money(pendienteCents / 100) }}
                        </div>
                      </div>

                      <input
                        v-model="form.monto"
                        @input="onMontoInput"
                        type="number"
                        step="0.01"
                        :max="centsToFixed(pendienteCents)"
                        :class="inputBase"
                        class="mt-1"
                        placeholder="0.00"
                      />

                      <div v-if="montoOverLimit" class="mt-1 text-xs font-bold text-rose-600">
                        El monto no puede superar el pendiente ({{ money(pendienteCents / 100) }}).
                      </div>
                      <div v-else-if="form.errors.monto" class="mt-1 text-xs font-bold text-rose-600">
                        {{ form.errors.monto }}
                      </div>
                    </div>

                    <div class="lg:col-span-4 min-w-0 relative z-[30]" ref="tipoWrap">
                      <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">
                        Tipo de comprobante
                      </label>

                      <button
                        type="button"
                        class="mt-1 w-full rounded-2xl border px-4 py-3 text-sm font-semibold text-left
                               bg-white/90 text-slate-900 hover:bg-slate-50 hover:shadow-sm
                               dark:bg-neutral-950/40 dark:text-neutral-100 dark:hover:bg-white/5
                               border-slate-200/70 dark:border-white/10
                               focus:outline-none focus:ring-2 focus:ring-indigo-500/25 focus:border-indigo-500/40
                               transition active:scale-[0.99]"
                        @click="tipoOpen = !tipoOpen"
                      >
                        <div class="flex items-center justify-between gap-2 min-w-0">
                          <span class="truncate">
                            {{ tipoSelected?.nombre ?? 'Selecciona tipo' }}
                          </span>
                          <span class="text-xs font-black opacity-70">{{ tipoOpen ? '▲' : '▼' }}</span>
                        </div>
                      </button>

                      <div v-if="tipoOpen" class="absolute left-0 right-0 top-full z-[120] mt-2">
                        <div
                          class="rounded-3xl border border-slate-200/80 bg-white shadow-2xl overflow-hidden
                                 dark:border-white/10 dark:bg-neutral-950
                                 animate-in fade-in-0 zoom-in-95 duration-150"
                        >
                          <div class="max-h-64 overflow-auto">
                            <button
                              v-for="t in tipoOptions"
                              :key="t.id"
                              type="button"
                              class="w-full px-4 py-3 text-left text-sm font-semibold transition
                                     hover:bg-slate-50 dark:hover:bg-white/5
                                     flex items-center justify-between gap-2"
                              :class="String(form.tipo_doc) === String(t.id)
                                ? 'bg-indigo-50 text-indigo-800 dark:bg-indigo-500/10 dark:text-indigo-200'
                                : 'text-slate-900 dark:text-neutral-100'"
                              @click="setTipo(String(t.id))"
                            >
                              <span class="truncate">{{ t.nombre }}</span>
                              <span v-if="String(form.tipo_doc) === String(t.id)" class="text-[12px] font-black">✓</span>
                            </button>
                          </div>
                        </div>
                      </div>

                      <div v-if="form.errors.tipo_doc" class="mt-1 text-xs font-bold text-rose-600">
                        {{ form.errors.tipo_doc }}
                      </div>
                    </div>

                    <div class="lg:col-span-3 min-w-0 relative z-[20]">
                      <label class="block text-xs font-black text-slate-600 dark:text-neutral-300">
                        Fecha del comprobante
                      </label>

                      <DatePickerShadcn v-model="form.fecha_emision" placeholder="Selecciona fecha" />

                      <div v-if="form.errors.fecha_emision" class="mt-1 text-xs font-bold text-rose-600">
                        {{ form.errors.fecha_emision }}
                      </div>
                    </div>

                    <div class="lg:col-span-2 min-w-0">
                      <button
                        type="button"
                        @click="doSubmit"
                        :disabled="!canSubmit"
                        class="w-full inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-3 text-sm font-black
                               bg-gradient-to-r from-slate-900 to-slate-950 text-white
                               hover:shadow-md hover:-translate-y-[1px]
                               dark:from-white dark:to-neutral-200 dark:text-slate-900
                               transition active:scale-[0.98]
                               disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:shadow-none disabled:hover:translate-y-0"
                      >
                        <Upload class="h-4 w-4" />
                        Subir
                      </button>
                    </div>
                  </div>
                </div>
              </div>

            </div>
          </div>
        </div>

      </div>
    </div>
  </AuthenticatedLayout>
</template>
