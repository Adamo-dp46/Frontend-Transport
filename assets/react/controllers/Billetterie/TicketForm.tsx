import { useState, useEffect, useCallback } from "react";
import { cn } from "../../../lib/utils";
import { Badge } from "../../../components/ui/badge";
import { Button } from "../../../components/ui/button";
import { Input } from "../../../components/ui/input";
import { Label } from "../../../components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "../../../components/ui/select";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
    CardDescription,
} from "../../../components/ui/card";
import { Separator } from "../../../components/ui/separator";
import {
    AlertCircle,
    CheckCircle2,
    Loader2,
    Users,
    MapPin,
    Bus,
    Plus,
    Printer,
    Eye,
} from "lucide-react";
import { flash } from "../../../elements/Alert"
import { visit } from "@hotwired/turbo"
import { Tabs, TabsList, TabsTrigger } from "../../../components/ui/tabs"
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "../../../components/ui/dialog"
import { Zap } from "lucide-react"


// ─── Types ────────────────────────────────────────────────────────────────────


interface CreatedTicket { // Pour le post création sans redirection
    id: number
    codeticket: string
    siege: { numero: number }
    nomclient?: string | null
    prix: number
}


interface Voyage {
    "@id": string;
    id: number;
    provenance: string;
    destination: string;
    codevoyage: string;
    placestotal: number;
    placesoccupees: number;
    car?: {
        id: number;
        matricule: string;
        nbrsiege: number;
        siegesGauche?: number;
        siegesDroite?: number;
    }

    datefin?: string | null;
}

interface Siege {
    "@id": string;
    id: number;
    numero: number;
    rangee: number;
    colonne: number;
    cote: "GAUCHE" | "DROITE";
    statut: "LIBRE" | "OCCUPE";
}

interface SiegesResponse {
    siegesGauche: number;
    siegesDroite: number;
    sieges: Siege[];
}

interface ClientInfo {
    nomclient: string;
    contactclient: string;
}

// ─── Props ────────────────────────────────────────────────────────────────────

interface Gare {
    "@id": string
    id: number
    libelle: string
    ville: string
}

interface TicketFormProps {
    voyages: Voyage[];
    gares: Gare[]
    preselectVoyageId?: number;
}

// ─── Constantes ───────────────────────────────────────────────────────────────

const SIEGE_W = 44;
const SIEGE_H = 44;
const GAP = 6;
const AISLE = 28;

// ─── Siège individuel ─────────────────────────────────────────────────────────

function SiegeCell({
    siege,
    selected,
    onClick,
}: {
    siege: Siege;
    selected: boolean;
    onClick: () => void;
}) {
    const isOccupe = siege.statut === "OCCUPE";

    return (
        <button
            type="button"
            disabled={isOccupe}
            onClick={onClick}
            title={`Siège ${siege.numero} — ${siege.statut}`}
            className={cn(
                "relative flex items-center justify-center rounded-md border-2 text-xs font-bold transition-all duration-150 select-none",
                "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1",
                isOccupe &&
                    "cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400",
                !isOccupe &&
                    !selected &&
                    "cursor-pointer border-emerald-300 bg-emerald-50 text-emerald-700 hover:border-emerald-500 hover:bg-emerald-100 hover:scale-105",
                !isOccupe &&
                    selected &&
                    "cursor-pointer border-blue-500 bg-blue-500 text-white shadow-md shadow-blue-200 scale-105"
            )}
            style={{ width: SIEGE_W, height: SIEGE_H }}
        >
            {siege.numero}
            {isOccupe && (
                <span className="absolute inset-0 flex items-center justify-center">
                    <span className="h-0.5 w-3/4 rotate-45 rounded bg-gray-400" />
                </span>
            )}
        </button>
    );
}

// ─── Plan du car ──────────────────────────────────────────────────────────────

