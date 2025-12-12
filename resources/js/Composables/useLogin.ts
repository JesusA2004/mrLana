/**
 * ======================================================
 * useLogin.ts
 * Composable que concentra TODA la lógica del login:
 * - Validación UX (frontend)
 * - Manejo de submit
 * - Spinner / bloqueo de botón
 * - Unificación de errores frontend + backend
 * ======================================================
 */

import { computed, ref } from 'vue'
import type { InertiaForm } from '@inertiajs/vue3'

/**
 * Tipo genérico para errores de formulario.
 * Ejemplo:
 * {
 *   email: 'El correo es obligatorio',
 *   password: 'Mínimo 8 caracteres'
 * }
 */
export type FieldErrors = Record<string, string>

/**
 * Contrato del formulario de login.
 * Define exactamente qué campos existen.
 */
export interface LoginData {
  email: string
  password: string
  remember: boolean
}

/**
 * ============================
 * Validador simple de email
 * (UX, no seguridad)
 * ============================
 */
function isEmail(value: string): boolean {
  return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())
}

/**
 * ============================
 * Validación de login (UX)
 * ============================
 * - Se ejecuta ANTES de enviar al backend
 * - Evita requests inútiles
 * - Mensajes claros al usuario
 */
function validateLogin(
  data: Pick<LoginData, 'email' | 'password'>
): FieldErrors {
  const errors: FieldErrors = {}

  const email = (data.email ?? '').trim()
  const password = data.password ?? ''

  // Validación de email
  if (!email) {
    errors.email = 'El correo es obligatorio.'
  } else if (!isEmail(email)) {
    errors.email = 'El correo no tiene un formato válido.'
  }

  // Validación de password (solo UX)
  if (!password) {
    errors.password = 'La contraseña es obligatoria.'
  } else if (password.length < 8) {
    errors.password = 'Mínimo 8 caracteres.'
  } else if (!/[A-Z]/.test(password)) {
    errors.password = 'Incluye al menos una mayúscula.'
  } else if (!/\d/.test(password)) {
    errors.password = 'Incluye al menos un número.'
  }

  return errors
}

/**
 * ======================================================
 * Composable principal del login
 * ======================================================
 * Recibe el form de Inertia y devuelve:
 * - errores finales
 * - estado de envío
 * - función submit
 */
export function useLogin(form: InertiaForm<LoginData>) {

  /**
   * Estado local para controlar:
   * - spinner
   * - botón disabled
   * - evitar doble submit
   */
  const isSubmitting = ref(false)

  const attempted = ref(false)

  /**
   * Errores del lado cliente (UX).
   * Se recalculan automáticamente cuando cambian los inputs.
   */
  const clientErrors = computed(() =>
    validateLogin({
      email: form.email,
      password: form.password,
    })
  )

  /**
   * Errores finales que se muestran en pantalla.
   * PRIORIDAD:
   * 1) Backend (Laravel)
   * 2) Frontend (UX)
   */
  const errors = computed(() => ({
    email: (form.errors.email as string) || (attempted.value ? clientErrors.value.email : '') || '',
    password: (form.errors.password as string) || (attempted.value ? clientErrors.value.password : '') || '',
  }))

  /**
   * Indica si el formulario puede enviarse.
   * Si hay errores UX → no se envía.
   */
  const canSubmit = computed(
    () => !clientErrors.value.email && !clientErrors.value.password
  )

  /**
   * ============================
   * Envío del formulario
   * ============================
   */
  function submit() {
    // Evita submits inválidos o repetidos
    if (!canSubmit.value || isSubmitting.value) return

    isSubmitting.value = true

    // Envío real con Inertia
    form.post(route('login'), {
      onFinish: () => {
        // Se ejecuta SIEMPRE (éxito o error)
        isSubmitting.value = false
        form.reset('password') // limpieza por seguridad
      },
    })
  }

  return {
    isSubmitting,
    errors,
    canSubmit,
    submit,
  }
}
