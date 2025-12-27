export type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

export type CorporativoMini = {
    id: number
    nombre: string
    codigo?: string | null
    activo?: boolean // si viene false => corporativo en baja
}

export type AreaRow = {
    id: number
    corporativo_id: number | null
    nombre: string
    activo: boolean

    // Para mostrar nombre y validar negocio (encadenado)
    corporativo?: CorporativoMini | null

    // Opcional: timestamps (si tu Resource los manda)
    created_at?: string | null
    updated_at?: string | null
}

export type Paginated<T> = {
    data: T[]
    links?: PaginationLink[] // a veces viene aquí
    meta?: {
        links?: PaginationLink[] // a veces viene aquí (Laravel paginator)
        current_page?: number
        last_page?: number
        per_page?: number
        total?: number
        from?: number | null
        to?: number | null
    }

    current_page?: number
    last_page?: number
    per_page?: number
    total?: number
    from?: number | null
    to?: number | null
}

export type AreasFilters = {
    q?: string
    corporativo_id?: string | number | null
    activo?: '' | '1' | '0' | 'all' | string

    // Soportamos ambos nombres para evitar broncas TS
    per_page?: number
    perPage?: number

    sort?: 'nombre' | 'id' | string
    dir?: 'asc' | 'desc' | string
}

export type AreasPageProps = {
    areas: Paginated<AreaRow>
    corporativos: CorporativoMini[]
    filters?: AreasFilters
}