function PlanCar({
    sieges,
    siegesGauche,
    siegesDroite,
    selectedIds,
    onToggle,
}: {
    sieges: Siege[];
    siegesGauche: number;
    siegesDroite: number;
    selectedIds: Set<number>;
    onToggle: (siege: Siege) => void;
}) {
    const byRangee = sieges.reduce<Record<number, Siege[]>>((acc, s) => {
        (acc[s.rangee] ??= []).push(s);
        return acc;
    }, {});

    const rangees = Object.keys(byRangee)
        .map(Number)
        .sort((a, b) => a - b);

    return (
        <div className="overflow-x-auto pb-2">
            {/* En-tête colonnes */}
            <div
                className="flex items-center mb-2 text-[10px] font-semibold text-gray-400 uppercase tracking-wider"
                style={{ paddingLeft: 32, gap: GAP }}
            >
                {Array.from({ length: siegesGauche }, (_, i) => (
                    <div
                        key={i}
                        className="flex items-center justify-center"
                        style={{ width: SIEGE_W }}
                    >
                        {String.fromCharCode(65 + i)}
                    </div>
                ))}
                <div style={{ width: AISLE + GAP * 2 }} />
                {Array.from({ length: siegesDroite }, (_, i) => (
                    <div
                        key={i}
                        className="flex items-center justify-center"
                        style={{ width: SIEGE_W }}
                    >
                        {String.fromCharCode(65 + siegesGauche + i)}
                    </div>
                ))}
            </div>

            {/* Rangées */}
            <div className="flex flex-col" style={{ gap: GAP }}>
                {rangees.map((rangee) => {
                    const siègesDeLaRangee = byRangee[rangee].sort((a, b) => {
                        if (a.cote !== b.cote) return a.cote === "GAUCHE" ? -1 : 1;
                        return a.colonne - b.colonne;
                    });

                    const gauche = siègesDeLaRangee.filter(
                        (s) => s.cote === "GAUCHE"
                    );
                    const droite = siègesDeLaRangee.filter(
                        (s) => s.cote === "DROITE"
                    );

                    return (
                        <div
                            key={rangee}
                            className="flex items-center"
                            style={{ gap: GAP }}
                        >
                            {/* Numéro rangée */}
                            <div className="w-7 text-right text-[10px] font-semibold text-gray-400">
                                {rangee}
                            </div>

                            {/* Gauche */}
                            <div className="flex" style={{ gap: GAP }}>
                                {gauche.map((s) => (
                                    <SiegeCell
                                        key={s.id}
                                        siege={s}
                                        selected={selectedIds.has(s.id)}
                                        onClick={() => onToggle(s)}
                                    />
                                ))}
                                {Array.from(
                                    { length: siegesGauche - gauche.length },
                                    (_, i) => (
                                        <div
                                            key={`eg-${i}`}
                                            style={{ width: SIEGE_W, height: SIEGE_H }}
                                        />
                                    )
                                )}
                            </div>

                            {/* Allée */}
                            <div
                                className="flex items-center justify-center text-[9px] text-gray-300 font-medium"
                                style={{ width: AISLE, height: SIEGE_H }}
                            >
                                {rangee === 1 ? "≡" : ""}
                            </div>

                            {/* Droite */}
                            <div className="flex" style={{ gap: GAP }}>
                                {droite.map((s) => (
                                    <SiegeCell
                                        key={s.id}
                                        siege={s}
                                        selected={selectedIds.has(s.id)}
                                        onClick={() => onToggle(s)}
                                    />
                                ))}
                                {Array.from(
                                    { length: siegesDroite - droite.length },
                                    (_, i) => (
                                        <div
                                            key={`ed-${i}`}
                                            style={{ width: SIEGE_W, height: SIEGE_H }}
                                        />
                                    )
                                )}
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Légende */}
            <div className="flex items-center gap-4 mt-4 text-xs text-gray-500">
                <span className="flex items-center gap-1.5">
                    <span className="inline-block w-4 h-4 rounded border-2 border-emerald-300 bg-emerald-50" />
                    Libre
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="inline-block w-4 h-4 rounded border-2 border-blue-500 bg-blue-500" />
                    Sélectionné
                </span>
                <span className="flex items-center gap-1.5">
                    <span className="inline-block w-4 h-4 rounded border-2 border-gray-200 bg-gray-100" />
                    Occupé
                </span>
            </div>
        </div>
    );
}






function CreatedTicketsPanel({ // Pour le post création sans redirection
    tickets,
    onNouvelleVente,
}: {
    tickets: CreatedTicket[]
    onNouvelleVente: () => void
}) {
    const [printingAll, setPrintingAll] = useState(false)

    const handlePrintAll = async () => {
        setPrintingAll(true)
        try {
            const res = await fetch("/ticket/batch/print", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ ids: tickets.map(t => t.id) }),
            })
            if (res.ok) {
                const blob = await res.blob()
                const url = URL.createObjectURL(blob)
                window.open(url, "_blank")
                setTimeout(() => URL.revokeObjectURL(url), 10_000)
            }
        } finally {
            setPrintingAll(false)
        }
    }

    return (
        <Card className="border-emerald-200 bg-emerald-50/50">
            <CardHeader className="pb-3">
                <div className="flex items-center justify-between">
                    <CardTitle className="flex items-center gap-2 text-base text-emerald-700">
                        <CheckCircle2 className="h-5 w-5" />
                        {tickets.length > 1
                            ? `${tickets.length} tickets créés avec succès`
                            : "Ticket créé avec succès"}
                    </CardTitle>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={onNouvelleVente}
                        className="gap-1.5 text-xs"
                    >
                        <Plus className="h-3.5 w-3.5" />
                        Nouvelle vente
                    </Button>
                </div>
            </CardHeader>
            <CardContent className="space-y-2">
                {/* Liste des tickets créés */}
                {tickets.map((ticket) => (
                    <div
                        key={ticket.id}
                        className="flex items-center justify-between rounded-lg border border-emerald-200 bg-white px-4 py-3"
                    >
                        <div className="flex items-center gap-3">
                            {/* Badge siège */}
                            <div className="flex h-8 w-8 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                                {ticket.siege.numero}
                            </div>
                            <div>
                                <p className="text-sm font-mono font-medium text-gray-700">
                                    {ticket.codeticket}
                                </p>
                                <p className="text-xs text-gray-400">
                                    {ticket.nomclient ?? "Passager non renseigné"}
                                    {" · "}
                                    <span className="font-medium text-emerald-600">
                                        {ticket.prix.toLocaleString("fr-FR")} FCFA
                                    </span>
                                </p>
                            </div>
                        </div>

                        {/* Actions par ticket */}
                        <div className="flex items-center gap-1.5">
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-8 gap-1.5 text-xs"
                                onClick={() =>
                                    window.open(`/ticket/${ticket.id}/pdf`, "_blank")
                                }
                            >
                                <Printer className="h-3.5 w-3.5" />
                                Imprimer
                            </Button>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                className="h-8 gap-1.5 text-xs"
                                asChild
                            >
                                <a href={`/ticket/${ticket.id}`}>
                                    <Eye className="h-3.5 w-3.5" />
                                    Voir
                                </a>
                            </Button>
                        </div>
                    </div>
                ))}

                {/* Actions globales si plusieurs tickets */}
                {tickets.length > 1 && (
                    <div className="flex items-center justify-end gap-2 pt-2 border-t border-emerald-200">
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            onClick={handlePrintAll}
                            disabled={printingAll}
                            className="gap-1.5 text-xs"
                        >
                            {printingAll ? (
                                <Loader2 className="h-3.5 w-3.5 animate-spin" />
                            ) : (
                                <Printer className="h-3.5 w-3.5" />
                            )}
                            Tout imprimer ({tickets.length})
                        </Button>
                    </div>
                )}
            </CardContent>
        </Card>
    )
}


