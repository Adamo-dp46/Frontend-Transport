import { useCallback, useRef, useState } from "react"

interface SearchResult {
    value: string
    label: string
    raw?: Record<string, unknown>
}

interface UseRemoteSearchOptions {
    resource: string
    minChars?: number
    limit?: number
    debounceMs?: number
}

export function useRemoteSearch({
    resource,
    minChars = 2,
    limit = 20,
    debounceMs = 300
}: UseRemoteSearchOptions) {
    const [results, setResults] = useState<SearchResult[]>([])
    const [loading, setLoading] = useState(false)
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)

    const search = useCallback((q: string) => {
        if(debounceRef.current) clearTimeout(debounceRef.current)
        if(q.length < minChars) {
            setResults([])
            return
        }
        debounceRef.current = setTimeout(async () => {
            setLoading(true)
            try {
                const res = await fetch(`/search?resource=${resource}&q=${encodeURIComponent(q)}&limit=${limit}`)
                const data: SearchResult[] = await res.json()
                setResults(data)
            } catch {
                setResults([])
            } finally {
                setLoading(false)
            }
        }, debounceMs)
    }, [resource, minChars, limit, debounceMs])

    const reset = () => setResults([])

    return {
        results,
        loading,
        search,
        reset
    }
}