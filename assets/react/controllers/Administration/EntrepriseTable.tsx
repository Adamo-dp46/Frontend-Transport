import { ColumnDef } from "@tanstack/react-table"
import { ArrowUpDown, FileText, MoreHorizontal, Sheet } from "lucide-react"
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

interface Entreprise {
    id: number
    libelle: string
    contact1: string
    contact2?: string
    adresse?: string
    email?: string
    sigle?: string
    siteweb?: string
    rccm?: string
    banque?: string
    type?: string
    centreimpot?: string
    tauxtva?: string
    createdAt: string
}

type Props = {
    entreprises: Entreprise[]
}

function buildColumns(): ColumnDef<Entreprise>[] {
    return [
        {
            accessorKey: "id",
            header: ({ column }) => (
                <DataTableColumnHeader column={column} title="Id" />
            )
        },
        {
            accessorKey: "libelle",
            header: "Libellé"
        },
        {
            accessorKey: "contact1",
            header: "Contact 1"
        },
        {
            accessorKey: "contact2",
            header: "Contact 2"
        },
        {
            accessorKey: "adresse",
            header: "Adresse"
        },
        {
            accessorKey: "email",
            header: "Email"
        },
        {
            accessorKey: "sigle",
            header: "Sigle"
        },
        /*
            {
                id: "logo",
                header: "",
                cell: ({ row }) => {
                    const e = row.original
                    return (
                        <div className="flex items-center justify-center">
                            {e.image ? (
                                <img
                                    src={e.image}
                                    alt={e.sigle}
                                    className="h-8 w-8 rounded object-contain"
                                />
                            ) : (
                                <div className="h-8 w-8 rounded bg-primary/10 text-primary flex items-center justify-center text-xs font-bold">
                                    {e.sigle.charAt(0).toUpperCase()}
                                </div>
                            )}
                        </div>
                    )
                },
            }
        */
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
                const entreprise = row.original
                return (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button variant="ghost" className="h-8 w-8 p-0">
                                <span className="sr-only">Open menu</span>
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                             <DropdownMenuItem asChild>
                                <a href={`/admin/entreprises/${entreprise.id}`}>Voir</a>
                            </DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )
            },
        },
    ]
}

export default function EntrepriseTable({entreprises}: Props) {
    const columns = useMemo(
        () => buildColumns(),
        []
    )

    return <>
        <div className="space-y-2">
            <DataTable
                columns={columns}
                data={entreprises}
                filterColumn="libelle"
                filterPlaceholder="Filtrer par libellé..."
            />
        </div>
    </>
}