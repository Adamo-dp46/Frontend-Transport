import { useState } from "react"
import { useRemoteSearch } from "../hooks/useRemoteSearch"
import { Popover, PopoverContent, PopoverTrigger } from "../../components/ui/popover"
import { Button } from "../../components/ui/button"
import { Check, ChevronsUpDown, Loader2 } from "lucide-react"
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "../../components/ui/command"
import { cn } from "../../lib/utils"

interface RemoteComboboxProps {
    resource: string
    value: string // id sélectionné
    label: string // label affiché
    placeholder?: string
    onChange: (value: string, label: string, raw?: Record<string, unknown>) => void
    disabled?: boolean
}

export function RemoteCombobox({
    resource,
    value,
    label,
    placeholder = 'Rechercher…',
    onChange,
    disabled = false,
}: RemoteComboboxProps) {
    const [open, setOpen] = useState(false)
    const { results, loading, search, reset } = useRemoteSearch({ resource })

    return (
        <Popover open={open} onOpenChange={(o) => { setOpen(o); if (!o) reset() }}>
            <PopoverTrigger asChild>
                <Button
                    variant="outline"
                    role="combobox"
                    disabled={disabled}
                    className="w-full justify-between font-normal"
                >
                    {value ? label : <span className="text-muted-foreground">{placeholder}</span>}
                    <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[var(--radix-popover-trigger-width)] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput
                        placeholder={placeholder}
                        onValueChange={(q) => search(q)}
                    />
                    <CommandList>
                        {loading && (
                            <div className="flex items-center justify-center py-4 text-sm text-muted-foreground gap-2">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Recherche…
                            </div>
                        )}
                        {!loading && results.length === 0 && (
                            <CommandEmpty>
                                Tapez au moins 2 caractères pour rechercher.
                            </CommandEmpty>
                        )}
                        {!loading && results.length > 0 && (
                            <CommandGroup>
                                {results.map((item) => (
                                    <CommandItem
                                        key={item.value}
                                        value={item.value}
                                        onSelect={() => {
                                            onChange(item.value, item.label, item.raw)
                                            setOpen(false)
                                            reset()
                                        }}
                                    >
                                        <Check className={cn(
                                            'mr-2 h-4 w-4',
                                            value === item.value ? 'opacity-100' : 'opacity-0'
                                        )} />
                                        {item.label}
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    )
} /*
    const [gareId, setGareId] = useState("")
    const [gareLabel, setGareLabel] = useState("")
    <RemoteCombobox
        resource="gares"
        value={gareId}
        label={gareLabel}
        placeholder="Rechercher une gare…"
        onChange={(val, lbl) => {
            setGareId(val)
            setGareLabel(lbl)
        }}
    />

    ...
    export interface ServerTableFilter {
        type: 'text' | 'select' | 'date_range' | 'remote_select'
        name: string
        label: string
        placeholder?: string
        resource?: string
        options?: { label: string; value: string }[]
    }

    - Dans 'ServerDataTable.tsx'
        if(filter.type === 'remote_select' && filter.resource) {
            return (
                <RemoteComboboxFilter
                    key={filter.name}
                    filter={filter}
                    queryParams={queryParams}
                    navigate={navigate}
                />
            )
        }

    function RemoteComboboxFilter({ filter, queryParams, navigate }: {
        filter: ServerTableFilter
        queryParams: Record<string, string>
        navigate: (o: Record<string, string | null>) => void
    }) {
        const currentValue = queryParams[filter.name] ?? ''
        const [label, setLabel] = useState(queryParams[`${filter.name}_label`] ?? '')
        return (
            <RemoteCombobox
                resource={filter.resource!}
                value={currentValue}
                label={label}
                placeholder={filter.label}
                onChange={(val, lbl) => {
                    setLabel(lbl)
                    navigate({ [filter.name]: val || null, page: '1' })
                }}
            />
        )
    }

    - Dans les ..Table.tsx
        {
            type: 'remote_select',
            name: 'fournisseur',
            label: 'Fournisseur',
            resource: 'fournisseurs',
        }
*/