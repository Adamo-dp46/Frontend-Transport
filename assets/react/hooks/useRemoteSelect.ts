import { useCallback, useEffect, useRef, useState } from "react"

export interface RemoteOption {
    id: number | string
    label: string
    raw?: Record<string, unknown>
}

interface UseRemoteSelectOptions {
    resource: string // slug : fournisseurs, pieces, cars, etc.
    limit?: number
    debounceMs?: number
    /** Valeur initiale pré-sélectionnée */
    initialValue?: RemoteOption | null
}

export function useRemoteSelect({
    resource,
    limit = 10,
    debounceMs = 300,
    initialValue = null
}: UseRemoteSelectOptions)
{
    const [query, setQuery] = useState(initialValue?.label ?? "")
    const [options, setOptions] = useState<RemoteOption[]>([])
    const [selected, setSelected] = useState<RemoteOption | null>(initialValue)
    const [loading, setLoading] = useState(false)
    const [error, setError] = useState<string | null>(null)
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)

    const fetchOptions = useCallback(async (q: string) => {
        setLoading(true)
        setError(null)
        try {
            const url = `/search/${resource}?q=${encodeURIComponent(q)}&limit=${limit}`
            const res = await fetch(url, {
                headers: { "X-Requested-With": "XMLHttpRequest" },
            })
            if(!res.ok) {
                throw new Error("Erreur réseau")
            }
            const data: RemoteOption[] = await res.json()
            setOptions(data)
        } catch (e) {
            setError("Erreur lors de la recherche")
            setOptions([])
        } finally {
            setLoading(false)
        }
    }, [resource, limit])

    // Debounce sur la query
    useEffect(() => {
        if(debounceRef.current) {
            clearTimeout(debounceRef.current)
        }
        debounceRef.current = setTimeout(() => {
            fetchOptions(query)
        }, debounceMs)
        return () => {
            if(debounceRef.current) {
                clearTimeout(debounceRef.current)
            }
        }
    }, [query, fetchOptions, debounceMs])

    const handleSelect = useCallback((option: RemoteOption) => {
        setSelected(option)
        setQuery(option.label)
        setOptions([])
    }, [])

    const handleClear = useCallback(() => {
        setSelected(null)
        setQuery("")
        setOptions([])
    }, [])

    return {
        query, setQuery,
        options,
        selected, handleSelect, handleClear,
        loading, error
    }
}
