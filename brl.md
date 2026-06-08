### Brl

- **Command**
    > php -S localhost:5000 -t public | symfony serve
    > php bin/console cache:clear
    > php bin/console cache:warmup
    > php bin/console debug:router
    > php bin/console make:controller
    > php bin/console make:entity
    > php bin/console make:form
    > php bin/console make:voter
    > php bin/console make:listener
    > php bin/console make:subscriber
    > php bin/console translation:extract --force fr --format=yaml
- 

- 
- Les moyens de paiement ou règlement pour les tickets, courrier ou bagages pour savoir par quoi les clients paient le plus souvent
    > Sur la page de création de ticket :: Mobile money -> Ouvre panneau -> Mtn, Wave.. -> puis on saisi l'indentifiant du paiement dans la base de données
- Reservation de ticket, une personne peut rester chez lui à la maison et réservé un ticket ..puis le tire en ligne(perso)
- Lien vers les ressources de select sur la page de ceux qui en besoin
- Select | Create User et List User
    > Présélection + sur les champs select parente, crée et le présélectionne dans le champ
- Matricule chauffeur(personnel) saisi de façon manuel, Matricule user ayant vendu le ticket, Matricule user ayant enregistré bagage et courrier
- Poids bagage -> nullable, Tarifbagage selon la valeur du bagage plutôt que le poids, Champ 'codeticket' pour savoir le bagage est lié à quelle ticket et trouver un moyen optimisé pour la sélection du ticket lors de la création du bagage
- Vu qu'on a les entités Courrier et Detailcourrier(colis) que doit pouvoir déclarer comme perdu ou c'est les 2
- Gérer le bordereau gare : /api/voyages/1/bordereau?gare=2
- Colis poids -> nullable, Ajouter `date_embauche` sur personnel

- Ticket
    - Code QR sur les tickets
    - Un champ remise ex:1000 sur ticket et on doit le prixvendu et béfinicière :: réduction dans ticket
        - contactbeneficiaire
    - Beneficiaire -> soit on le crée ou on le choisi avec le select..
        - Peut être un client qui vient toujours ou un corsaire(celui qui envoi le client) reçois identreprise et visible dans toutes les gares
    - Carte de fidélité pour les meilleurs clients basé sur le numéro du client

- Pour gérer les dépenses : 2 types (Dépense générale et gare)
    Objetdepense -> libelle          Objetdepensegare..
    Depense                          Depensegare..
        objetdepense -> vers Objetdepense
        date
        montant
        detail

- Place: de la droite vers la gauche et 6 places derrière
    3 4 5 2 1
     6 places
    - Vert: libre au départ, vendu: rouge, libérer à nouveau gris, ainsi de suite

- Courrier: séparer leur chiffre d'affaire avec celle de la société
- Application mobile pour les clients Réservation de tickets voir les départs, scanner un code qr (comportant toutes les informations du ticket)

- : libérer  (annuler), dans le pdf indiquer si c'est annuler

Gare
    users OneToMany
    datecreation
    statut : ACTIF, SUSPENDRE
    contactchefgare..


Dans l'application on a déjà la notion de multi-entreprise, maintenant on vas ajouter la notion de multi-gare
- On sais que l'administrateur de l'entreprise n'est pas lié à une gare mais à une entreprise
- Lorsque l'administrateur voudra crée un utilisateur il va le lié à une gare
- L'administrateur peut suspendre une gare ce qui va bloquer la connexion à tous les utilisateurs de cette gare
- Aussi pour chaque gare on aura un administrateur via un 'ROLE_ADMIN_GARE' (crée par l'administrateur de l'entreprise) qui lui aussi pourra crée ses propres utilisateurs et leur donner des permissions ou les suspendre et autre..


Dans notre logique je crois que j'ai mal gérer le module Exploitation et Billetterie


Eclairssi moi la notion de départ et voyage est ce la même chose ?

- Les sièges grisé et dégrisé
    - Si une gare intermédiaire fais receptionné sur le voyage ça dégrise les sièges qui doivent décendre à cette gare
    - Les sièges grisés en cas de vente ne conçernent que la gare de départ, les autres gares du même trajet peuvent vendre dessus au cas ou il n'y a pas assez de place il programmé un nouveau départ
    - Si la gare 1 vend 30/64, la gare 2 peut vendre les 64 places du départ sans tenir compte des places vendu à la gare précédente, le car arrive à la gare 2 des clients vont décendre et les autres montes au cas ou c'est rempli la gare 2 programme un autre départ pour les restants et vend les tickets et devient la gare de départ du nouveau départ donc les sièges vendu deviennent grisé



- 
    - Une gare ne peut pas vendre un ticket qui n'est sur son trajet, ni voir les données des gares qui ne sont pas sur son trajet
    - Une gare ne peut pas vendre les tickets de la provenance du trajet mais plutôt la destination

- Lors de la création d'un trajet on vas liés les gares qui le conçerne, Dans un trajet on a la gare de départ, la gare de destination et les gares intermédiares
    - Chaque destination à son tarif du genre de gare en gare
    - En créant le trajet on sélectionne les gares du trajet par ordre
    - Les gares entre le trajet ne clôture pas le voyage mais receptionne
    - Les gares intermédiare peuvent vendre les tickets d'un départ(voyage) qui vient vers eux sans tenir compte des places occupées ou siège grisé et s'ils ont vendu plus de tickets il programme un autre départ
    - Vente simultanée sur un car selon le trajet où passera le car

