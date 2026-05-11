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
import { ServerMeta, ServerTableFilter, useServerTable } from "../../hooks/useServerTable"
import { ServerDataTableColumnHeader } from "../../components/server/server-data-table-column-header"
import { ServerDataTable } from "../../components/server/server-data-table"
import { Badge } from "../../../components/ui/badge"

interface User {
    id: number
    email: string
    nom: string
    prenom: string
    avatar?: string
    statut: string
    roles: string[] // Pour détecter l'admin
}

type Props = {
    users: User[]
    meta: ServerMeta
    queryParams: Record<string, string>
    canEdit: boolean
    canDelete: boolean
    currentUserId: number // Pour empêcher l'auto suspension
    csrfDelete: string
}

function buildColumns(
    getSortToggleUrl: (f: string) => string,
    getSortExplicitUrl: (f: string, dir: 'asc' | 'desc') => string,
    getSortState: (f: string) => 'asc' | 'desc' | false,
    canEdit: boolean,
    canDelete: boolean,
    currentUserId: number,
    csrfDelete: string
): ColumnDef<User>[] {

    const sortUrls = (field: string) => ({
        toggle: getSortToggleUrl(field),
        asc: getSortExplicitUrl(field, 'asc'),
        desc: getSortExplicitUrl(field, 'desc'),
    })

    return [
        {
            accessorKey: "id",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Id" sortUrls={sortUrls('id')} sortState={getSortState('id')} />
            )
        },
        {
            accessorKey: "email",
            header: "Email"
        },
        {
            accessorKey: "nom",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Nom" sortUrls={sortUrls('nom')} sortState={getSortState('nom')} />
            )
        },
        {
            accessorKey: "prenom",
            header: ({ column }) => (
                <ServerDataTableColumnHeader column={column} title="Prenom" sortUrls={sortUrls('prenom')} sortState={getSortState('prenom')} />
            )
        },
        {
            accessorKey: "statut",
            header: "Statut",
            cell: ({ row }) => {
                const statut = row.original.statut
                const cls = statut === 'ACTIF' ? 'bg-green-50 text-green-700 dark:bg-green-950 dark:text-green-300' : 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-300'
                return (
                    <Badge className={cls}>
                        {statut === 'ACTIF' ? 'Actif' : 'Suspendu'}
                    </Badge>
                )
            }
        },
        {
            id: "avatar",
            header: "",
            cell: ({ row }) => {
                const user = row.original
                const initiales = `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase()
                return (
                    <div className="flex items-center justify-center">
                        {user.avatar ? (
                            <img
                                src={user.avatar}
                                alt={`${user.prenom} ${user.nom}`}
                                className="h-8 w-8 rounded-full object-cover"
                            />
                        ) : (
                            <div className="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold">
                                {initiales}
                            </div>
                        )}
                    </div>
                )
            },
        },
        {
            id: "actions",
            cell: ({ row }) => {
                const user = row.original
                const isAdmin = user.roles.includes('ROLE_ADMIN')
                const isSelf = user.id === currentUserId
                const suspendable = canEdit && !isAdmin && !isSelf

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
                                <a href={`/admin/utilisateurs/${user.id}`}>Voir</a>
                            </DropdownMenuItem>

                            {canEdit && !isAdmin && <DropdownMenuSeparator />}
                            {canEdit && !isAdmin && (
                                <DropdownMenuItem asChild>
                                    <a href={`/admin/utilisateurs/${user.id}/modifier`}>Modifier</a>
                                </DropdownMenuItem>
                            )}

                            {suspendable && <DropdownMenuSeparator />}
                            {suspendable && (
                                <DropdownMenuItem asChild>
                                    <form
                                        method="POST"
                                        action={`/admin/utilisateurs/${user.id}/suspendre`}
                                        onSubmit={(e) => {
                                            const action = user.statut === 'ACTIF' ? 'suspendre' : 'réactiver'
                                            if (!confirm(`Voulez-vous ${action} cet utilisateur ?`)) {
                                                e.preventDefault()
                                            }
                                        }}
                                    >
                                        <input type="hidden" name="_token" value={csrfDelete} />
                                        <button
                                            type="submit"
                                            className={`w-full text-left ${user.statut === 'ACTIF' ? 'text-orange-600 focus:text-orange-700' : 'text-green-600 focus:text-green-700'}`}
                                        >
                                            {user.statut === 'ACTIF' ? 'Suspendre' : 'Réactiver'}
                                        </button>
                                    </form>
                                </DropdownMenuItem>
                            )}

                            {/*
                                {canEdit && canDelete && <DropdownMenuSeparator />}
                                {canDelete && (
                                    <DropdownMenuItem asChild>
                                        <form
                                            method="POST"
                                            action={`/admin/utilisateurs/${user.id}/supprimer`}
                                            onSubmit={(e) => {
                                                if(!confirm("Supprimer cet utilisateur ?")) {
                                                    e.preventDefault()
                                                }
                                            }}
                                        >
                                            <button
                                                type="submit"
                                                className="w-full text-left text-red-600 focus:text-red-700"
                                            >
                                                Supprimer
                                            </button>
                                        </form>
                                    </DropdownMenuItem>
                                )}
                            */}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )
            },
        },
    ]
}

export default function UserTable({users, meta, queryParams, canEdit, canDelete, currentUserId, csrfDelete}: Props) {

    const { getSortState, getSortToggleUrl, getSortExplicitUrl } = useServerTable(queryParams)

    const columns = useMemo(
        () => buildColumns(getSortToggleUrl, getSortExplicitUrl, getSortState, canEdit, canDelete, currentUserId, csrfDelete),
        [queryParams, canEdit, canDelete, currentUserId, csrfDelete]
    )

    const filters: ServerTableFilter[] = useMemo(() => [
        {
            type: 'text',
            name: 'search',
            label: 'Nom',
            placeholder: 'Filtrer par nom..',
        },
        {
            type: 'text',
            name: 'email',
            label: 'Email',
            placeholder: 'Filtrer par email..'
        },
        {
            type: 'select',
            name: 'statut',
            label: 'Statut',
            options: [
                { value: 'ACTIF',    label: 'Actif' },
                { value: 'SUSPENDU', label: 'Suspendu' }
            ]
        }
    ], [])

    return (
        <ServerDataTable
            columns={columns}
            data={users}
            meta={meta}
            queryParams={queryParams}
            filters={filters}
        />
    )
}