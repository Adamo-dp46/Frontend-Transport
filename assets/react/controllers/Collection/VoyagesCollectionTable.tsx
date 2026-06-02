import { ColumnDef } from "@tanstack/react-table"
import { ServerMeta, useServerTable } from "../../hooks/useServerTable"
import { ServerDataTableColumnHeader } from "../../components/server/server-data-table-column-header"
import { formatDate } from "../../../lib/functions"
import { useMemo } from "react"
import { ServerDataTable } from "../../components/server/server-data-table"
import { Badge } from "../../../components/ui/badge"
import { Voyage } from "../../models/voyage.model"

type Props = {
    voyages: Voyage[]
    meta: ServerMeta
    queryParams: Record<string, string>
    urlPrefix: string /*
        - Le préfixe url pour isoler la table des autres sur la même page
    */
}

function buildColumns(
    getSortToggleUrl: (f: string) => string,
    getSortExplicitUrl: (f: string, dir: 'asc' | 'desc') => string,
    getSortState: (f: string) => 'asc' | 'desc' | false,
): ColumnDef<Voyage>[] {

    const sortUrls = (field: string) => ({
        toggle: getSortToggleUrl(field),
        asc: getSortExplicitUrl(field, 'asc'),
        desc: getSortExplicitUrl(field, 'desc'),
    })

    return [
        {
            accessorKey: 'id',
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Id" sortUrls={sortUrls('id')} sortState={getSortState('id')} />
            )
        },
        {
            accessorKey: 'codevoyage',
            header: 'Code voyage',
        },
        {
            accessorKey: 'provenance',
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Provenance" sortUrls={sortUrls('provenance')} sortState={getSortState('provenance')} />
            )
        },
        {
            accessorKey: 'destination',
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Destination" sortUrls={sortUrls('destination')} sortState={getSortState('destination')} />
            )
        },
        {
            accessorKey: 'datedebut',
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Date de départ" sortUrls={sortUrls('datedebut')} sortState={getSortState('datedebut')} />
            ),
            cell: ({ row }) => (
                <span className="tabular-nums font-medium">
                    {formatDate(row.original.datedebut)}
                </span>
            ),
        },
        {
            accessorKey: 'datefin',
            header: "Date d'arrivée",
            cell: ({ row }) => row.original.datefin
                ? <span className="tabular-nums">{formatDate(row.original.datefin)}</span>
                : <span className="text-muted-foreground">Non clôturé</span>
        },
        {
            accessorFn: (row) => row.trajet?.codetrajet ?? "",
            id: "trajet",
            header: "Trajet",
            cell: ({ row }) =>
                row.original.trajet
                ? <Badge className="bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">{row.original.trajet.codetrajet}</Badge>
                : <span className="text-muted-foreground">Aucun</span>
        },
        {
            accessorKey: "placestotal",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Places total" sortUrls={sortUrls('placestotal')} sortState={getSortState('placestotal')} />
            ),
        },
        {
            accessorKey: "placesoccupees",
            header: "Place ocuupées"
        },
        {
            id: "nbcourriers",
            header: "Nbre de courriers",
            cell: ({ row }) => {
                return <span className="tabular-nums font-medium">{row.original.courriers.length}</span>
            }
        },
        {
            id: "nbbagages",
            header: "Nbre de bagages",
            cell: ({ row }) => {
                return <span className="tabular-nums font-medium">{row.original.bagages.length}</span>
            }
        },
        {
            id: "nbpersonnels",
            header: "Personnels affectés",
            cell: ({ row }) => {
                return <span className="tabular-nums font-medium">{row.original.detailpersonnels.length}</span>
            }
        },
        {
            accessorKey: "createdAt",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Date de création"
                    sortUrls={sortUrls('createdAt')} sortState={getSortState('createdAt')} />
            ),
            cell: ({ row }) => (
                <span>
                    {formatDate(row.original.createdAt)}
                </span>
            )
        },
        {
            id: 'actions',
            header: '',
            cell: ({ row }) => (
                <a href={`/voyage/${row.original.id}`} className="text-xs text-primary hover:underline">Voir</a>
            )
        }
    ]
}

export default function VoyagesCollectionTable({
    voyages,
    meta,
    queryParams,
    urlPrefix
}: Props)
{
    const { getSortState, getSortToggleUrl, getSortExplicitUrl } = useServerTable(queryParams, urlPrefix) /*
        - Le 'urlPrefix' va donner 'v[sort], v[page]..'
    */
    const columns = useMemo(
        () => buildColumns(getSortToggleUrl, getSortExplicitUrl, getSortState),
        [queryParams]
    )

    return (
        <ServerDataTable
            columns={columns}
            data={voyages}
            meta={meta}
            queryParams={queryParams}
            urlPrefix={urlPrefix}
        /> /*
            - On.. de 'filters[]' utilisateur ici vu que c'est une table liée 
        */
    )
}