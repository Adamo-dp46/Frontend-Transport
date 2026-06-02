import { Siege } from "./siege.model"
import { Voyage } from "./voyage.model"

export interface Ticket {
    id: number
    siege: Siege
    nomclient?: string
    contactclient?: string
    prix: number
    codeticket: string
    voyage: Voyage
    createdAt: string
}