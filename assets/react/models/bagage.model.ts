import { Gare } from "./gare.model"
import { Voyage } from "./voyage.model"

export interface Bagage {
    id: number
    codebagage: string
    nomclient: string
    contactclient: string
    nature: string
    type?: string
    poids: string
    montant: number
    montantforce: boolean
    statut: string
    voyage?: Voyage
    garedepart?: Gare
    garedescente?: Gare
    createdAt: string
}