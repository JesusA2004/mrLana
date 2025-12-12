import { computed, ref, watch } from 'vue'
import {
  calcPasswordScore,
  passwordChecklist,
  meetsBasePasswordRule,
} from '@/Utils/password'
import { passwordProgressCopy } from '@/Utils/passwordProgress'

/**
 * usePasswordForm
 * ------------------------------------------------------------
 * Encapsula toda la lógica reactiva del formulario de contraseña:
 * - errores cliente
 * - checklist
 * - progreso (copy ejecutivo)
 * - barra de fuerza
 * - validación cliente + focus al primer bloqueo
 * - limpieza de errores al teclear
 *
 * El componente Vue solo se queda con UI + wiring.
 */
export function usePasswordForm(form: any) {
  const clientErrors = ref<string[]>([])

  const checks = computed(() => passwordChecklist(form.password || ''))
  const progressCopy = computed(() => passwordProgressCopy(form.password || ''))

  const strength = computed(() => {
    const value = form.password || ''

    if (!value.length) {
      return {
        width: '0%',
        text: 'Mínimo 8 caracteres, al menos 1 mayúscula y 1 número.',
        barClass: 'bg-slate-200',
        textClass: 'text-xs text-slate-500',
      }
    }

    const score = calcPasswordScore(value)

    if (score <= 1) {
      return {
        width: '33%',
        text: 'Contraseña muy débil.',
        barClass: 'bg-red-500',
        textClass: 'text-xs text-red-600',
      }
    }

    if (score === 2) {
      return {
        width: '66%',
        text: 'Contraseña aceptable, puedes mejorarla.',
        barClass: 'bg-amber-400',
        textClass: 'text-xs text-amber-600',
      }
    }

    if (meetsBasePasswordRule(value)) {
      const hasSymbol = /[^A-Za-z0-9]/.test(value)
      return {
        width: '100%',
        text: hasSymbol ? 'Contraseña fuerte.' : 'Contraseña segura.',
        barClass: 'bg-emerald-500',
        textClass: 'text-xs text-emerald-700',
      }
    }

    return {
      width: '100%',
      text: 'Casi listo. Asegúrate de cumplir mayúscula y número.',
      barClass: 'bg-amber-400',
      textClass: 'text-xs text-amber-600',
    }
  })

  /**
   * Limpia errores cliente cuando el usuario cambia inputs
   */
  watch(
    () => [form.current_password, form.password, form.password_confirmation],
    () => {
      if (clientErrors.value.length) clientErrors.value = []
    }
  )

  /**
   * Valida en cliente y opcionalmente enfoca el primer input inválido
   */
  function validateClient(inputs?: {
    current?: HTMLInputElement | null
    password?: HTMLInputElement | null
    confirm?: HTMLInputElement | null
  }) {
    const errors: string[] = []

    const current = (form.current_password || '').trim()
    const pwd = (form.password || '').trim()
    const confirm = (form.password_confirmation || '').trim()

    if (!current) errors.push('La contraseña actual es obligatoria.')
    if (!pwd) errors.push('La nueva contraseña es obligatoria.')
    if (!confirm) errors.push('La confirmación de contraseña es obligatoria.')

    if (pwd && !meetsBasePasswordRule(pwd)) {
      errors.push('La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.')
    }

    if (pwd && confirm && pwd !== confirm) {
      errors.push('La nueva contraseña y la confirmación no coinciden.')
    }

    clientErrors.value = errors

    if (errors.length && inputs) {
      if (!current) inputs.current?.focus()
      else if (!pwd) inputs.password?.focus()
      else if (!confirm) inputs.confirm?.focus()
    }

    return errors.length === 0
  }

  return {
    clientErrors,
    checks,
    progressCopy,
    strength,
    validateClient,
  }
}
