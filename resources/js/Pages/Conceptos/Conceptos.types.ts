export type PaginationLink = {
  url: string | null
  label: string
  active: boolean
}

export type Paginated<T> = {
  data: T[]
  links: PaginationLink[]
  current_page: number
  last_page: number
  total: number
  from?: number | null
  to?: number | null
  per_page?: number
}

export type ConceptoRow = {
  id: number
  grupo: string
  nombre: string
  activo: boolean
  created_at?: string
  updated_at?: string
}

export type ConceptosFilters = {
  q?: string
  activo?: string | number
  perPage?: string | number
  sort?: 'id' | 'nombre'
  dir?: 'asc' | 'desc'
}

export type ConceptosPageProps = {
  conceptos: Paginated<ConceptoRow>
  filters?: ConceptosFilters
}