- Pour la vente de tickets
    - Des clients descendent dans les gare intermédiares du trajet et d'autres prennent leur place
    - On doit pouvoir revendre un ticket, si un client descend en cours de chemin et que d'autres monte on doit pouvoir revendre le même sièges

- Dans voyage on un chauffeur et un commercial, le commercial est lié à la gare de départ ce qui lui permet de voir les sièges grisés, le commercial peut vendre les tickets en route via un TPE dans le cas un client monte en route, si un client décend il demande à l'administrateur de la gare de provenance de dégrisé le siège pour qu'il le revende














Et dans le cas du edit — même logique que pour le car, inclure le personnel déjà affecté s'il est suspendu :

```php
$personnels = $this->api->collection('/api/personnels', ['statut' => 'ACTIF']);

// Ajouter les personnels déjà affectés s'ils sont suspendus
foreach ($detailpersonnels as $detail) {
    $personnelId = $detail['personnel']['id'];
    $alreadyInList = array_filter($personnels, fn($p) => $p['id'] === $personnelId);
    if (empty($alreadyInList)) {
        $personnels[] = $detail['personnel'];
    }
}
```




**Intro.js** `introjs.com` - Permet d'indiquer l'utilisation d'une application à un utilisateur
- npm install intro.js --save

## Comparaison Driver.js vs Intro.js

**Driver.js**
- Plus moderne, léger (~5kb gzip)
- Highlight visuel élégant — met en surbrillance l'élément ciblé avec un overlay
- API simple et flexible
- Pas de dépendances
- Meilleur pour des guides contextuels par page/module
- Gratuit et open source

**Intro.js**
- Plus ancien, plus lourd
- Style "tooltip numéroté" classique
- Nécessite une licence pour usage commercial
- Plus de configuration nécessaire

**Recommandation : Driver.js** — plus adapté à ton cas car tu as des guides par module (contextuels par page), il est plus léger, gratuit et son rendu est plus moderne.

---







- L'export côté client


Voici le plan avant de coder.

---

## Plan d'architecture — Export stats

### Principe général

Chaque page de stats aura deux boutons — **PDF** et **Excel** — qui appellent des routes dédiées dans le controller frontend. Ces routes récupèrent les données depuis l'API puis génèrent le fichier.

---

### Routes par module

| Module | PDF | Excel |
|---|---|---|
| Dashboard | `stats.export.pdf` | `stats.export.excel` |
| Billetterie | `billetterie.export.pdf` | `billetterie.export.excel` |
| Agents | `agents.export.pdf` | `agents.export.excel` |
| Personnel | `personnel.export.pdf` | `personnel.export.excel` |
| Flotte | `flotte.export.pdf` | `flotte.export.excel` |
| Trajets | `trajets.export.pdf` | `trajets.export.excel` |
| Caisse | `caisse.export.pdf` | `caisse.export.excel` |
| Courriers | `courriers.export.pdf` | `courriers.export.excel` |
| Bagages | `bagages.export.pdf` | `bagages.export.excel` |

---

### Services utilisés

- **PDF** — `PdfService` déjà en place avec format A4
- **Excel** — `PhpSpreadsheet` que tu as déjà installé pour les rapports

---

### Boutons dans les templates

```twig
<div class="flex items-center gap-2">
    <a href="{{ path('billetterie.export.pdf', {debut: debut, fin: fin}) }}"
       class="btn btn-sm btn-outline" target="_blank">
        PDF
    </a>
    <a href="{{ path('billetterie.export.excel', {debut: debut, fin: fin}) }}"
       class="btn btn-sm btn-outline">
        Excel
    </a>
</div>
```

---

On commence par quel module ? Je suggère **Billetterie** pour établir le pattern, ensuite les autres suivront la même structure.







Pas de problème. On part de zéro.Ces skills sont pour Python — dans ton cas c'est Symfony avec DOMPDF et PhpSpreadsheet. Je pars directement sur le pattern Symfony.

---

### Pattern général — ExportService

On crée un service dédié pour éviter de dupliquer la logique dans chaque controller.

**`src/Domain/Service/ExportService.php`**
```php
<?php

namespace App\Domain\Service;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Twig\Environment;
use Dompdf\Dompdf;
use Dompdf\Options;

class ExportService
{
    public function __construct(
        private readonly Environment $twig,
    ) {}

    public function pdf(string $template, array $data, string $filename): Response
    {
        $html = $this->twig->render($template, $data);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function excel(Spreadsheet $spreadsheet, string $filename): StreamedResponse
    {
        return new StreamedResponse(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Cache-Control'       => 'max-age=0',
        ]);
    }

    /**
     * Style header row — fond sombre, texte blanc, gras
     */
    public function styleHeader(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF1a1a1a']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
        ]);
    }

    /**
     * Style ligne zebra — fond gris clair sur les lignes paires
     */
    public function styleZebra(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, string $range, bool $even): void
    {
        if ($even) {
            $sheet->getStyle($range)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF7F7F7');
        }
    }

    /**
     * Ligne titre du rapport — fusion de cellules
     */
    public function titreRapport(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $titre,
        string $periode,
        int $nbColonnes
    ): void {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($nbColonnes);

        $sheet->mergeCells("A1:{$lastCol}1");
        $sheet->setCellValue('A1', $titre);
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FF111111']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->mergeCells("A2:{$lastCol}2");
        $sheet->setCellValue('A2', 'Période : ' . $periode);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'color' => ['argb' => 'FF666666']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->getRowDimension(2)->setRowHeight(16);
    }
}
```

---

