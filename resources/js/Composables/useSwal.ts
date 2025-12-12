import Swal from 'sweetalert2'

/**
 * useSwal
 *
 * Centraliza SweetAlert para que tus componentes Vue queden cortos.
 * - toastSuccess: notificación corta
 * - toastError: error visible
 * - confirmDanger: confirmación para delete
 */
export function useSwal() {
  const toastSuccess = async (title: string) => {
    await Swal.fire({
      icon: 'success',
      title,
      timer: 1700,
      showConfirmButton: false,
    })
  }

  const toastError = async (title: string, text?: string) => {
    await Swal.fire({
      icon: 'error',
      title,
      text,
    })
  }

  const confirmDanger = async (title: string, text: string) => {
    const res = await Swal.fire({
      icon: 'warning',
      title,
      text,
      showCancelButton: true,
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
      reverseButtons: true,
      focusCancel: true,
    })

    return res.isConfirmed
  }

  return { toastSuccess, toastError, confirmDanger }
}
