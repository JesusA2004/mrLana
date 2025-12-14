import { csrfToken } from '@/Utils/dom'

export async function uploadCorporativoLogo(file: File): Promise<string> {
  const form = new FormData()
  form.append('logo', file)

  const res = await fetch('/corporativos/logo', {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-CSRF-TOKEN': csrfToken(),
    },
    body: form,
  })

  if (!res.ok) {
    let msg = 'No se pudo subir el logo.'
    try {
      const j = await res.json()
      msg = j?.message ?? msg
    } catch {}
    throw new Error(msg)
  }

  const data = await res.json()
  return String(data.logo_path || '')
}