### Export Billetterie — Controller

```php
#[Route('/billetterie/export/pdf', name: 'billetterie.export.pdf', methods: ['GET'])]
public function billetteriePdf(Request $request): Response
{
    $debut = $request->query->get('debut', date('Y-m-01'));
    $fin   = $request->query->get('fin', date('Y-m-t'));

    try {
        $raw = $this->api->item('/api/stats/billetterie?' . http_build_query(['debut' => $debut, 'fin' => $fin]));

        $billetterie = [
            'totalTickets'      => $raw['totalTickets'] ?? 0,
            'recetteTotale'     => $raw['recetteTotale'] ?? 0,
            'recettesParJour'   => $raw['recettesParJour'] ?? [],
            'recettesParTrajet' => $raw['recettesParTrajet'] ?? [],
            'recettesParCar'    => $raw['recettesParCar'] ?? [],
        ];
    } catch (ApiException $e) {
        $response = $this->apiExceptionHandler->handle($e);
        if ($response) return $response;
    }

    return $this->exportService->pdf(
        'exports/billetterie.html.twig',
        ['billetterie' => $billetterie, 'debut' => $debut, 'fin' => $fin],
        'billetterie-' . $debut . '-' . $fin . '.pdf'
    );
}

#[Route('/billetterie/export/excel', name: 'billetterie.export.excel', methods: ['GET'])]
public function billeterieExcel(Request $request): Response
{
    $debut = $request->query->get('debut', date('Y-m-01'));
    $fin   = $request->query->get('fin', date('Y-m-t'));

    try {
        $raw = $this->api->item('/api/stats/billetterie?' . http_build_query(['debut' => $debut, 'fin' => $fin]));

        $billetterie = [
            'totalTickets'      => $raw['totalTickets'] ?? 0,
            'recetteTotale'     => $raw['recetteTotale'] ?? 0,
            'recettesParJour'   => $raw['recettesParJour'] ?? [],
            'recettesParTrajet' => $raw['recettesParTrajet'] ?? [],
            'recettesParCar'    => $raw['recettesParCar'] ?? [],
        ];
    } catch (ApiException $e) {
        $response = $this->apiExceptionHandler->handle($e);
        if ($response) return $response;
    }

    $spreadsheet = $this->buildBilleterieSpreadsheet($billetterie, $debut, $fin);

    return $this->exportService->excel(
        $spreadsheet,
        'billetterie-' . $debut . '-' . $fin . '.xlsx'
    );
}

private function buildBilleterieSpreadsheet(array $billetterie, string $debut, string $fin): Spreadsheet
{
    $spreadsheet = new Spreadsheet();
    $periode     = $debut . ' au ' . $fin;

    // ── Feuille 1 : Récapitulatif ────────────────────────────
    $sheet = $spreadsheet->getActiveSheet()->setTitle('Récapitulatif');
    $this->exportService->titreRapport($sheet, 'Rapport Billetterie', $periode, 2);

    $sheet->setCellValue('A4', 'Indicateur');
    $sheet->setCellValue('B4', 'Valeur');
    $this->exportService->styleHeader($sheet, 'A4:B4');

    $kpis = [
        ['Total tickets', $billetterie['totalTickets']],
        ['Recette totale (FCFA)', $billetterie['recetteTotale']],
    ];

    foreach ($kpis as $i => [$label, $val]) {
        $row = 5 + $i;
        $sheet->setCellValue("A{$row}", $label);
        $sheet->setCellValue("B{$row}", $val);
        $this->exportService->styleZebra($sheet, "A{$row}:B{$row}", $i % 2 === 0);
    }

    $sheet->getColumnDimension('A')->setWidth(30);
    $sheet->getColumnDimension('B')->setWidth(20);

    // ── Feuille 2 : Par jour ─────────────────────────────────
    $sheet2 = $spreadsheet->createSheet()->setTitle('Par jour');
    $this->exportService->titreRapport($sheet2, 'Recettes par jour', $periode, 3);

    $sheet2->setCellValue('A4', 'Date');
    $sheet2->setCellValue('B4', 'Tickets');
    $sheet2->setCellValue('C4', 'Recette (FCFA)');
    $this->exportService->styleHeader($sheet2, 'A4:C4');

    foreach ($billetterie['recettesParJour'] as $i => $row) {
        $r = 5 + $i;
        $sheet2->setCellValue("A{$r}", $row['label']);
        $sheet2->setCellValue("B{$r}", $row['nbtickets']);
        $sheet2->setCellValue("C{$r}", $row['montant']);
        $this->exportService->styleZebra($sheet2, "A{$r}:C{$r}", $i % 2 === 0);
    }

    foreach (['A' => 18, 'B' => 12, 'C' => 20] as $col => $width) {
        $sheet2->getColumnDimension($col)->setWidth($width);
    }

    // ── Feuille 3 : Par trajet ───────────────────────────────
    $sheet3 = $spreadsheet->createSheet()->setTitle('Par trajet');
    $this->exportService->titreRapport($sheet3, 'Recettes par trajet', $periode, 3);

    $sheet3->setCellValue('A4', 'Trajet');
    $sheet3->setCellValue('B4', 'Tickets');
    $sheet3->setCellValue('C4', 'Recette (FCFA)');
    $this->exportService->styleHeader($sheet3, 'A4:C4');

    foreach ($billetterie['recettesParTrajet'] as $i => $row) {
        $r = 5 + $i;
        $sheet3->setCellValue("A{$r}", $row['trajet']);
        $sheet3->setCellValue("B{$r}", $row['nbtickets']);
        $sheet3->setCellValue("C{$r}", $row['montant']);
        $this->exportService->styleZebra($sheet3, "A{$r}:C{$r}", $i % 2 === 0);
    }

    foreach (['A' => 35, 'B' => 12, 'C' => 20] as $col => $width) {
        $sheet3->getColumnDimension($col)->setWidth($width);
    }

    // ── Feuille 4 : Par car ──────────────────────────────────
    $sheet4 = $spreadsheet->createSheet()->setTitle('Par car');
    $this->exportService->titreRapport($sheet4, 'Recettes par véhicule', $periode, 3);

    $sheet4->setCellValue('A4', 'Matricule');
    $sheet4->setCellValue('B4', 'Tickets');
    $sheet4->setCellValue('C4', 'Recette (FCFA)');
    $this->exportService->styleHeader($sheet4, 'A4:C4');

    foreach ($billetterie['recettesParCar'] as $i => $row) {
        $r = 5 + $i;
        $sheet4->setCellValue("A{$r}", $row['matricule']);
        $sheet4->setCellValue("B{$r}", $row['nbtickets']);
        $sheet4->setCellValue("C{$r}", $row['montant']);
        $this->exportService->styleZebra($sheet4, "A{$r}:C{$r}", $i % 2 === 0);
    }

    foreach (['A' => 20, 'B' => 12, 'C' => 20] as $col => $width) {
        $sheet4->getColumnDimension($col)->setWidth($width);
    }

    $spreadsheet->setActiveSheetIndex(0);

    return $spreadsheet;
}
```

