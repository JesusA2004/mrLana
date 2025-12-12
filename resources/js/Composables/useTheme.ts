import { ref } from 'vue'

/**
 * Estado global (singleton) compartido por toda la app
 */
const isDark = ref(false)

/**
 * Aplica el tema a nivel <html> y lo persiste.
 * Ojo: NO usa onMounted para que funcione en cualquier momento.
 */
const apply = (val: boolean) => {
  isDark.value = val
  document.documentElement.classList.toggle('dark', val)
  localStorage.setItem('theme', val ? 'dark' : 'light')
}

/**
 * Inicializa el tema una sola vez (idempotente).
 */
const init = () => {
  const saved = localStorage.getItem('theme') // 'dark' | 'light' | null
  const prefersDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches

  apply(saved ? saved === 'dark' : !!prefersDark)
}

const toggle = () => apply(!isDark.value)

export function useTheme() {
  return { isDark, init, toggle, apply }
}
