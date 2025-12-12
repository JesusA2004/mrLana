// resources/js/Composables/useLayoutMotion.ts
import { onMounted } from 'vue'

export function useLayoutMotion() {
  const mount = () => {
    // Fade-in general del layout
    const root = document.getElementById('app-layout')
    if (root) {
      root.animate(
        [{ opacity: 0, transform: 'translateY(6px)' },
         { opacity: 1, transform: 'translateY(0)' }],
        { duration: 260, easing: 'ease-out' }
      )
    }
  }

  onMounted(mount)
}