---

### Template PDF — `templates/exports/billetterie.html.twig`

```twig
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111; padding: 20px 24px; }

        .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #111; padding-bottom: 10px; margin-bottom: 16px; }
        .company-name { font-size: 17px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; }
        .company-sub { font-size: 9px; color: #555; margin-top: 3px; }
        .doc-label { text-align: right; }
        .doc-label h1 { font-size: 13px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; border: 2px solid #111; padding: 4px 12px; display: inline-block; }
        .doc-label p { font-size: 9px; color: #666; margin-top: 3px; }

        .kpis { display: flex; gap: 10px; margin-bottom: 18px; }
        .kpi { flex: 1; border: 1px solid #ddd; border-radius: 3px; padding: 8px; text-align: center; }
        .kpi-value { font-size: 20px; font-weight: bold; display: block; }
        .kpi-label { font-size: 8.5px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }

        .section { margin-bottom: 18px; }
        .section-title { font-size: 9.5px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #111; padding-bottom: 3px; margin-bottom: 7px; }

        table { width: 100%; border-collapse: collapse; font-size: 9.5px; }
        thead tr { background: #1a1a1a; color: #fff; }
        thead th { padding: 5px 8px; text-align: left; font-size: 8.5px; font-weight: bold; text-transform: uppercase; }
        thead th.right { text-align: right; }
        tbody tr:nth-child(even) { background: #f7f7f7; }
        tbody tr:last-child td { border-bottom: 2px solid #111; }
        tbody td { padding: 4px 8px; border-bottom: 1px solid #e5e5e5; }
        tbody td.right { text-align: right; }

        .footer { margin-top: 14px; border-top: 1px solid #ddd; padding-top: 8px; display: flex; justify-content: space-between; font-size: 8.5px; color: #999; }
    </style>
</head>
<body>

    <div class="header">
        <div>
            <p class="company-name">Compagnie de Transport</p>
            <p class="company-sub">Rapport Billetterie — {{ debut }} au {{ fin }}</p>
        </div>
        <div class="doc-label">
            <h1>Billetterie</h1>
            <p>Généré le {{ "now"|date("d/m/Y à H:i") }}</p>
        </div>
    </div>

    <div class="kpis">
        <div class="kpi">
            <span class="kpi-value">{{ billetterie.totalTickets }}</span>
            <span class="kpi-label">Total tickets</span>
        </div>
        <div class="kpi">
            <span class="kpi-value">{{ billetterie.recetteTotale | number_format(0, ',', ' ') }} FCFA</span>
            <span class="kpi-label">Recette totale</span>
        </div>
    </div>

    <div class="section">
        <p class="section-title">Recettes par jour</p>
        {% if billetterie.recettesParJour is empty %}
            <p style="color:#aaa; font-style:italic;">Aucune donnée.</p>
        {% else %}
        <table>
            <thead><tr><th>Date</th><th class="right">Tickets</th><th class="right">Recette</th></tr></thead>
            <tbody>
                {% for r in billetterie.recettesParJour %}
                <tr>
                    <td>{{ r.label }}</td>
                    <td class="right">{{ r.nbtickets }}</td>
                    <td class="right">{{ r.montant | number_format(0, ',', ' ') }} FCFA</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% endif %}
    </div>

    <div class="section">
        <p class="section-title">Recettes par trajet</p>
        {% if billetterie.recettesParTrajet is empty %}
            <p style="color:#aaa; font-style:italic;">Aucune donnée.</p>
        {% else %}
        <table>
            <thead><tr><th>Trajet</th><th class="right">Tickets</th><th class="right">Recette</th></tr></thead>
            <tbody>
                {% for r in billetterie.recettesParTrajet %}
                <tr>
                    <td>{{ r.trajet }}</td>
                    <td class="right">{{ r.nbtickets }}</td>
                    <td class="right">{{ r.montant | number_format(0, ',', ' ') }} FCFA</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% endif %}
    </div>

    <div class="section">
        <p class="section-title">Recettes par véhicule</p>
        {% if billetterie.recettesParCar is empty %}
            <p style="color:#aaa; font-style:italic;">Aucune donnée.</p>
        {% else %}
        <table>
            <thead><tr><th>Matricule</th><th class="right">Tickets</th><th class="right">Recette</th></tr></thead>
            <tbody>
                {% for r in billetterie.recettesParCar %}
                <tr>
                    <td>{{ r.matricule }}</td>
                    <td class="right">{{ r.nbtickets }}</td>
                    <td class="right">{{ r.montant | number_format(0, ',', ' ') }} FCFA</td>
                </tr>
                {% endfor %}
            </tbody>
        </table>
        {% endif %}
    </div>

    <div class="footer">
        <span>Rapport Billetterie — {{ debut }} au {{ fin }}</span>
        <span>Généré le {{ "now"|date("d/m/Y à H:i") }}</span>
    </div>

</body>
</html>
```

