import { ColumnDef } from "@tanstack/react-table"
import { MoreHorizontal } from "lucide-react"
import { Button } from "../../../components/ui/button"
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from "../../../components/ui/dropdown-menu"
import { useMemo } from "react"
import { formatDate } from "../../../lib/functions"
import { Badge } from "../../../components/ui/badge"
import { ServerMeta, ServerTableFilter, useServerTable } from "../../hooks/useServerTable"
import { ServerDataTableColumnHeader } from "../../components/server/server-data-table-column-header"
import { ServerDataTable } from "../../components/server/server-data-table"
import { Approvisionnement } from "../../models/approvisionnement.model"
import { Fournisseur } from "../../models/fournisseur.model"

type Props = {
    approvisionnements: Approvisionnement[],
    meta: ServerMeta
    queryParams: Record<string, string>
    fournisseurs: Fournisseur[]
    canEdit: boolean,
    canDelete: boolean,
    csrfDelete: string
}

function buildColumns(
    getSortToggleUrl: (f: string) => string,
    getSortExplicitUrl: (f: string, dir: 'asc' | 'desc') => string,
    getSortState: (f: string) => 'asc' | 'desc' | false,
    canEdit: boolean,
    canDelete: boolean,
    csrfDelete: string
): ColumnDef<Approvisionnement>[]{

    const sortUrls = (field: string) => ({
        toggle: getSortToggleUrl(field),
        asc: getSortExplicitUrl(field, 'asc'),
        desc: getSortExplicitUrl(field, 'desc')
    })

    return [
        {
            accessorKey: "id",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Id" sortUrls={sortUrls('id')} sortState={getSortState('id')} />
            )
        },
        {
            accessorKey: "dateappro",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Date d'approvisionnement" sortUrls={sortUrls('dateappro')} sortState={getSortState('dateappro')} />
            ),
            cell: ({ row }) => (
                <span className="font-medium tabular-nums">
                    {formatDate(row.original.dateappro)}
                </span>
            )
        },
        {
            id: "fournisseur",
            header: "Fournisseur",
            accessorFn: (row) => row.fournisseur?.nom ?? "",
            cell: ({ row }) =>
                row.original.fournisseur
                ? <Badge className="bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">{row.original.fournisseur.nom}</Badge>
                : <span className="text-muted-foreground">Aucun</span>
        },
        {
            id: "nbpieces",
            header: "Nbre de pièces",
            cell: ({ row }) => {
                const total = row.original.detailapprovisionnements.reduce((sum, d) => sum + d.quantite, 0)
                return <span className="tabular-nums font-medium">{total}</span>
            },
        },
        {
            id: "couttotal",
            header: "Coût total",
            cell: ({ row }) => {
                const total = row.original.detailapprovisionnements.reduce((sum, d) => sum + (d.quantite * d.prixunitaire), 0)
                return <span className="tabular-nums font-semibold">{total.toLocaleString("fr-FR")} FCFA</span>
            },
        },
        {
            accessorKey: "createdAt",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Date de création" sortUrls={sortUrls('createdAt')} sortState={getSortState('createdAt')} />
            ),
            cell: ({ row }) => (
                <span className="font-medium tabular-nums">
                    {formatDate(row.original.createdAt)}
                </span>
            )
        },
        {
            id: "actions",
            cell: ({ row }) => {
                const approvisionnement = row.original
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
                                <a href={`/approvisionnement/${approvisionnement.id}`}>Voir</a>
                            </DropdownMenuItem>

                            {canEdit && <DropdownMenuSeparator />}

                             {canEdit && (
                                <DropdownMenuItem asChild>
                                    <a href={`/approvisionnement/${approvisionnement.id}/modifier`}>Modifier</a>
                                </DropdownMenuItem>
                            )}

                            {canDelete && <DropdownMenuSeparator />}

                            {canDelete && (
                                <DropdownMenuItem asChild>
                                    <form
                                        method="POST"
                                        action={`/approvisionnement/${approvisionnement.id}/supprimer`}
                                        onSubmit={(e) => {
                                            if(!confirm("Supprimer cet approvisionnement ?")) {
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
        }
    ]
}

export default function ApprovisionnementTable({
    approvisionnements,
    meta,
    queryParams,
    fournisseurs,
    canEdit,
    canDelete,
    csrfDelete
}: Props) {

    const { getSortState, getSortToggleUrl, getSortExplicitUrl } = useServerTable(queryParams)
    const columns = useMemo(
        () => buildColumns(getSortToggleUrl, getSortExplicitUrl, getSortState, canEdit, canDelete, csrfDelete),
        [queryParams, canEdit, canDelete, csrfDelete]
    )
    const filters: ServerTableFilter[] = useMemo(() => [
        {
            type: 'select',
            name: 'fournisseur',
            label: 'Fournisseur',
            options: fournisseurs.map((f) => ({ value: `${f.id}`, label: f.nom })),
        },
        {
            type: 'date_range',
            name: 'date',
            label: 'Période',
        },
    ], [fournisseurs])

    return (
        <ServerDataTable
            columns={columns}
            data={approvisionnements}
            meta={meta}
            queryParams={queryParams}
            filters={filters}
        />
    )
}