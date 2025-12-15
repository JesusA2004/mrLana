export type CorporativoMini = {
  id: number
  nombre: string
  codigo?: string | null
  activo?: boolean
}

export type AreaRow = {
  id: number
  corporativo_id: number | null
  nombre: string
  activo: boolean
  corporativo?: CorporativoMini | null
}

export type PaginationLink = { url: string | null; label: string; active: boolean }

export type Paginated<T> = {
  data: T[]
  links: PaginationLink[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from?: number | null
  to?: number | null
}

export type AreasFilters = {
  q?: string
  corporativo_id?: string | number
  activo?: string
  perPage?: number
  sort?: 'nombre' | 'id'
  dir?: 'asc' | 'desc'
}

export type AreasPageProps = {
  areas: Paginated<AreaRow>
  corporativos: CorporativoMini[]
  filters?: AreasFilters
}
