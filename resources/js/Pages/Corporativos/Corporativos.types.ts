export type CorporativoRow = {
    id: number
    nombre: string
    rfc: string | null
    direccion: string | null
    telefono: string | null
    email: string | null
    codigo: string | null
    logo_path: string | null
    activo: boolean
    created_at?: string | null
    updated_at?: string | null
}

export type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

export type CorporativosProps = {

    corporativos: {
        data: CorporativoRow[]
        meta: {
        current_page: number
        last_page: number
        per_page: number
        total: number
        from: number | null
        to: number | null
        links: PaginationLink[]
        }
    }

    filters: {
        q: string
        activo: 'all' | '1' | '0'
        per_page: number
    }
}
