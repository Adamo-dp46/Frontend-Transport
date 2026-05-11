export interface SiegePlan {
    id: number
    numero: number
    rangee: number
    colonne: number
    cote: "GAUCHE" | "DROITE"
    statut: "LIBRE" | "OCCUPE"
}

interface PlanCarProps {
    sieges: SiegePlan[]
    siegesGauche: number
    siegesDroite: number
    selectedIds?: Set<number>
    onToggle?: (siege: SiegePlan) => void
    // Mode affichage — interactif (billetterie) ou lecture seule (show car)
    readonly?: boolean
}

// ─── Constantes dimensionnelles ───────────────────────────────────────────────

const SEAT_SIZE  = 36
const SEAT_GAP   = 6
const AISLE_W    = 32
const ROW_H      = SEAT_SIZE + SEAT_GAP
const PAD_X      = 20
const PAD_TOP    = 80   // espace pour le volant / avant du bus
const PAD_BOTTOM = 48   // espace pour l'arrière
const BORDER_R   = 18   // border-radius carrosserie

// ─── Couleurs ─────────────────────────────────────────────────────────────────

const COLORS = {
    body:        '#1e293b',   // carrosserie sombre
    bodyStroke:  '#334155',
    window:      '#0ea5e9',   // vitres bleues
    windowAlpha: '33',        // transparence vitres
    floor:       '#f8fafc',   // sol clair
    floorStroke: '#e2e8f0',
    aisle:       '#f1f5f9',   // allée légèrement différente
    seatFree:    '#d1fae5',   // vert clair
    seatFreeBorder: '#10b981',
    seatOccupied:   '#fee2e2',
    seatOccupiedBorder: '#ef4444',
    seatSelected:   '#3b82f6',
    seatSelectedBorder: '#1d4ed8',
    seatText:    '#1e293b',
    seatTextSelected: '#ffffff',
    wheel:       '#475569',
    wheelCenter: '#94a3b8',
    door:        '#fbbf24',
    doorStroke:  '#f59e0b',
    stripe:      '#0ea5e9',   // bande décorative latérale
}

// ─── Sous-composant : siège individuel ────────────────────────────────────────

function Seat({
    siege,
    x,
    y,
    selected,
    readonly,
    onToggle,
}: {
    siege: SiegePlan
    x: number
    y: number
    selected: boolean
    readonly: boolean
    onToggle?: (s: SiegePlan) => void
}) {
    const isOccupe = siege.statut === "OCCUPE"

    let fill   = COLORS.seatFree
    let stroke = COLORS.seatFreeBorder
    let textColor = COLORS.seatText

    if (isOccupe) {
        fill   = COLORS.seatOccupied
        stroke = COLORS.seatOccupiedBorder
    } else if (selected) {
        fill      = COLORS.seatSelected
        stroke    = COLORS.seatSelectedBorder
        textColor = COLORS.seatTextSelected
    }

    const cursor = readonly || isOccupe ? 'default' : 'pointer'

    // Forme du siège : rectangle arrondi avec dossier
    const s = SEAT_SIZE
    const r = 5

    return (
        <g
            transform={`translate(${x}, ${y})`}
            onClick={() => {
                if (!readonly && !isOccupe && onToggle) onToggle(siege)
            }}
            style={{ cursor }}
        >
            {/* Dossier du siège */}
            <rect
                x={2} y={0}
                width={s - 4} height={s * 0.32}
                rx={r} ry={r}
                fill={fill}
                stroke={stroke}
                strokeWidth={1.5}
                opacity={0.7}
            />
            {/* Assise */}
            <rect
                x={0} y={s * 0.28}
                width={s} height={s * 0.6}
                rx={r} ry={r}
                fill={fill}
                stroke={stroke}
                strokeWidth={1.5}
            />
            {/* Accoudoir gauche */}
            <rect
                x={-3} y={s * 0.32}
                width={4} height={s * 0.4}
                rx={2} ry={2}
                fill={stroke}
                opacity={0.6}
            />
            {/* Accoudoir droit */}
            <rect
                x={s - 1} y={s * 0.32}
                width={4} height={s * 0.4}
                rx={2} ry={2}
                fill={stroke}
                opacity={0.6}
            />
            {/* Numéro */}
            <text
                x={s / 2}
                y={s * 0.65}
                textAnchor="middle"
                dominantBaseline="middle"
                fontSize={10}
                fontWeight="700"
                fontFamily="ui-monospace, monospace"
                fill={textColor}
            >
                {siege.numero}
            </text>
            {/* Croix si occupé */}
            {isOccupe && (
                <>
                    <line
                        x1={6} y1={s * 0.35}
                        x2={s - 6} y2={s * 0.82}
                        stroke={COLORS.seatOccupiedBorder}
                        strokeWidth={1.5}
                        opacity={0.5}
                    />
                    <line
                        x1={s - 6} y1={s * 0.35}
                        x2={6} y2={s * 0.82}
                        stroke={COLORS.seatOccupiedBorder}
                        strokeWidth={1.5}
                        opacity={0.5}
                    />
                </>
            )}
        </g>
    )
}

