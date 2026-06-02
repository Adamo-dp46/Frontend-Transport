import { Gare } from "./gare.model"
import { Voyage } from "./voyage.model"

interface Detailcourrier {
    id: number
    nature: string
    valeur: number
    montant: number
    designation?: string
    emballage?: string
    type?: string
    poids?: number
}

export interface Courrier {
    id: number
    codecourrier: string
    nomexpediteur: string
    nomdestinataire: string
    contactexpediteur: string
    contactdestinataire: string
    garedepart: Gare
    garearrivee: Gare
    voyage: Voyage | null
    montant: number
    fraissuivi: number | null
    statut: string
    detailcourriers: Detailcourrier[]
    modepaiement: string
    etatpaiement: string
    createdAt: string
}
