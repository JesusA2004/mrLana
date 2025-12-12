import { meetsBasePasswordRule } from './password'

export function passwordProgressCopy(value: string) {
  if (!value.length) {
    return {
      title: 'Define una contraseña nueva',
      desc: 'Usa una combinación larga y difícil de adivinar.',
      badge: 'Sin iniciar',
      badgeClass: 'bg-slate-100 text-slate-600 border-slate-200',
    }
  }

  const hasSymbol = /[^A-Za-z0-9]/.test(value)
  const meetsBase = meetsBasePasswordRule(value)

  if (!meetsBase) {
    return {
      title: 'Vas calentando…',
      desc: 'Completa los criterios básicos para que sea aceptada.',
      badge: 'En progreso',
      badgeClass: 'bg-amber-50 text-amber-700 border-amber-200',
    }
  }

  if (meetsBase && hasSymbol) {
    return {
      title: 'Excelente. Va por muy buen camino.',
      desc: 'Cumple los criterios y tiene complejidad extra.',
      badge: 'Fuerte',
      badgeClass: 'bg-emerald-50 text-emerald-700 border-emerald-200',
    }
  }

  return {
    title: 'Bien. Va por buen camino.',
    desc: 'Cumple lo esencial; un símbolo la haría aún más robusta.',
    badge: 'Buena',
    badgeClass: 'bg-sky-50 text-sky-700 border-sky-200',
  }
}