// ─── Composant principal ──────────────────────────────────────────────────────

export default function PlanCar({
    sieges,
    siegesGauche,
    siegesDroite,
    selectedIds = new Set(),
    onToggle,
    readonly = false,
}: PlanCarProps) {

    // Grouper par rangée
    const byRangee = sieges.reduce<Record<number, SiegePlan[]>>((acc, s) => {
        (acc[s.rangee] ??= []).push(s)
        return acc
    }, {})

    const rangees = Object.keys(byRangee).map(Number).sort((a, b) => a - b)
    const nRangees = rangees.length

    // Dimensions SVG
    const totalCols  = siegesGauche + siegesDroite
    const interiorW  = siegesGauche * SEAT_SIZE + siegesGauche * SEAT_GAP
                     + AISLE_W
                     + siegesDroite * SEAT_SIZE + siegesDroite * SEAT_GAP
    const svgW       = interiorW + PAD_X * 2
    const svgH       = nRangees * ROW_H + PAD_TOP + PAD_BOTTOM + 20

    // Position X du début de chaque colonne gauche / droite
    const leftStartX = PAD_X
    const aisleStartX = leftStartX + siegesGauche * (SEAT_SIZE + SEAT_GAP)
    const rightStartX = aisleStartX + AISLE_W

    return (
        <div className="w-full overflow-x-auto">
            <svg
                viewBox={`0 0 ${svgW} ${svgH}`}
                width={svgW}
                height={svgH}
                xmlns="http://www.w3.org/2000/svg"
                style={{ display: 'block', margin: '0 auto' }}
            >
                {/* ── Carrosserie extérieure ── */}
                <rect
                    x={0} y={0}
                    width={svgW} height={svgH}
                    rx={BORDER_R} ry={BORDER_R}
                    fill={COLORS.body}
                    stroke={COLORS.bodyStroke}
                    strokeWidth={2}
                />

                {/* ── Bande décorative latérale ── */}
                <rect
                    x={0} y={svgH * 0.38}
                    width={svgW} height={8}
                    fill={COLORS.stripe}
                    opacity={0.8}
                />
                <rect
                    x={0} y={svgH * 0.38 + 10}
                    width={svgW} height={3}
                    fill={COLORS.stripe}
                    opacity={0.4}
                />

                {/* ── Vitres latérales gauche ── */}
                {rangees.map((_, i) => (
                    <rect
                        key={`win-l-${i}`}
                        x={1} y={PAD_TOP + i * ROW_H + 4}
                        width={PAD_X - 2} height={SEAT_SIZE - 4}
                        rx={3} ry={3}
                        fill={`${COLORS.window}${COLORS.windowAlpha}`}
                        stroke={COLORS.window}
                        strokeWidth={0.8}
                        opacity={0.7}
                    />
                ))}

                {/* ── Vitres latérales droite ── */}
                {rangees.map((_, i) => (
                    <rect
                        key={`win-r-${i}`}
                        x={svgW - PAD_X + 1} y={PAD_TOP + i * ROW_H + 4}
                        width={PAD_X - 2} height={SEAT_SIZE - 4}
                        rx={3} ry={3}
                        fill={`${COLORS.window}${COLORS.windowAlpha}`}
                        stroke={COLORS.window}
                        strokeWidth={0.8}
                        opacity={0.7}
                    />
                ))}

                {/* ── Sol intérieur ── */}
                <rect
                    x={PAD_X - 2}
                    y={PAD_TOP - 8}
                    width={interiorW + 4}
                    height={nRangees * ROW_H + 20}
                    rx={4} ry={4}
                    fill={COLORS.floor}
                    stroke={COLORS.floorStroke}
                    strokeWidth={1}
                />

                {/* ── Allée centrale ── */}
                <rect
                    x={aisleStartX}
                    y={PAD_TOP - 8}
                    width={AISLE_W}
                    height={nRangees * ROW_H + 20}
                    fill={COLORS.aisle}
                    opacity={0.8}
                />

                {/* Lignes de séparation des rangées */}
                {rangees.map((_, i) => i > 0 && (
                    <line
                        key={`row-line-${i}`}
                        x1={PAD_X}
                        y1={PAD_TOP + i * ROW_H - SEAT_GAP / 2}
                        x2={svgW - PAD_X}
                        y2={PAD_TOP + i * ROW_H - SEAT_GAP / 2}
                        stroke={COLORS.floorStroke}
                        strokeWidth={0.5}
                        strokeDasharray="4 4"
                    />
                ))}

                {/* ── Avant du bus — volant + tableau de bord ── */}
                {/* Tableau de bord */}
                <rect
                    x={PAD_X - 2}
                    y={8}
                    width={interiorW + 4}
                    height={PAD_TOP - 16}
                    rx={6} ry={6}
                    fill="#1e293b"
                    stroke="#334155"
                    strokeWidth={1}
                />
                {/* Pare-brise */}
                <rect
                    x={PAD_X + 8}
                    y={12}
                    width={interiorW - 16}
                    height={PAD_TOP - 28}
                    rx={4} ry={4}
                    fill={`${COLORS.window}55`}
                    stroke={COLORS.window}
                    strokeWidth={1}
                />
                {/* Reflet pare-brise */}
                <rect
                    x={PAD_X + 14}
                    y={15}
                    width={interiorW * 0.25}
                    height={PAD_TOP - 36}
                    rx={3} ry={3}
                    fill="white"
                    opacity={0.08}
                />
                {/* Volant */}
                <circle
                    cx={svgW * 0.25}
                    cy={PAD_TOP - 14}
                    r={14}
                    fill="none"
                    stroke={COLORS.wheel}
                    strokeWidth={4}
                />
                <circle
                    cx={svgW * 0.25}
                    cy={PAD_TOP - 14}
                    r={4}
                    fill={COLORS.wheelCenter}
                />
                <line
                    x1={svgW * 0.25} y1={PAD_TOP - 28}
                    x2={svgW * 0.25} y2={PAD_TOP - 18}
                    stroke={COLORS.wheel}
                    strokeWidth={2}
                />
                <line
                    x1={svgW * 0.25 - 14} y1={PAD_TOP - 14}
                    x2={svgW * 0.25 - 4}  y2={PAD_TOP - 14}
                    stroke={COLORS.wheel}
                    strokeWidth={2}
                />
                <line
                    x1={svgW * 0.25 + 4}  y1={PAD_TOP - 14}
                    x2={svgW * 0.25 + 14} y2={PAD_TOP - 14}
                    stroke={COLORS.wheel}
                    strokeWidth={2}
                />
                {/* Indicateurs tableau de bord */}
                {[0.5, 0.6, 0.7, 0.8].map((p, i) => (
                    <circle
                        key={i}
                        cx={svgW * p}
                        cy={PAD_TOP - 16}
                        r={4}
                        fill={['#22c55e', '#f59e0b', '#ef4444', '#3b82f6'][i]}
                        opacity={0.8}
                    />
                ))}
                {/* Label AVANT */}
                <text
                    x={svgW / 2} y={6}
                    textAnchor="middle"
                    fontSize={7}
                    fontWeight="600"
                    fontFamily="ui-monospace, monospace"
                    fill={COLORS.window}
                    letterSpacing="3"
                >
                    AVANT
                </text>

                {/* ── Porte d'entrée ── */}
                <rect
                    x={svgW - PAD_X - 2}
                    y={PAD_TOP + 4}
                    width={PAD_X}
                    height={ROW_H * 1.4}
                    rx={3} ry={3}
                    fill={COLORS.door}
                    stroke={COLORS.doorStroke}
                    strokeWidth={1.5}
                    opacity={0.9}
                />
                <line
                    x1={svgW - PAD_X + (PAD_X / 2) - 2}
                    y1={PAD_TOP + 4}
                    x2={svgW - PAD_X + (PAD_X / 2) - 2}
                    y2={PAD_TOP + ROW_H * 1.4 + 4}
                    stroke={COLORS.doorStroke}
                    strokeWidth={1}
                    opacity={0.5}
                />
                <text
                    x={svgW - PAD_X / 2 - 2}
                    y={PAD_TOP + ROW_H * 0.75}
                    textAnchor="middle"
                    fontSize={5.5}
                    fontWeight="700"
                    fontFamily="ui-monospace, monospace"
                    fill="#92400e"
                    transform={`rotate(-90, ${svgW - PAD_X / 2 - 2}, ${PAD_TOP + ROW_H * 0.75})`}
                >
                    PORTE
                </text>

                {/* ── Numéros de rangées ── */}
                {rangees.map((rangee, i) => (
                    <text
                        key={`rangee-${rangee}`}
                        x={aisleStartX + AISLE_W / 2}
                        y={PAD_TOP + i * ROW_H + SEAT_SIZE / 2 + 2}
                        textAnchor="middle"
                        dominantBaseline="middle"
                        fontSize={8}
                        fontWeight="600"
                        fontFamily="ui-monospace, monospace"
                        fill="#94a3b8"
                    >
                        {rangee}
                    </text>
                ))}

                {/* ── En-têtes colonnes (A, B, C, D...) ── */}
                {Array.from({ length: siegesGauche }, (_, i) => (
                    <text
                        key={`col-l-${i}`}
                        x={leftStartX + i * (SEAT_SIZE + SEAT_GAP) + SEAT_SIZE / 2}
                        y={PAD_TOP - 14}
                        textAnchor="middle"
                        fontSize={9}
                        fontWeight="700"
                        fontFamily="ui-monospace, monospace"
                        fill="#94a3b8"
                    >
                        {String.fromCharCode(65 + i)}
                    </text>
                ))}
                {Array.from({ length: siegesDroite }, (_, i) => (
                    <text
                        key={`col-r-${i}`}
                        x={rightStartX + i * (SEAT_SIZE + SEAT_GAP) + SEAT_SIZE / 2}
                        y={PAD_TOP - 14}
                        textAnchor="middle"
                        fontSize={9}
                        fontWeight="700"
                        fontFamily="ui-monospace, monospace"
                        fill="#94a3b8"
                    >
                        {String.fromCharCode(65 + siegesGauche + i)}
                    </text>
                ))}

                {/* ── Sièges ── */}
                {rangees.map((rangee, rowIdx) => {
                    const rowSeats = byRangee[rangee].sort((a, b) => {
                        if (a.cote !== b.cote) return a.cote === "GAUCHE" ? -1 : 1
                        return a.colonne - b.colonne
                    })
                    const y = PAD_TOP + rowIdx * ROW_H

                    return rowSeats.map((siege) => {
                        const colIdx = siege.colonne - 1
                        const x = siege.cote === "GAUCHE"
                            ? leftStartX + colIdx * (SEAT_SIZE + SEAT_GAP)
                            : rightStartX + colIdx * (SEAT_SIZE + SEAT_GAP)

                        return (
                            <Seat
                                key={siege.id}
                                siege={siege}
                                x={x}
                                y={y}
                                selected={selectedIds.has(siege.id)}
                                readonly={readonly}
                                onToggle={onToggle}
                            />
                        )
                    })
                })}

                {/* ── Arrière du bus ── */}
                <rect
                    x={PAD_X - 2}
                    y={svgH - PAD_BOTTOM + 4}
                    width={interiorW + 4}
                    height={PAD_BOTTOM - 8}
                    rx={4} ry={4}
                    fill="#1e293b"
                    stroke="#334155"
                    strokeWidth={1}
                />
                <text
                    x={svgW / 2}
                    y={svgH - 6}
                    textAnchor="middle"
                    fontSize={7}
                    fontWeight="600"
                    fontFamily="ui-monospace, monospace"
                    fill={COLORS.window}
                    letterSpacing="3"
                >
                    ARRIÈRE
                </text>

            </svg>

            {/* ── Légende ── */}
            {!readonly && (
                <div className="flex items-center justify-center gap-5 mt-3 text-xs text-gray-500">
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block w-4 h-4 rounded border-2"
                              style={{ background: COLORS.seatFree, borderColor: COLORS.seatFreeBorder }} />
                        Libre
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block w-4 h-4 rounded border-2"
                              style={{ background: COLORS.seatSelected, borderColor: COLORS.seatSelectedBorder }} />
                        Sélectionné
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block w-4 h-4 rounded border-2"
                              style={{ background: COLORS.seatOccupied, borderColor: COLORS.seatOccupiedBorder }} />
                        Occupé
                    </span>
                </div>
            )}
            {readonly && (
                <div className="flex items-center justify-center gap-5 mt-3 text-xs text-gray-500">
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block w-4 h-4 rounded border-2"
                              style={{ background: COLORS.seatFree, borderColor: COLORS.seatFreeBorder }} />
                        Disponible
                    </span>
                    <span className="flex items-center gap-1.5">
                        <span className="inline-block w-4 h-4 rounded border-2"
                              style={{ background: COLORS.seatOccupied, borderColor: COLORS.seatOccupiedBorder }} />
                        Occupé
                    </span>
                </div>
            )}
        </div>
    )
}