function QuickConfirmDialog({ // Pour vente rapide
    siege,
    voyage,
    open,
    onClose,
    onConfirm,
    submitting,
}: {
    siege: Siege | null
    voyage: Voyage | null
    open: boolean
    onClose: () => void
    onConfirm: () => void
    submitting: boolean
}) {
    if (!siege || !voyage) return null

    return (
        <Dialog open={open} onOpenChange={(o) => { if (!o) onClose() }}>
            <DialogContent className="max-w-sm">
                <DialogHeader>
                    <DialogTitle className="flex items-center gap-2">
                        <Zap className="h-4 w-4 text-amber-500" />
                        Confirmer la vente rapide
                    </DialogTitle>
                    <DialogDescription>
                        Vérifiez les informations avant de confirmer.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-3 py-2">
                    {/* Siège */}
                    <div className="flex items-center justify-between rounded-lg bg-muted/50 px-4 py-3">
                        <span className="text-sm text-muted-foreground">Siège</span>
                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-600">
                            {siege.numero}
                        </div>
                    </div>
                    {/* Trajet */}
                    <div className="flex items-center justify-between rounded-lg bg-muted/50 px-4 py-3">
                        <span className="text-sm text-muted-foreground">Trajet</span>
                        <span className="text-sm font-medium">
                            {voyage.provenance} → {voyage.destination}
                        </span>
                    </div>
                    {/* Info */}
                    <p className="text-xs text-muted-foreground text-center">
                        Le ticket sera créé sans informations passager.
                        Le PDF s'ouvrira automatiquement.
                    </p>
                </div>

                <DialogFooter className="gap-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={onClose}
                        disabled={submitting}
                    >
                        Annuler
                    </Button>
                    <Button
                        type="button"
                        onClick={onConfirm}
                        disabled={submitting}
                        className="bg-amber-500 hover:bg-amber-600 text-white gap-2"
                    >
                        {submitting ? (
                            <>
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Création…
                            </>
                        ) : (
                            <>
                                <Zap className="h-4 w-4" />
                                Confirmer et imprimer
                            </>
                        )}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    )
}