---

### Boutons dans le template Billetterie

```twig
<div class="flex items-start justify-between gap-4 max-sm:flex-col mb-6">
    <div>
        <h1 class="text-4xl font-light tracking-[-0.55px] leading-[1.2]">Billetterie</h1>
    </div>
    <div class="flex items-center gap-2 flex-wrap shrink-0">
        <form method="get" action="{{ path('billetterie') }}" class="flex items-center gap-2">
            <input type="date" name="debut" value="{{ debut }}" class="input input-sm" />
            <span class="text-muted-foreground text-sm">→</span>
            <input type="date" name="fin" value="{{ fin }}" class="input input-sm" />
            <button type="submit" class="btn btn-primary btn-sm">Filtrer</button>
        </form>
        <a href="{{ path('billetterie.export.pdf', {debut: debut, fin: fin}) }}" target="_blank" class="btn btn-sm btn-outline">PDF</a>
        <a href="{{ path('billetterie.export.excel', {debut: debut, fin: fin}) }}" class="btn btn-sm btn-outline">Excel</a>
    </div>
</div>
```

Le pattern est posé — `ExportService` + méthode privée `buildXxxSpreadsheet` + template Twig pour le PDF. On applique la même structure pour tous les autres modules. On continue avec **Agents** ?








Le problème est bien identifié. Il faut un composant `RemoteSelect` qui fait de la recherche distante via l'API au lieu de charger toute la collection.

---

## Architecture

Trois cas d'usage à couvrir :
- Filtre dans `ServerDataTable` (type `remote_select`)
- Select dans formulaire Twig + vanilla JS
- Select dans composant React (TicketForm, etc.)

---

## 1. Hook `useRemoteSelect.ts`

```ts
// assets/react/hooks/useRemoteSelect.ts
import { useState, useEffect, useRef, useCallback } from 'react'

interface Option {
    value: string
    label: string
}

interface UseRemoteSelectOptions {
    endpoint: string           // ex: '/auth/search?apiPath=/api/fournisseurs'
    labelKey: string           // ex: 'nom', 'libelle'
    valueKey?: string          // défaut: 'id'
    minChars?: number          // défaut: 2
    debounce?: number          // défaut: 300ms
    initialValue?: string      // id sélectionné au chargement (pour edit)
    initialLabel?: string      // label affiché au chargement (pour edit)
    searchParam?: string       // param de recherche, défaut: 'search'
}

export function useRemoteSelect({
    endpoint,
    labelKey,
    valueKey = 'id',
    minChars = 2,
    debounce = 300,
    initialValue,
    initialLabel,
    searchParam = 'search',
}: UseRemoteSelectOptions) {
    const [query, setQuery]       = useState('')
    const [options, setOptions]   = useState<Option[]>([])
    const [loading, setLoading]   = useState(false)
    const [selected, setSelected] = useState<Option | null>(
        initialValue && initialLabel
            ? { value: initialValue, label: initialLabel }
            : null
    )
    const [open, setOpen] = useState(false)
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null)
    const abortRef = useRef<AbortController | null>(null)

    const search = useCallback(async (q: string) => {
        if (q.length < minChars) {
            setOptions([])
            return
        }

        // Annuler la requête précédente
        abortRef.current?.abort()
        abortRef.current = new AbortController()

        setLoading(true)
        try {
            const url = new URL(endpoint, window.location.origin)
            url.searchParams.set(searchParam, q)

            const res = await fetch(url.toString(), {
                signal: abortRef.current.signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            })
            const data = await res.json()

            // Supporte hydra:member et member
            const items = data['hydra:member'] ?? data['member'] ?? data ?? []
            setOptions(items.map((item: any) => ({
                value: String(item[valueKey]),
                label: item[labelKey],
            })))
        } catch (e: any) {
            if (e.name !== 'AbortError') setOptions([])
        } finally {
            setLoading(false)
        }
    }, [endpoint, labelKey, valueKey, minChars, searchParam])

    useEffect(() => {
        if (debounceRef.current) clearTimeout(debounceRef.current)
        debounceRef.current = setTimeout(() => search(query), debounce)
        return () => { if (debounceRef.current) clearTimeout(debounceRef.current) }
    }, [query, search, debounce])

    const select = (opt: Option) => {
        setSelected(opt)
        setQuery('')
        setOptions([])
        setOpen(false)
    }

    const clear = () => {
        setSelected(null)
        setQuery('')
        setOptions([])
    }

    return { query, setQuery, options, loading, selected, select, clear, open, setOpen }
}
```

---

