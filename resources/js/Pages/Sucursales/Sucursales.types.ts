export type LinkLike = { url: string | null; label: string; active: boolean } | null

export type CorporativoMini = {
  id: number
  nombre: string
  codigo?: string | null
  activo?: boolean
}

export type SucursalRow = {
  id: number
  corporativo_id: number
  corporativo_nombre?: string | null
  corporativo?: { id: number; nombre: string; codigo?: string | null } | null
  nombre: string
  codigo?: string | null
  ciudad?: string | null
  estado?: string | null
  direccion?: string | null
  activo: boolean
}

export type SucursalesPageProps = {
  sucursales: {
    data: SucursalRow[]
    links: LinkLike[]
    current_page: number
    last_page: number
    total: number
    per_page: number
    from: number | null
    to: number | null
  }
  filters: {
    q: string
    corporativo_id: string | number | null
    activo: string | number | null
    perPage: number
    sort?: string
    dir?: 'asc' | 'desc'
  }
  corporativos: CorporativoMini[]
}