// ─── Composant principal ──────────────────────────────────────────────────────

export default function TicketForm({
    voyages,
    gares,
    preselectVoyageId,
}: TicketFormProps) {
    const [voyageId, setVoyageId] = useState<string>(
        preselectVoyageId ? String(preselectVoyageId) : ""
    );
    const [siegesData, setSiegesData] = useState<SiegesResponse | null>(null);
    const [loadingSieges, setLoadingSieges] = useState(false);
    const [selectedSieges, setSelectedSieges] = useState<Siege[]>([]);
    const [clientInfos, setClientInfos] = useState<Record<number, ClientInfo>>(
        {}
    );
    const [submitting, setSubmitting] = useState(false);
    const [flashError, setFlashError] = useState<string | null>(null);
    const [flashSuccess, setFlashSuccess] = useState<string | null>(null);

    const selectedVoyage = voyages.find((v) => String(v.id) === voyageId);


    const [gareId, setGareId] = useState<string>("")


    const [createdTickets, setCreatedTickets] = useState<CreatedTicket[]>([]) // Pour le post création sans redirection, pour l'utiliser on supprime toute la logique de redirection/window.open dans 'handleSubmit'
    const handleNouvelleVente = () => {
        setCreatedTickets([])
        setSelectedSieges([])
        setClientInfos({})
        // Garder le voyage sélectionné pour enchaîner les ventes rapidement
        // Garder gareId et voyageId pour enchaîner rapidement
    }

    const [mode, setMode] = useState<"normal" | "rapide">("normal") // Pour la vente rapide
    const [quickSiege, setQuickSiege] = useState<Siege | null>(null)
    const [quickSubmitting, setQuickSubmitting] = useState(false)
    const handleQuickSell = async () => {
    if (!quickSiege || !voyageId || !gareId) return
        setQuickSubmitting(true)

        try {
            const res = await fetch("/ticket/nouveau", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    tickets: [{
                        voyage:        `/api/voyages/${voyageId}`,
                        siege:         quickSiege["@id"],
                        gare:          `/api/gares/${gareId}`,
                        nomclient:     null,
                        contactclient: null,
                    }],
                }),
            })

            const data = await res.json()

            if (!res.ok) {
                setFlashError(data.detail ?? "Erreur lors de la création.")
                setQuickSiege(null)
                return
            }

            if (data.created?.length > 0) {
                const id = data.created[0]

                // Charger les détails du ticket créé pour le panneau
                const detail = await fetch(`/ticket/${id}/json`).then(r => r.json())
                setCreatedTickets(prev => [...prev, detail])

                // Impression PDF automatique
                window.open(`/ticket/${id}/pdf`, "_blank")

                // Recharger le plan
                await loadSieges(voyageId)
            }

            if (data.errors?.length > 0) {
                setFlashError(data.errors.join(" | "))
            }

        } catch {
            setFlashError("Erreur réseau. Veuillez réessayer.")
        } finally {
            setQuickSubmitting(false)
            setQuickSiege(null)
        }
    }


    // ── Charger les sièges ────────────────────────────────────────────────────
    const loadSieges = useCallback(async (id: string) => {
        setLoadingSieges(true);
        setSiegesData(null);
        setSelectedSieges([]);
        setClientInfos({});
        setFlashError(null);
        setFlashSuccess(null);
        try {
            const res = await fetch(`/ticket/sieges/${id}`);
            if (!res.ok) throw new Error("Erreur réseau");
            const data: SiegesResponse = await res.json();
            setSiegesData(data);
        } catch {
            setFlashError("Impossible de charger le plan des sièges.");
        } finally {
            setLoadingSieges(false);
        }
    }, []);

    /*
    useEffect(() => {
        if (voyageId) loadSieges(voyageId);
    }, [voyageId, loadSieges]);
    */
    // Après — ne charge que si voyage ET gare sont sélectionnés sinon le plan se charge uniquement quand les deux sont sélectionnés. L'inconvénient : si l'agent change de voyage après avoir déjà sélectionné une gare, le plan se recharge immédiatement — ce qui est le comportement souhaité. Donc les deux approches sont valides
    useEffect(() => {
        if (voyageId && gareId) loadSieges(voyageId);
    }, [voyageId, gareId, loadSieges]);

    // ── Sélection siège ───────────────────────────────────────────────────────
    const toggleSiege = (siege: Siege) => {
        setSelectedSieges((prev) => {
            const exists = prev.find((s) => s.id === siege.id);
            if (exists) {
                setClientInfos((ci) => {
                    const next = { ...ci };
                    delete next[siege.id];
                    return next;
                });
                return prev.filter((s) => s.id !== siege.id);
            }
            setClientInfos((ci) => ({
                ...ci,
                [siege.id]: { nomclient: "", contactclient: "" },
            }));
            return [...prev, siege];
        });
    };

    const updateClientInfo = (
        siegeId: number,
        field: keyof ClientInfo,
        value: string
    ) => {
        setClientInfos((prev) => ({
            ...prev,
            [siegeId]: { ...prev[siegeId], [field]: value },
        }));
    };

    // ── Soumission ────────────────────────────────────────────────────────────
    const handleSubmit = async () => {
        setFlashError(null);
        setFlashSuccess(null);

        if (!voyageId) {
            setFlashError("Veuillez sélectionner un voyage.");
            return;
        }

        if(!gareId) {
            setFlashError("Veuillez sélectionner une gare d'embarquement.")
            return
        }

        if (selectedSieges.length === 0) {
            setFlashError("Veuillez sélectionner au moins un siège.");
            return;
        }

        // nomclient et contactclient sont optionnels — pas de validation requise
        const tickets = selectedSieges.map((s) => ({
            voyage: `/api/voyages/${voyageId}`,
            siege: s["@id"],  // IRI : /api/sieges/{id}
            gare: `/api/gares/${gareId}`,
            nomclient: clientInfos[s.id]?.nomclient || null,
            contactclient: clientInfos[s.id]?.contactclient || null,
        }));

        setSubmitting(true);
        try {
            const res = await fetch("/ticket/nouveau", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({ tickets }),
            });

            const data = await res.json();

            if (!res.ok) {
                setFlashError(data.detail ?? "Erreur lors de la création.");
                return;
            }

            // Erreurs partielles (certains tickets ont échoué)
            if (data.errors?.length > 0) {
                setFlashError(data.errors.join(" | "));
            }

            /*
            if (data.created?.length > 0) {
                if (data.created.length === 1) {
                    // Rediriger vers le ticket créé
                    // window.location.href = `/ticket/${data.created[0]}`; // window.location.href = `/ticket/${data.created[0]}/pdf`;
                    visit(`/ticket/${data.created[0]}`) // Pour que turbo redirige 
                } else {
                     // Vente groupée → ouvrir chaque PDF dans un nouvel onglet
                    // Petit délai entre chaque ouverture pour éviter le blocage popup des navigateurs
                    data.created.forEach((id: number, index: number) => {
                        setTimeout(() => {
                            window.open(`/ticket/${id}/pdf`, `_blank`);
                        }, index * 300);
                    });

                    /* Recharger le plan après l'ouverture des onglets
                    setFlashSuccess(
                        `${data.created.length} ticket(s) créé(s). Si les onglets n'ont pas ouvert, ` +
                        `imprimez-les depuis la liste des tickets.`
                    ); *

                    flash(`${data.created.length} ticket(s) créé(s). Si les onglets n'ont pas ouvert, ` + `imprimez-les depuis la liste des tickets.`, 'success'); // Vu que certains navigateurs bloquent 'window.open' si ce n'est pas déclenché directement par un clic utilisateur

                    await loadSieges(voyageId);
                }
            }
            */
            if (data.created?.length > 0) { // Post création sans redirection
                // Charger les détails de chaque ticket créé
                const details = await Promise.all(
                    data.created.map((id: number) =>
                        fetch(`/ticket/${id}/json`).then(r => r.json())
                    )
                )
                // setCreatedTickets(details) // ❌ Écrase les tickets précédents
                // ✅ Accumule
                setCreatedTickets(prev => [...prev, ...details])
                // Recharger le plan pour refléter les nouveaux sièges occupés
                await loadSieges(voyageId)
                // Réinitialiser la sélection
                setSelectedSieges([])
                setClientInfos({})
            }

        } catch {
            setFlashError("Erreur réseau. Veuillez réessayer.");
        } finally {
            setSubmitting(false);
        }
    };

    const placesRestantes = selectedVoyage
        ? selectedVoyage.placestotal - selectedVoyage.placesoccupees
        : null;

    // ─────────────────────────────────────────────────────────────────────────

    return (
        <div className="space-y-6">
            {/* Flash messages */}
            {flashError && (
                <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{flashError}</span>
                </div>
            )}
            {flashSuccess && (
                <div className="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                    <CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />
                    <span>{flashSuccess}</span>
                </div>
            )}





            {/* Panneau post-création */}
            {createdTickets.length > 0 && (
                <CreatedTicketsPanel
                    tickets={createdTickets}
                    onNouvelleVente={handleNouvelleVente}
                />
            )}


             {/* Onglets - Pour la vente rapide */}
            <Tabs value={mode} onValueChange={(v) => {
                setMode(v as "normal" | "rapide")
                // Réinitialiser la sélection au changement d'onglet
                setSelectedSieges([])
                setClientInfos({})
                setQuickSiege(null)
            }}>
                <TabsList className="w-full">
                    <TabsTrigger value="normal" className="flex-1">
                        Vente normale
                    </TabsTrigger>
                    <TabsTrigger value="rapide" className="flex-1 gap-1.5">
                        <Zap className="h-3.5 w-3.5 text-amber-500" />
                        Vente rapide
                    </TabsTrigger>
                </TabsList>
            </Tabs>

            {/* Sélection voyage + gare côte à côte */}
            <Card>
                <CardHeader className="pb-3">
                    <CardTitle className="flex items-center gap-2 text-base">
                        <MapPin className="h-4 w-4 text-blue-500" />
                        Voyage &amp; Gare
                    </CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        {/* Voyage */}
                        <div className="space-y-1.5">
                            <Label htmlFor="voyage">Voyage</Label>
                            <Select value={voyageId} onValueChange={setVoyageId}>
                                <SelectTrigger id="voyage" className="w-full">
                                    <SelectValue placeholder="Choisir un voyage…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {voyages.map((v) => (
                                        <SelectItem key={v.id} value={String(v.id)}>
                                            <span className="font-mono text-xs text-gray-500 mr-2">
                                                {v.codevoyage}
                                            </span>
                                            {v.provenance} → {v.destination}
                                            <span className="ml-2 text-xs text-gray-400">
                                                ({v.placestotal - v.placesoccupees} places restantes)
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        {/* Gare */}
                        <div className="space-y-1.5">
                            <Label htmlFor="gare">Gare d'embarquement</Label>
                            <Select value={gareId} onValueChange={setGareId}>
                                <SelectTrigger id="gare" className="w-full">
                                    <SelectValue placeholder="Choisir une gare…" />
                                </SelectTrigger>
                                <SelectContent>
                                    {gares.map((g) => (
                                        <SelectItem key={g.id} value={String(g.id)}>
                                            {g.libelle}
                                            {g.ville && (
                                                <span className="ml-2 text-xs text-gray-400">
                                                    · {g.ville}
                                                </span>
                                            )}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {/* Badges info voyage */}
                    {selectedVoyage && (
                        <div className="flex flex-wrap gap-2 text-sm">
                            <Badge variant="outline" className="gap-1">
                                <Bus className="h-3 w-3" />
                                {selectedVoyage.car?.matricule ?? "Aucun car"}
                            </Badge>
                            <Badge variant="outline" className="gap-1">
                                <Users className="h-3 w-3" />
                                {selectedVoyage.placesoccupees} / {selectedVoyage.placestotal} places
                            </Badge>
                            {placesRestantes !== null && (
                                <Badge variant={placesRestantes === 0 ? "destructive" : "secondary"}>
                                    {placesRestantes === 0 ? "Complet" : `${placesRestantes} restante(s)`}
                                </Badge>
                            )}
                            {selectedVoyage.datefin && (
                                <Badge variant="destructive" className="gap-1">
                                    Voyage clôturé
                                </Badge>
                            )}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Ajouter */}
            {voyageId && selectedVoyage?.datefin && (
                <div className="flex items-start gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                    Ce voyage est clôturé. La vente de tickets est impossible.
                </div>
            )}

            {/*
            // ✅ — déjà correct en fait, mais l'ordre dans le JSX pose problème
            // Le message "voyage clôturé" et "sélectionnez une gare" peuvent s'afficher ensemble
            // Ajouter une condition exclusive :
            */}
            {voyageId && !selectedVoyage?.datefin && !gareId && (
                <div className="flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                    <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />
                    Veuillez sélectionner une gare d'embarquement avant de choisir un siège.
                </div>
            )} 
        {/* Plan + formulaires passagers côte à côte */}
        {/* Plan du car — comportement selon le mode: Pour la vente rapide  */}
            {voyageId && gareId && !selectedVoyage?.datefin && (
                <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">

                    {/* Plan du véhicule */}
                    <Card>
                        <CardHeader className="pb-3">
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Bus className="h-4 w-4 text-blue-500" />
                                Plan du véhicule
                            </CardTitle>
                            <CardDescription>
                                {mode === "rapide"
                                    ? "Cliquez sur un siège pour créer et imprimer le ticket immédiatement."
                                    : "Cliquez sur les sièges disponibles pour les sélectionner."}
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {loadingSieges ? (
                                <div className="flex items-center justify-center py-12 text-gray-400">
                                    <Loader2 className="h-6 w-6 animate-spin mr-2" />
                                    Chargement du plan…
                                </div>
                            ) : siegesData && siegesData.sieges.length > 0 ? (
                                <PlanCar
                                    sieges={siegesData.sieges}
                                    siegesGauche={siegesData.siegesGauche}
                                    siegesDroite={siegesData.siegesDroite}
                                    selectedIds={new Set(selectedSieges.map((s) => s.id))}
                                    onToggle={(siege) => {
                                        if (mode === "rapide") {
                                            setQuickSiege(siege)
                                        } else {
                                            toggleSiege(siege)
                                        }
                                    }}
                                />
                            ) : siegesData ? (
                                <p className="text-sm text-gray-400 py-6 text-center">
                                    Aucun siège configuré pour ce véhicule.
                                </p>
                            ) : null}
                        </CardContent>
                    </Card>

                    {/* Formulaires passagers — mode normal uniquement */}
                    {mode === "normal" && (
                        <div className="flex flex-col gap-4">
                            {selectedSieges.length > 0 ? (
                                <Card className="flex-1">
                                    <CardHeader className="pb-3">
                                        <CardTitle className="flex items-center gap-2 text-base">
                                            <Users className="h-4 w-4 text-blue-500" />
                                            Informations passagers
                                            <Badge className="ml-1">
                                                {selectedSieges.length} siège(s)
                                            </Badge>
                                        </CardTitle>
                                        <CardDescription>
                                            Ces informations sont optionnelles et peuvent être
                                            renseignées plus tard.
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {selectedSieges.map((siege, idx) => (
                                            <div key={siege.id}>
                                                {idx > 0 && <Separator className="mb-4" />}
                                                <div className="space-y-3">
                                                    <div className="flex items-center gap-2">
                                                        <div className="flex h-7 w-7 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600">
                                                            {siege.numero}
                                                        </div>
                                                        <span className="text-sm font-medium text-gray-700">
                                                            Siège {siege.numero}
                                                            <span className="ml-1.5 text-xs font-normal text-gray-400">
                                                                Rangée {siege.rangee} ·{" "}
                                                                {siege.cote === "GAUCHE" ? "Gauche" : "Droite"}
                                                            </span>
                                                        </span>
                                                    </div>
                                                    <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 pl-9">
                                                        <div className="space-y-1.5">
                                                            <Label
                                                                htmlFor={`nom-${siege.id}`}
                                                                className="text-gray-600"
                                                            >
                                                                Nom du client{" "}
                                                                <span className="text-gray-400 font-normal">
                                                                    (optionnel)
                                                                </span>
                                                            </Label>
                                                            <Input
                                                                id={`nom-${siege.id}`}
                                                                placeholder="Nom complet"
                                                                value={clientInfos[siege.id]?.nomclient ?? ""}
                                                                onChange={(e) =>
                                                                    updateClientInfo(siege.id, "nomclient", e.target.value)
                                                                }
                                                            />
                                                        </div>
                                                        <div className="space-y-1.5">
                                                            <Label
                                                                htmlFor={`contact-${siege.id}`}
                                                                className="text-gray-600"
                                                            >
                                                                Contact{" "}
                                                                <span className="text-gray-400 font-normal">
                                                                    (optionnel)
                                                                </span>
                                                            </Label>
                                                            <Input
                                                                id={`contact-${siege.id}`}
                                                                placeholder="Téléphone ou email"
                                                                value={clientInfos[siege.id]?.contactclient ?? ""}
                                                                onChange={(e) =>
                                                                    updateClientInfo(siege.id, "contactclient", e.target.value)
                                                                }
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </CardContent>
                                </Card>
                            ) : (
                                <Card className="flex-1 border-dashed">
                                    <CardContent className="flex items-center justify-center py-12">
                                        <p className="text-sm text-gray-400 text-center">
                                            Sélectionnez des sièges sur le plan<br />
                                            pour renseigner les informations passagers.
                                        </p>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Barre de confirmation intégrée dans la colonne droite */}
                            {selectedSieges.length > 0 && (
                                <div className="flex items-center justify-between rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">
                                    <div className="text-sm text-blue-700">
                                        <span className="font-semibold">{selectedSieges.length}</span>{" "}
                                        ticket(s) à créer
                                        {selectedVoyage?.car && (
                                            <span className="text-blue-400 ml-1.5">
                                                · {selectedVoyage.car.matricule}
                                            </span>
                                        )}
                                    </div>
                                    <Button
                                        type="button"
                                        onClick={handleSubmit}
                                        disabled={submitting}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        {submitting ? (
                                            <>
                                                <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                                Création…
                                            </>
                                        ) : (
                                            <>
                                                <CheckCircle2 className="mr-2 h-4 w-4" />
                                                Confirmer{" "}
                                                {selectedSieges.length > 1 ? "les tickets" : "le ticket"}
                                            </>
                                        )}
                                    </Button>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* Dialog confirmation vente rapide */}
            <QuickConfirmDialog
                siege={quickSiege}
                voyage={selectedVoyage ?? null}
                open={quickSiege !== null}
                onClose={() => setQuickSiege(null)}
                onConfirm={handleQuickSell}
                submitting={quickSubmitting}
            />
        </div>
    );
}