## 2. Endpoint de recherche côté frontend Symfony

Un endpoint générique qui proxifie la recherche vers l'API backend :

```php
// src/Controller/SearchController.php
#[Route('/ressource/search', name: 'ressource.search', methods: ['GET'])]
public function search(Request $request): JsonResponse
{
    $apiPath = $request->query->get('apiPath');
    if (!$apiPath) {
        return $this->json(['error' => 'apiPath requis'], 400);
    }

    // Sécurité — whitelist des endpoints autorisés
    $allowed = [
        '/api/fournisseurs',
        '/api/personnels',
        '/api/cars',
        '/api/trajets',
        '/api/pieces',
        '/api/gares',
        '/api/voyages',
    ];

    if (!in_array($apiPath, $allowed, true)) {
        return $this->json(['error' => 'Endpoint non autorisé'], 403);
    }

    // Transmettre tous les query params sauf apiPath
    $params = $request->query->all();
    unset($params['apiPath']);

    try {
        $data = $this->api->get($apiPath, $params);
        return $this->json($data);
    } catch (ApiException $e) {
        return $this->json(['error' => $e->getMessage()], $e->getCode());
    }
}
```

---

## 3. Composant `RemoteSelect.tsx`

```tsx
// assets/react/components/ui/remote-select.tsx
import { useRef, useEffect } from 'react'
import { Loader2, X, Search } from 'lucide-react'
import { useRemoteSelect } from '../../hooks/useRemoteSelect'
import { cn } from '../../../lib/utils'

interface Props {
    // Config recherche
    apiPath: string          // ex: '/api/fournisseurs'
    labelKey: string         // ex: 'nom'
    valueKey?: string
    searchParam?: string     // param API Platform, ex: 'nom' (pour SearchFilter 'partial')
    minChars?: number

    // Valeur
    value?: string
    onChange: (value: string, label: string) => void
    onClear?: () => void

    // Affichage
    placeholder?: string
    initialLabel?: string    // label à afficher si value est déjà défini (mode edit)
    className?: string
    disabled?: boolean

    // Champ hidden pour form natif
    name?: string
}

export function RemoteSelect({
    apiPath,
    labelKey,
    valueKey = 'id',
    searchParam = 'search',
    minChars = 2,
    value,
    onChange,
    onClear,
    placeholder = 'Rechercher…',
    initialLabel,
    className,
    disabled,
    name,
}: Props) {
    const containerRef = useRef<HTMLDivElement>(null)

    const endpoint = `/ressource/search?apiPath=${encodeURIComponent(apiPath)}`

    const {
        query, setQuery,
        options, loading,
        selected, select, clear,
        open, setOpen,
    } = useRemoteSelect({
        endpoint,
        labelKey,
        valueKey,
        minChars,
        searchParam,
        initialValue: value,
        initialLabel,
    })

    // Fermer en cliquant dehors
    useEffect(() => {
        const handler = (e: MouseEvent) => {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false)
            }
        }
        document.addEventListener('mousedown', handler)
        return () => document.removeEventListener('mousedown', handler)
    }, [setOpen])

    const handleSelect = (opt: { value: string; label: string }) => {
        select(opt)
        onChange(opt.value, opt.label)
    }

    const handleClear = () => {
        clear()
        onClear?.()
        onChange('', '')
    }

    const displayValue = selected?.label ?? ''

    return (
        <div ref={containerRef} className={cn('relative', className)}>
            {/* Champ hidden pour form natif */}
            {name && <input type="hidden" name={name} value={selected?.value ?? ''} />}

            <div className={cn(
                'flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm',
                'ring-offset-background focus-within:ring-2 focus-within:ring-ring focus-within:ring-offset-2',
                disabled && 'opacity-50 pointer-events-none'
            )}>
                <Search className="h-4 w-4 text-muted-foreground shrink-0" />

                {selected && !open ? (
                    // Affichage de la valeur sélectionnée
                    <span
                        className="flex-1 cursor-pointer truncate"
                        onClick={() => { setOpen(true); setQuery('') }}
                    >
                        {displayValue}
                    </span>
                ) : (
                    <input
                        type="text"
                        value={query}
                        onChange={e => { setQuery(e.target.value); setOpen(true) }}
                        onFocus={() => setOpen(true)}
                        placeholder={selected ? displayValue : placeholder}
                        className="flex-1 bg-transparent outline-none placeholder:text-muted-foreground"
                        autoComplete="off"
                    />
                )}

                {loading && <Loader2 className="h-4 w-4 animate-spin text-muted-foreground shrink-0" />}

                {selected && (
                    <button
                        type="button"
                        onClick={handleClear}
                        className="text-muted-foreground hover:text-foreground shrink-0"
                    >
                        <X className="h-4 w-4" />
                    </button>
                )}
            </div>

            {/* Dropdown résultats */}
            {open && (query.length >= minChars) && (
                <div className={cn(
                    'absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-md',
                    'max-h-60 overflow-y-auto'
                )}>
                    {loading ? (
                        <div className="flex items-center gap-2 px-3 py-2 text-sm text-muted-foreground">
                            <Loader2 className="h-4 w-4 animate-spin" />
                            Recherche…
                        </div>
                    ) : options.length === 0 ? (
                        <div className="px-3 py-2 text-sm text-muted-foreground">
                            Aucun résultat
                        </div>
                    ) : (
                        options.map(opt => (
                            <button
                                key={opt.value}
                                type="button"
                                className={cn(
                                    'w-full text-left px-3 py-2 text-sm hover:bg-accent hover:text-accent-foreground',
                                    selected?.value === opt.value && 'bg-accent/50 font-medium'
                                )}
                                onMouseDown={e => e.preventDefault()} // évite blur avant click
                                onClick={() => handleSelect(opt)}
                            >
                                {opt.label}
                            </button>
                        ))
                    )}
                </div>
            )}
        </div>
    )
}
```

