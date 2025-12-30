import { computed, ref, watch } from 'vue'
import {
  calcPasswordScore,
  passwordChecklist,
  meetsBasePasswordRule,
} from '@/Utils/password'
import { passwordProgressCopy } from '@/Utils/passwordProgress'

type Mode = 'update' | 'reset'

type UsePasswordFormOptions = {
  mode?: Mode
}

type ValidateInputs = {
  current?: HTMLInputElement | null
  password?: HTMLInputElement | null
  confirm?: HTMLInputElement | null
}

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
 * Soporta dos modos:
 * - update: requiere current_password
 * - reset: NO requiere current_password (flujo "Olvidé mi contraseña")
 */
export function usePasswordForm(form: any, options: UsePasswordFormOptions = {}) {
  const mode: Mode = options.mode ?? 'update'
  const clientErrors = ref<string[]>([])

  const checks = computed(() => passwordChecklist(form.password || ''))
  const progressCopy = computed(() => passwordProgressCopy(form.password || ''))

  const strength = computed(() => {
    const value = (form.password || '').toString()

    if (!value.length) {
      return {
        width: '0%',
        text: 'Mínimo 8 caracteres, al menos 1 mayúscula y 1 número.',
        barClass: 'bg-slate-200 dark:bg-zinc-800/60',
        textClass: 'text-xs text-slate-500 dark:text-zinc-400',
      }
    }

    const score = calcPasswordScore(value)

    if (score <= 1) {
      return {
        width: '33%',
        text: 'Contraseña muy débil.',
        barClass: 'bg-red-500',
        textClass: 'text-xs text-red-600 dark:text-red-300',
      }
    }

    if (score === 2) {
      return {
        width: '66%',
        text: 'Contraseña aceptable, puedes mejorarla.',
        barClass: 'bg-amber-400',
        textClass: 'text-xs text-amber-700 dark:text-amber-300',
      }
    }

    // score alto
    if (meetsBasePasswordRule(value)) {
      const hasSymbol = /[^A-Za-z0-9]/.test(value)
      return {
        width: '100%',
        text: hasSymbol ? 'Contraseña fuerte.' : 'Contraseña segura.',
        barClass: 'bg-emerald-500',
        textClass: 'text-xs text-emerald-700 dark:text-emerald-300',
      }
    }

    return {
      width: '100%',
      text: 'Casi listo. Asegúrate de cumplir mayúscula y número.',
      barClass: 'bg-amber-400',
      textClass: 'text-xs text-amber-700 dark:text-amber-300',
    }
  })

  /**
   * Limpia errores cliente cuando el usuario cambia inputs relevantes
   */
  watch(
    () => {
      const pwd = form.password
      const conf = form.password_confirmation
      if (mode === 'update') return [form.current_password, pwd, conf]
      return [pwd, conf]
    },
    () => {
      if (clientErrors.value.length) clientErrors.value = []
    }
  )

  /**
   * Valida en cliente y opcionalmente enfoca el primer input inválido
   */
  function validateClient(inputs?: ValidateInputs) {
    const errors: string[] = []

    const current = ((form.current_password ?? '') as string).trim()
    const pwd = ((form.password ?? '') as string).trim()
    const confirm = ((form.password_confirmation ?? '') as string).trim()

    // Reglas por modo
    if (mode === 'update') {
      if (!current) errors.push('La contraseña actual es obligatoria.')
    }

    if (!pwd) errors.push('La nueva contraseña es obligatoria.')
    if (!confirm) errors.push('La confirmación de contraseña es obligatoria.')

    if (pwd && !meetsBasePasswordRule(pwd)) {
      errors.push('La nueva contraseña debe tener mínimo 8 caracteres, una mayúscula y un número.')
    }

    if (pwd && confirm && pwd !== confirm) {
      errors.push('La nueva contraseña y la confirmación no coinciden.')
    }

    clientErrors.value = errors

    // Focus inteligente
    if (errors.length && inputs) {
      if (mode === 'update' && !current) inputs.current?.focus()
      else if (!pwd) inputs.password?.focus()
      else if (!confirm) inputs.confirm?.focus()
    }

    return errors.length === 0
  }

  return {
    mode,
    clientErrors,
    checks,
    progressCopy,
    strength,
    validateClient,
  }
}
