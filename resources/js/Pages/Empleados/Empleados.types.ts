/**
 * ======================================================
 * Empleados.types.ts
 * ------------------------------------------------------
 * Tipos ligeros para Index + Modal.
 * NOTA:
 * - corporativo/sucursal/area se usan como catálogos.
 * - user es “lite”: solo lo que se muestra (email/rol/activo).
 * ======================================================
 */

export type CorporativoLite = {
    id: number
    nombre: string
    codigo?: string | null
    activo?: boolean
}

export type SucursalLite = {
    id: number
    corporativo_id?: number | null
    nombre: string
    codigo?: string | null
    activo?: boolean
    corporativo?: CorporativoLite | null
}

export type AreaLite = {
    id: number
    corporativo_id?: number | null
    nombre: string
    activo?: boolean
}

export type UserLite = {
    id: number
    email: string
    rol: 'ADMIN' | 'CONTADOR' | 'COLABORADOR'
    activo: boolean
}

export type EmpleadoRow = {
    id: number
    sucursal_id: number
    area_id?: number | null

    nombre: string
    apellido_paterno: string
    apellido_materno?: string | null
    email?: string | null
    telefono?: string | null
    puesto?: string | null
    activo: boolean

    sucursal?: SucursalLite | null
    area?: AreaLite | null
    user?: UserLite | null
}

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

export type EmpleadosFilters = {
    q?: string
    corporativo_id?: string | number
    sucursal_id?: string | number
    area_id?: string | number
    activo?: 'all' | '1' | '0' | string | number
    per_page?: string | number
    perPage?: string | number
    sort?: 'nombre' | 'id'
    dir?: 'asc' | 'desc'
}

export type EmpleadosPageProps = {
    empleados: Paginated<EmpleadoRow>
    corporativos: CorporativoLite[]
    sucursales: SucursalLite[]
    areas: AreaLite[]
    filters?: EmpleadosFilters
}