---

## 4. Intégration dans `ServerTableFilter`

### Nouveau type dans `useServerTable.ts`

```ts
export interface ServerTableFilter {
    type: 'text' | 'select' | 'date_range' | 'remote_select' // ← nouveau
    name: string
    label: string
    placeholder?: string
    options?: { label: string; value: string }[]
    // Config remote_select
    remote?: {
        apiPath: string
        labelKey: string
        valueKey?: string
        searchParam?: string
    }
}
```

### Dans `ServerDataTable.tsx`

```tsx
// Ajouter le cas remote_select dans le render des filtres
if (filter.type === 'remote_select' && filter.remote) {
    const currentId    = queryParams[filter.name] ?? ''
    const currentLabel = queryParams[`${filter.name}_label`] ?? ''

    return (
        <RemoteSelect
            key={filter.name}
            apiPath={filter.remote.apiPath}
            labelKey={filter.remote.labelKey}
            valueKey={filter.remote.valueKey}
            searchParam={filter.remote.searchParam}
            placeholder={filter.placeholder ?? filter.label}
            value={currentId}
            initialLabel={currentLabel}
            onChange={(val, label) => {
                navigate({
                    [filter.name]: val || null,
                    [`${filter.name}_label`]: label || null,
                    page: '1',
                })
            }}
            onClear={() => {
                navigate({
                    [filter.name]: null,
                    [`${filter.name}_label`]: null,
                    page: '1',
                })
            }}
        />
    )
}
```

> On stocke le label dans l'URL (`fournisseur_label=Dupont`) pour pouvoir le réafficher après un reload de page — sinon le `RemoteSelect` n'aurait que l'id sans savoir quoi afficher.

---

## 5. Utilisation dans `ApprovisionnementTable`

```tsx
// Avant
{
    type: 'select',
    name: 'fournisseur',
    label: 'Fournisseur',
    options: fournisseurs.map((f) => ({ value: `${f.id}`, label: f.libelle })),
},

// Après — plus besoin de passer fournisseurs en prop
{
    type: 'remote_select',
    name: 'fournisseur',
    label: 'Fournisseur',
    placeholder: 'Rechercher un fournisseur…',
    remote: {
        apiPath: '/api/fournisseurs',
        labelKey: 'nom',
        searchParam: 'nom',  // correspond au SearchFilter 'partial' sur 'nom'
    },
},
```

Et dans le controller, plus besoin de charger la collection :

```php
// Avant
'fournisseurs' => $this->api->collection('/api/fournisseurs')

// Après — supprimer complètement cette ligne
```

---

## 6. Pour les formulaires Twig (new/edit) — vanilla JS

Pour les `<select>` natifs dans les templates Twig, un web component léger sans React :

```js
// assets/js/remote-select.js
// Usage : <div data-remote-select data-api-path="/api/fournisseurs"
//              data-label-key="nom" data-search-param="nom"
//              data-name="fournisseur" data-initial-value="3" data-initial-label="Dupont SARL">
//         </div>

document.querySelectorAll('[data-remote-select]').forEach(container => {
    const { apiPath, labelKey, searchParam = 'search', name,
            initialValue, initialLabel } = container.dataset

    const endpoint = `/ressource/search?apiPath=${encodeURIComponent(apiPath)}`

    container.innerHTML = `
        <div class="relative">
            <input type="hidden" name="${name}" value="${initialValue ?? ''}">
            <div class="flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm">
                <input type="text" placeholder="Rechercher…" autocomplete="off"
                    class="flex-1 bg-transparent outline-none placeholder:text-muted-foreground remote-input"
                    value="${initialLabel ?? ''}">
            </div>
            <div class="remote-dropdown absolute z-50 mt-1 w-full rounded-md border bg-white shadow-md hidden max-h-60 overflow-y-auto"></div>
        </div>
    `

    const hiddenInput = container.querySelector('input[type="hidden"]')
    const textInput   = container.querySelector('.remote-input')
    const dropdown    = container.querySelector('.remote-dropdown')
    let debounce: any = null
    let abort: AbortController | null = null

    textInput.addEventListener('input', () => {
        const q = textInput.value.trim()
        if (q.length < 2) { dropdown.classList.add('hidden'); return }

        clearTimeout(debounce)
        debounce = setTimeout(async () => {
            abort?.abort()
            abort = new AbortController()

            const url = `${endpoint}&${searchParam}=${encodeURIComponent(q)}`
            const res  = await fetch(url, { signal: abort.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' } }).catch(() => null)
            if (!res) return

            const data  = await res.json()
            const items = data['hydra:member'] ?? data['member'] ?? []

            dropdown.innerHTML = items.length === 0
                ? '<div class="px-3 py-2 text-sm text-gray-500">Aucun résultat</div>'
                : items.map((item: any) => `
                    <button type="button" data-value="${item.id}" data-label="${item[labelKey]}"
                        class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100">
                        ${item[labelKey]}
                    </button>
                `).join('')

            dropdown.classList.remove('hidden')

            dropdown.querySelectorAll('button').forEach(btn => {
                btn.addEventListener('click', () => {
                    hiddenInput.value  = btn.dataset.value
                    textInput.value    = btn.dataset.label
                    dropdown.classList.add('hidden')
                })
            })
        }, 300)
    })

    // Fermer en cliquant dehors
    document.addEventListener('click', e => {
        if (!container.contains(e.target as Node)) dropdown.classList.add('hidden')
    })
})
```

