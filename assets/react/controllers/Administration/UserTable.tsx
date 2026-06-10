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
import { ServerMeta, ServerTableFilter, useServerTable } from "../../hooks/useServerTable"
import { ServerDataTableColumnHeader } from "../../components/server/server-data-table-column-header"
import { ServerDataTable } from "../../components/server/server-data-table"
import { Badge } from "../../../components/ui/badge"
import { User } from "../../models/user.model"

type Props = {
    users: User[]
    meta: ServerMeta
    queryParams: Record<string, string>
    canEdit: boolean
    currentUserId: number // Pour empêcher l'auto suspension
    csrfDelete: string
    apiUrl: string
    isSuperAdmin: boolean
    // currentUserRoles: string[]
    canPromouvoirAdminGare: boolean // true si ROLE_ADMIN
    csrfPromouvoirAdminGare: string
    currentUserIsFounder?: boolean
}

function buildColumns(
    getSortToggleUrl: (f: string) => string,
    getSortExplicitUrl: (f: string, dir: 'asc' | 'desc') => string,
    getSortState: (f: string) => 'asc' | 'desc' | false,
    canEdit: boolean,
    currentUserId: number,
    csrfDelete: string,
    apiUrl: string,
    isSuperAdmin: boolean,
    // currentUserRoles: string[],
    canPromouvoirAdminGare: boolean,
    csrfPromouvoirAdminGare: string,
    currentUserIsFounder?: boolean
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
            id: 'role',
            header: 'Rôle',
            cell: ({ row }) => {
                const isAdmin = row.original.roles.includes('ROLE_ADMIN')
                const isFounder = row.original.isFounder
                const isAdminGare = row.original.roles.includes('ROLE_ADMIN_GARE')
                if(isFounder) {
                    return <Badge className="bg-yellow-50 text-yellow-700 dark:bg-yellow-950 dark:text-yellow-300">
                        👑 Fondateur
                    </Badge>
                }
                if(isAdminGare) {
                    return <Badge className="bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-300">Admin de gare</Badge>
                }
                return isAdmin
                    ? <Badge className="bg-purple-50 text-purple-700 dark:bg-purple-950 dark:text-purple-300">Admin</Badge>
                    : <Badge className="bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300">Utilisateur</Badge>
            }
        },
        {
            id: 'gare',
            header: 'Gare',
            cell: ({ row }) => {
                const gare = row.original.gare
                if(!gare) {
                    return <span className="text-muted-foreground text-xs">Aucune</span>
                }
                return <span className="text-sm">{gare.libelle}{gare.ville ? ` - ${gare.ville}` : ''}</span>
            }
        },
        {
            id: "fileUrl",
            header: "",
            cell: ({ row }) => {
                const user = row.original
                const initiales = `${user.prenom.charAt(0)}${user.nom.charAt(0)}`.toUpperCase()
                return (
                    <div className="flex items-center justify-center">
                        {user.fileUrl ? (
                            <img
                                src={`${apiUrl}/media${user.fileUrl}?w=400&h=400&fm=jpg&fit=crop`}
                                alt={`${user.prenom} ${user.nom}`}
                                className="h-8 w-8 rounded-full object-cover shrink-0"
                            />
                        ) : (
                            <div className="h-8 w-8 rounded-full bg-primary/10 text-primary flex items-center justify-center text-xs font-semibold">
                                {initiales}
                            </div>
                        )}
                    </div>
                )
            }
        },
        {
            id: "actions",
            cell: ({ row }) => {
                const user = row.original
                const isAdmin = user.roles.includes('ROLE_ADMIN')
                const isSelf = user.id === currentUserId
                const suspendable = !isSelf && (isSuperAdmin ? true : (canEdit && !isAdmin)) /*
                    - Plus 'const suspendable = canEdit && !isAdmin && !isSelf' pour inclure le super admin, l'admin normal peut suspendre tous sauf un admin et le super admin peut suspendre tous sauf lui même
                */
                const editable = canEdit && (isSuperAdmin || !isAdmin)
                const promouvable = currentUserIsFounder && !isSelf && !user.isFounder
                const isAdminGare  = user.roles.includes('ROLE_ADMIN_GARE')
                const peutAdminGare = canPromouvoirAdminGare && !isSelf && !user.isFounder && !isAdmin

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

                            {editable && !isAdmin && <DropdownMenuSeparator />}
                            {editable && !isAdmin && (
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

                            {peutAdminGare && <DropdownMenuSeparator />}
                            {peutAdminGare && (
                                <DropdownMenuItem asChild>
                                    <form method="POST" action={`/admin/utilisateurs/${user.id}/promouvoir/gare`}
                                        onSubmit={(e) => {
                                            const action = isAdminGare ? 'révoquer comme administrateur de gare' : 'nommer administrateur de gare'
                                            if(!confirm(`Voulez-vous ${action} cet utilisateur ?`)) {
                                                e.preventDefault()
                                            }
                                        }}>
                                        <input type="hidden" name="_token" value={csrfPromouvoirAdminGare} />
                                        <button type="submit" className={`w-full text-left ${isAdminGare ? 'text-orange-600' : 'text-green-600'}`}>
                                            {isAdminGare ? 'Révoquer admin de gare' : 'Nommer admin de gare'}
                                        </button>
                                    </form>
                                </DropdownMenuItem>
                            )}

                            {promouvable && <DropdownMenuSeparator />}
                            {promouvable && (
                                <DropdownMenuItem asChild>
                                    <form
                                        method="POST"
                                        action={`/admin/utilisateurs/${user.id}/promouvoir`}
                                        onSubmit={(e) => {
                                            const isAdmin = user.roles.includes('ROLE_ADMIN')
                                            const action = isAdmin ? 'rétrograder en utilisateur standard' : 'promouvoir en administrateur'
                                            if(!confirm(`Voulez-vous ${action} cet utilisateur ?`)) {
                                                e.preventDefault()
                                            }
                                        }}
                                    >
                                        <button
                                            type="submit"
                                            className={`w-full text-left ${user.roles.includes('ROLE_ADMIN')
                                                ? 'text-muted-foreground focus:text-foreground'
                                                : 'text-blue-600 focus:text-blue-700'
                                            }`}
                                        >
                                            {user.roles.includes('ROLE_ADMIN')
                                                ? 'Rétrograder en utilisateur'
                                                : 'Promouvoir en administrateur'
                                            }
                                        </button>
                                    </form>
                                </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                    </DropdownMenu>
                )
            }
        }
    ]
}

export default function UserTable({
    users,
    meta,
    queryParams,
    canEdit,
    currentUserId,
    csrfDelete,
    apiUrl,
    isSuperAdmin,
    currentUserIsFounder,
    canPromouvoirAdminGare,
    csrfPromouvoirAdminGare
}: Props)
{
    const { getSortState, getSortToggleUrl, getSortExplicitUrl } = useServerTable(queryParams)
    const columns = useMemo(
        () => buildColumns(getSortToggleUrl, getSortExplicitUrl, getSortState, canEdit, currentUserId, csrfDelete, apiUrl, isSuperAdmin, canPromouvoirAdminGare, csrfPromouvoirAdminGare, currentUserIsFounder),
        [queryParams, canEdit, currentUserId, csrfDelete, apiUrl, isSuperAdmin, canPromouvoirAdminGare, csrfPromouvoirAdminGare, currentUserIsFounder]
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