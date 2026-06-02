import { Tarif } from "./tarif.model"

export interface Trajet {
    id: number
    codetrajet: string
    provenance: string
    destination: string
    tarif: Tarif,
    voyagesCount: number
}