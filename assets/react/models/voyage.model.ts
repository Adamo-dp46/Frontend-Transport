import { Car } from "./car.model"
import { Detailpersonnel } from "./detailpersonnel.model"

interface Ref {
    id: number
    motif: string
}

interface LigneRef {
    id: number
    codeligne: string
    libelle: string | null
    gareorigine?: { id: number; libelle: string }
    gareterminus?: { id: number; libelle: string }
}

export interface Voyage {
    id: number
    codevoyage: string
    provenance: string
    destination: string
    datedebut: string
    datefin: string | null
    ligne: LigneRef
    car: Car | null
    detailpersonnels: Detailpersonnel[]
    courriers: Ref[]
    bagages: Ref[]
    placestotal: number,
    ticketsCount: number // nb de billets actifs vendus (compté à la volée côté API ; remplace 'placesoccupees')
    createdAt: string
}
