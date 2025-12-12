/**
 * useLoginMotion
 * ------------------------------------------------------
 * Maneja micro-interacciones del login:
 * - Tilt 3D suave al mover el mouse (desktop only)
 * - Shake visual cuando hay errores de validación
 *
 * Importante:
 * - NO modifica directamente `transform`
 * - Usa CSS variables (--rx, --ry) para no romper animaciones
 */
export function useLoginMotion() {

  /**
   * Aplica efecto tilt 3D al elemento recibido
   * @param el HTMLElement del card (login-card)
   */
  const mount = (el: HTMLElement | null) => {
    if (!el) return

    // Evita aplicar tilt en dispositivos touch (mobile/tablet)
    const isTouch = window.matchMedia('(hover: none)').matches
    if (isTouch) return

    /**
     * Calcula la posición del mouse relativa al card
     * y ajusta las variables CSS para la rotación 3D
     */
    const onMove = (e: MouseEvent) => {
      const r = el.getBoundingClientRect()

      // Normaliza el rango a [-0.5, 0.5]
      const x = (e.clientX - r.left) / r.width - 0.5
      const y = (e.clientY - r.top) / r.height - 0.5

      // Se actualizan variables CSS (no transform directo)
      el.style.setProperty('--rx', `${(-y * 4).toFixed(2)}deg`)
      el.style.setProperty('--ry', `${(x * 6).toFixed(2)}deg`)
    }

    /**
     * Resetea la rotación al salir el mouse
     */
    const onLeave = () => {
      el.style.setProperty('--rx', '0deg')
      el.style.setProperty('--ry', '0deg')
    }

    el.addEventListener('mousemove', onMove)
    el.addEventListener('mouseleave', onLeave)
  }

  /**
   * Aplica animación "shake" cuando hay errores
   * Se usa en un wrapper para no romper el tilt del card
   * @param el HTMLElement del wrapper (login-shake)
   */
  const pulseOnError = (el: HTMLElement | null) => {
    if (!el) return

    // Reinicia la animación si ya estaba aplicada
    el.classList.remove('shake')
    void el.offsetWidth // fuerza reflow
    el.classList.add('shake')
  }

  return { mount, pulseOnError }
}
