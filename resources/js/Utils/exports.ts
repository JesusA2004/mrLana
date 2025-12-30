// resources/js/Utils/exports.ts

export type QSValue =
  | string
  | number
  | boolean
  | null
  | undefined
  | Array<string | number | boolean | null | undefined>

export type QSRecord = Record<string, QSValue>

/**
 * Convierte un objeto a querystring.
 * - Ignora null/undefined
 * - Ignora strings vacíos
 * - Arrays -> k[]=v1&k[]=v2
 */
export function toQS(obj: QSRecord): string {
    const p = new URLSearchParams()

    Object.entries(obj ?? {}).forEach(([k, v]) => {
        if (v === null || v === undefined) return

        if (typeof v === 'string') {
        const s = v.trim()
        if (!s) return
        p.set(k, s)
        return
        }

        if (Array.isArray(v)) {
        v.filter(x => x !== null && x !== undefined && String(x).trim() !== '')
            .forEach(x => p.append(`${k}[]`, String(x)))
        return
        }

        p.set(k, String(v))
    })

    const qs = p.toString()
    return qs ? `?${qs}` : ''
}

/**
 * Descarga un archivo sin navegar ni abrir pestañas.
 * Nota: el backend debe responder con Content-Disposition: attachment para “forzar” descarga.
 */
export function downloadFile(url: string): void {
    const a = document.createElement('a')
    a.href = url
    a.download = '' // fuerza intención de descarga
    a.rel = 'noopener'
    a.style.display = 'none'
    document.body.appendChild(a)
    a.click()
    a.remove()
}
