import { ColumnDef } from "@tanstack/react-table"
import { MoreHorizontal } from "lucide-react"
import { DataTable } from "../../components/data-table"
import { Button } from "../../../components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "../../../components/ui/dropdown-menu"
import { DataTableColumnHeader } from "../../components/data-table-column-header"
import { useMemo } from "react"
import { formatDate } from "../../../lib/functions"

interface Fournisseur {
    id: number
    libelle: string
    nom: string
    contact: string
    email?: string
    adresse?: string
    pays?: string
    createdAt: string
}

type Props = {
    fournisseurs: Fournisseur[],
    canEdit: boolean,
    canDelete: boolean,
    csrfDelete: string
}

function buildColumns(canEdit: boolean, canDelete: boolean, csrfDelete: string): ColumnDef<Fournisseur>[] {
    return [
        {
            accessorKey: "id",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Id" />
            )
        },
        {
            accessorKey: "libelle",
            header: 'Libellé'
        },
        {
            accessorKey: "nom",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Nom" />
            )
        },
        {
            accessorKey: "contact",
            header: 'Contact'
        },
        {
            accessorKey: "email",
            header: 'Email'
        },
        {
            accessorKey: "adresse",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Adresse" />
            )
        },
        {
            accessorKey: "pays",
            header: 'Pays'
        },
        {
            accessorKey: "createdAt",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Date de création" />
            ),
            cell: ({ row }) => (
                <span>
                    {formatDate(row.original.createdAt)}
                </span>
            )
        },
        {
            id: "actions",
            cell: ({ row }) => {
                const fournisseur = row.original
                return (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Ouvrir menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                                <a href={`/fournisseur/${fournisseur.id}`}>Voir</a>
                            </DropdownMenuItem>

                            {canEdit && <DropdownMenuSeparator />}

                            {canEdit && (
                                <DropdownMenuItem asChild>
                                    <a href={`/fournisseur/${fournisseur.id}/modifier`}>Modifier</a>
                                </DropdownMenuItem>
                            )}

                            {canEdit && canDelete && <DropdownMenuSeparator />}

                            {canDelete && (
                                <DropdownMenuItem asChild>
                                    <form
                                        method="POST"
                                        action={`/fournisseur/${fournisseur.id}/supprimer`}
                                        onSubmit={(e) => {
                                            if(!confirm("Supprimer ce fournisseur ?")) {
                                                e.preventDefault()
                                            }
                                        }}
                                    >
                                        <input type="hidden" name="_token" value={csrfDelete} />
                                        <button
                                            type="submit"
                                            className="w-full text-left text-red-600 focus:text-red-700"
                                        >
                                            Supprimer
                                        </button>
                                    </form>
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )
            },
        },
    ]
}

export default function CarTable({fournisseurs, canEdit, canDelete, csrfDelete}: Props) {
    const columns = useMemo(
        () => buildColumns(canEdit, canDelete, csrfDelete),
        [canEdit, canDelete, csrfDelete]
    )

    return <>
        <div className="space-y-2">
            <DataTable
                columns={columns}
                data={fournisseurs}
                filterColumn="nom"
                filterPlaceholder="Filtrer par nom.."
            />
        </div>
    </>
}