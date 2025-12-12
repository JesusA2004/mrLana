/**
 * password.ts
 * ------------------------------------------------------
 * Utilidades puras para validación y evaluación
 * de contraseñas.
 * No depende de Vue.
 */

export const BASE_PASSWORD_REGEX = /^(?=.*[A-Z])(?=.*\d).{8,}$/

/**
 * Calcula un score simple de fortaleza (0–4)
 */
export function calcPasswordScore(value: string): number {
  let score = 0
  if (value.length >= 8) score++
  if (/[A-Z]/.test(value)) score++
  if (/\d/.test(value)) score++
  if (/[^A-Za-z0-9]/.test(value)) score++
  return score
}

/**
 * Checklist evaluable para UI
 */
export function passwordChecklist(value: string) {
  return [
    { key: 'length', label: 'Mínimo 8 caracteres', ok: value.length >= 8 },
    { key: 'uppercase', label: 'Al menos 1 mayúscula', ok: /[A-Z]/.test(value) },
    { key: 'number', label: 'Al menos 1 número', ok: /\d/.test(value) },
    { key: 'symbol', label: 'Símbolo (recomendado)', ok: /[^A-Za-z0-9]/.test(value) },
  ]
}

/**
 * Valida formato base requerido por backend
 */
export function meetsBasePasswordRule(value: string): boolean {
  return BASE_PASSWORD_REGEX.test(value)
}
