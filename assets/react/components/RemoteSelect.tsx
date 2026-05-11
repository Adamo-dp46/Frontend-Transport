import { useState } from "react"
import { Check, ChevronsUpDown, X, Loader2 } from "lucide-react"
import { Button } from "../../components/ui/button"
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "../../components/ui/popover"
import {
    Command,
    CommandEmpty,
    CommandGroup,
    CommandInput,
    CommandItem,
    CommandList,
} from "../../components/ui/command"
import { cn } from "../../lib/utils"
import { RemoteOption, useRemoteSelect } from "../hooks/useRemoteSelect"

interface RemoteSelectProps {
    /** Slug de la ressource : fournisseurs, pieces, cars, etc. */
    resource: string
    /** name du champ hidden soumis dans le formulaire */
    name: string
    placeholder?: string
    /** Valeur pré-sélectionnée (mode édition) */
    initialValue?: RemoteOption | null
    /** Callback quand la sélection change */
    onChange?: (option: RemoteOption | null) => void
    disabled?: boolean
    required?: boolean
}

export function RemoteSelect({
    resource,
    name,
    placeholder = "Rechercher...",
    initialValue = null,
    onChange,
    disabled = false,
    required = false,
}: RemoteSelectProps) {
    const [open, setOpen] = useState(false)

    const {
        query, setQuery,
        options,
        selected, handleSelect, handleClear,
        loading, error,
    } = useRemoteSelect({ resource, initialValue })

    const onSelect = (option: RemoteOption) => {
        handleSelect(option)
        setOpen(false)
        onChange?.(option)
    }

    const onClear = (e: React.MouseEvent) => {
        e.stopPropagation()
        handleClear()
        onChange?.(null)
    }

    return (
        <div className="relative">
            {/* Champ hidden soumis avec le formulaire */}
            <input
                type="hidden"
                name={name}
                value={selected?.id ?? ""}
                required={required}
            />

            <Popover open={open} onOpenChange={setOpen}>
                <PopoverTrigger asChild>
                    <Button
                        variant="outline"
                        role="combobox"
                        aria-expanded={open}
                        disabled={disabled}
                        className="w-full justify-between font-normal h-9 px-3"
                    >
                        <span className={cn("truncate", !selected && "text-muted-foreground")}>
                            {selected ? selected.label : placeholder}
                        </span>
                        <div className="flex items-center gap-1 ml-2 shrink-0">
                            {selected && (
                                <span
                                    onClick={onClear}
                                    className="hover:text-foreground text-muted-foreground cursor-pointer"
                                >
                                    <X className="h-3.5 w-3.5" />
                                </span>
                            )}
                            <ChevronsUpDown className="h-3.5 w-3.5 text-muted-foreground" />
                        </div>
                    </Button>
                </PopoverTrigger>

                <PopoverContent className="w-full p-0" align="start">
                    <Command shouldFilter={false}>
                        <CommandInput
                            placeholder={placeholder}
                            value={query}
                            onValueChange={setQuery}
                        />
                        <CommandList>
                            {loading && (
                                <div className="flex items-center justify-center py-4 gap-2 text-xs text-muted-foreground">
                                    <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                    Chargement...
                                </div>
                            )}
                            {error && (
                                <div className="py-3 text-center text-xs text-red-500">{error}</div>
                            )}
                            {!loading && !error && options.length === 0 && (
                                <CommandEmpty>Aucun résultat</CommandEmpty>
                            )}
                            {!loading && !error && options.length > 0 && (
                                <CommandGroup>
                                    {options.map((opt) => (
                                        <CommandItem
                                            key={opt.id}
                                            value={String(opt.id)}
                                            onSelect={() => onSelect(opt)}
                                            className="cursor-pointer"
                                        >
                                            <Check
                                                className={cn(
                                                    "mr-2 h-4 w-4 shrink-0",
                                                    selected?.id === opt.id ? "opacity-100" : "opacity-0"
                                                )}
                                            />
                                            {opt.label}
                                        </CommandItem>
                                    ))}
                                </CommandGroup>
                            )}
                        </CommandList>
                    </Command>
                </PopoverContent>
            </Popover>
        </div>
    )
}
