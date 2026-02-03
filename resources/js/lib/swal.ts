// resources/js/lib/swal.ts
import Swal, { type SweetAlertIcon, type SweetAlertResult } from 'sweetalert2'
import 'sweetalert2/dist/sweetalert2.min.css'

/**
 * Asegura que SweetAlert2 quede por encima de modales/dialogs.
 * (Tu estándar: z-index 20000)
 */
export function ensureSwalTop() {
  const id = 'swal-zfix'
  if (document.getElementById(id)) return
  const style = document.createElement('style')
  style.id = id
  style.innerHTML = `.swal2-container{ z-index:20000 !important; }`
  document.head.appendChild(style)
}

/**
 * Base común: evita glitches de altura/scroll en layouts SPA.
 */
function base() {
  ensureSwalTop()
  return {
    heightAuto: false,
    backdrop: true,
    allowOutsideClick: true,
    allowEscapeKey: true,
  } as const
}

/**
 * Cierra cualquier Swal activo.
 */
export function swalClose() {
  Swal.close()
}

/**
 * Success modal (ok).
 */
export function swalOk(text: string, title = 'Listo') {
  return Swal.fire({
    ...base(),
    icon: 'success',
    title,
    text,
    confirmButtonText: 'Aceptar',
  })
}

/**
 * Error modal (err).
 */
export function swalErr(text: string, title = 'Error') {
  return Swal.fire({
    ...base(),
    icon: 'error',
    title,
    text,
    confirmButtonText: 'Entendido',
  })
}

/**
 * Warning modal.
 */
export function swalWarn(text: string, title = 'Atención') {
  return Swal.fire({
    ...base(),
    icon: 'warning',
    title,
    text,
    confirmButtonText: 'Ok',
  })
}

/**
 * Loading modal: NO se cierra solo. Tú lo cierras con swalClose().
 * Bloquea click afuera y ESC para que no rompan el flujo.
 */
export function swalLoading(text = 'Procesando...') {
  return Swal.fire({
    ...base(),
    title: text,
    allowOutsideClick: false,
    allowEscapeKey: false,
    showConfirmButton: false,
    didOpen: () => {
      Swal.showLoading()
    },
  })
}

/**
 * Confirm genérico: para deletes/bulk deletes.
 */
export function swalConfirm(options: {
  title?: string
  text?: string
  confirmText?: string
  cancelText?: string
  icon?: SweetAlertIcon
  danger?: boolean
}): Promise<SweetAlertResult> {
  const {
    title = 'Confirmar',
    text = '¿Deseas continuar?',
    confirmText = 'Sí, continuar',
    cancelText = 'Cancelar',
    icon = 'warning',
    danger = true,
  } = options

  return Swal.fire({
    ...base(),
    icon,
    title,
    text,
    showCancelButton: true,
    confirmButtonText: confirmText,
    cancelButtonText: cancelText,
    reverseButtons: true,
    focusCancel: true,
    // si quieres “danger” visual, SweetAlert2 usa clases; aquí lo dejamos simple
    // para no casarte con un theme. (Tailwind no aplica directo en Swal.)
  })
}

/**
 * Toast rápido (arriba-derecha). Ideal para "guardado" o "copiado".
 */
export function swalToast(text: string, icon: SweetAlertIcon = 'success') {
  ensureSwalTop()
  return Swal.fire({
    toast: true,
    position: 'top-end',
    icon,
    title: text,
    showConfirmButton: false,
    timer: 2200,
    timerProgressBar: true,
    heightAuto: false,
  })
}

/**
 * Notificación simple: decide modal o toast.
 * Útil para eventos globales.
 */
export function swalNotify(text: string, type: 'ok' | 'err' | 'warn' | 'info' = 'ok') {
  const icon: SweetAlertIcon =
    type === 'ok' ? 'success' : type === 'err' ? 'error' : type === 'warn' ? 'warning' : 'info'

  return swalToast(text, icon)
}
