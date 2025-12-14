import Swal from 'sweetalert2'
import { computed } from 'vue'

export function useSwalTheme() {
  const isDark = computed(() => document.documentElement.classList.contains('dark'))

  function swalBaseClasses() {
    return {
      popup:
        'rounded-3xl shadow-2xl border border-slate-200/70 dark:border-white/10 ' +
        'bg-white dark:bg-neutral-900 text-slate-800 dark:text-neutral-100',
      title: 'text-slate-900 dark:text-neutral-100',
      htmlContainer: 'text-slate-700 dark:text-neutral-200 !m-0 overflow-x-hidden',
      actions: 'gap-2',
      confirmButton:
        'rounded-2xl px-4 py-2 font-semibold bg-slate-900 text-white hover:bg-slate-800 ' +
        'dark:bg-neutral-100 dark:text-neutral-900 dark:hover:bg-white transition active:scale-[0.98]',
      cancelButton:
        'rounded-2xl px-4 py-2 font-semibold bg-slate-100 text-slate-800 hover:bg-slate-200 ' +
        'dark:bg-neutral-800 dark:text-neutral-100 dark:hover:bg-neutral-700 transition active:scale-[0.98]',
    }
  }

  function toast() {
    return Swal.mixin({
      toast: true,
      position: 'top-end',
      showConfirmButton: false,
      timer: 2200,
      timerProgressBar: true,
      customClass: {
        popup:
          'rounded-2xl shadow-2xl border border-slate-200/70 dark:border-white/10 ' +
          'bg-white dark:bg-neutral-900 text-slate-800 dark:text-neutral-100',
        title: 'text-sm font-semibold',
      },
      didOpen: (p) => {
        if (p) p.classList.toggle('dark', isDark.value)
      },
    })
  }

  function ensurePopupDark() {
    const popup = Swal.getPopup()
    if (popup) popup.classList.toggle('dark', isDark.value)
  }

  return { Swal, isDark, toast, swalBaseClasses, ensurePopupDark }
}