Dans Twig :

```twig
{# new.html.twig / edit.html.twig — remplace le <select> #}
<div
    data-remote-select
    data-api-path="/api/fournisseurs"
    data-label-key="nom"
    data-search-param="nom"
    data-name="fournisseur"
    data-initial-value="{{ approvisionnement.fournisseur.id ?? '' }}"
    data-initial-label="{{ approvisionnement.fournisseur.nom ?? '' }}"
></div>
```

---

## Récap

| Contexte | Solution |
|---|---|
| Filtre `ServerDataTable` | `type: 'remote_select'` dans `ServerTableFilter` |
| Formulaire React | Composant `<RemoteSelect>` direct |
| Formulaire Twig natif | Web component vanilla JS `data-remote-select` |
| Backend | `SearchController::search()` proxy générique |
| Controller Symfony | Supprimer les `$this->api->collection(...)` pour les gros selects |







Le **9** est le plus simple des cinq — le taux de remplissage est déjà calculable depuis les données qu'on a. Il s'agit juste d'afficher une barre de progression sur le plan du car dans `TicketForm.tsx`.

Voici ce qu'on ajoute dans la `Card` du plan du véhicule, juste avant le `<CardContent>` :

```tsx
{/* Barre de remplissage — dans CardHeader, après CardDescription */}
{selectedVoyage && selectedVoyage.placestotal > 0 && (
    <div className="mt-3 space-y-1.5">
        {/* Chiffres */}
        <div className="flex items-center justify-between text-xs">
            <span className="text-muted-foreground">
                {selectedVoyage.placesoccupees} / {selectedVoyage.placestotal} places vendues
            </span>
            <span className={cn(
                "font-semibold tabular-nums",
                taux >= 90 ? "text-red-500" :
                taux >= 60 ? "text-amber-500" :
                "text-emerald-600"
            )}>
                {taux}%
            </span>
        </div>
        {/* Barre */}
        <div className="h-2 w-full rounded-full bg-zinc-100 overflow-hidden">
            <div
                className={cn(
                    "h-full rounded-full transition-all duration-500",
                    taux >= 90 ? "bg-red-500" :
                    taux >= 60 ? "bg-amber-400" :
                    "bg-emerald-500"
                )}
                style={{ width: `${taux}%` }}
            />
        </div>
    </div>
)}
```

La variable `taux` se calcule juste avant le `return` du composant :

```tsx
const taux = selectedVoyage && selectedVoyage.placestotal > 0
    ? Math.round((selectedVoyage.placesoccupees / selectedVoyage.placestotal) * 100)
    : 0
```

---

### Le point clé — mise à jour en temps réel

Le `taux` se base sur `selectedVoyage.placesoccupees` qui vient des props initiales chargées par Symfony. Après chaque vente, `loadSieges()` recharge le plan mais `placesoccupees` sur le voyage n'est pas mis à jour automatiquement puisqu'il vient de Twig.

Deux solutions :

**Option A — Simple** : incrémenter `placesoccupees` localement dans le state après chaque vente réussie, sans refetch du voyage.

**Option B — Propre** : après chaque vente, faire un fetch `/ticket/sieges/{voyageId}` qui retourne déjà `siegesGauche` et `siegesDroite` — étendre cette réponse pour inclure `placesoccupees` et `placestotal` à jour.

Je te recommande l'**Option B** car elle est déjà dans le flow existant. Voici les deux modifications :

**Dans `TicketController::sieges()`** — ajouter les places dans la réponse :

```php
return $this->json([
    'siegesGauche'   => $voyage['car']['siegesGauche'] ?? 2,
    'siegesDroite'   => $voyage['car']['siegesDroite'] ?? 2,
    'sieges'         => $sieges,
    'placestotal'    => $voyage['placestotal']    ?? 0,    // ← ajouter
    'placesoccupees' => $voyage['placesoccupees'] ?? 0,    // ← ajouter
]);
```

**Dans `TicketForm.tsx`** — mettre à jour le voyage local après `loadSieges` :

```tsx
// Étendre SiegesResponse
interface SiegesResponse {
    siegesGauche:    number
    siegesDroite:    number
    sieges:          Siege[]
    placestotal:     number     // ← ajouter
    placesoccupees:  number     // ← ajouter
}

// Ajouter un state pour les compteurs à jour
const [placesInfo, setPlacesInfo] = useState<{
    total: number
    occupees: number
} | null>(null)

// Dans loadSieges, après setSiegesData(data) :
setPlacesInfo({
    total:    data.placestotal,
    occupees: data.placesoccupees,
})

// Pour le calcul du taux, utiliser placesInfo en priorité :
const placesTotal    = placesInfo?.total    ?? selectedVoyage?.placestotal    ?? 0
const placesOccupees = placesInfo?.occupees ?? selectedVoyage?.placesoccupees ?? 0
const taux = placesTotal > 0 ? Math.round((placesOccupees / placesTotal) * 100) : 0
```

Ainsi après chaque vente, `loadSieges` recharge le plan **et** met à jour les compteurs — la barre reflète le vrai taux en temps réel sans recharger la page.

Les 5 fonctionnalités sont maintenant complètes. Tu veux qu'on passe à autre chose ?