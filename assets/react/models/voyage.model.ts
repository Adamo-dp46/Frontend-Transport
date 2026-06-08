import { Car } from "./car.model"
import { Detailpersonnel } from "./detailpersonnel.model"
import { Trajet } from "./trajet.model"

interface Ref {
    id: number
    motif: string
}

export interface Voyage {
    id: number
    codevoyage: string
    provenance: string
    destination: string
    datedebut: string
    datefin: string | null
    trajet: Trajet
    car: Car | null
    detailpersonnels: Detailpersonnel[]
    courriers: Ref[]
    bagages: Ref[]
    placestotal: number,
    placesoccupees: number
    createdAt: string
}
