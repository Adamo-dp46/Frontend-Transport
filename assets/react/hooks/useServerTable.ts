import { visit } from "@hotwired/turbo"

export interface ServerMeta {
    total: number
    page: number
    perPage: number
    totalPages: number
    from: number
    to: number
}

export interface ServerTableFilter {
    type: 'text' | 'select' | 'date_range' /*
        - 'date_range' permet de générer 2 params url 'date_from' et 'date_to' pour les filtres de type date
    */
    name: string
    label: string
    placeholder?: string
    options?: { label: string; value: string }[]
}

export function useServerTable(queryParams: Record<string, string>) {
    const buildUrl = (overrides: Record<string, string | null>) => {
        const url = new URL(window.location.href)
        const merged = { ...queryParams, ...overrides }
        Object.entries(merged).forEach(([k, v]) => {
            if (v !== null && v !== '') url.searchParams.set(k, v)
            else url.searchParams.delete(k)
        })
        return url.toString()
    }

    const navigate = (overrides: Record<string, string | null>) => {
        // window.location.href = buildUrl(overrides)
        visit(buildUrl(overrides))
    }

    const getSortState = (field: string): 'asc' | 'desc' | false => {
        if (queryParams.sort !== field) return false
        return (queryParams.dir ?? 'asc') as 'asc' | 'desc'
    }

    /** URL de toggle (asc→desc ou desc→asc) pour le bouton principal */
    const getSortToggleUrl = (field: string): string => {
        const current = getSortState(field)
        const newDir = current === 'asc' ? 'desc' : 'asc'
        return buildUrl({ sort: field, dir: newDir, page: '1' })
    }

    /** URL avec direction explicite pour les items Asc / Desc du dropdown */
    const getSortExplicitUrl = (field: string, dir: 'asc' | 'desc'): string => {
        return buildUrl({ sort: field, dir, page: '1' })
    }

    return { navigate, buildUrl, getSortState, getSortToggleUrl, getSortExplicitUrl }
}