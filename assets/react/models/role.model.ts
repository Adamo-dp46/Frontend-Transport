import { Permission } from "./permission.model"

export interface Role {
    id: number
    name: string
    description?: string
    typerole: string
    permissions: Permission[]
}