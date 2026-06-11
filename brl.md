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
- dateembauche 'nullable' sur Personnel
- datecreation 'nullable' sur Gare
- On.. la liste des utilisateurs d'une gare sur la page show, faire le filtre gare dans le UserTable seulement pour l'admin de l'entreprise
- Pour le 'ApiUser' on reçois 'gare' nullable - libelle, ville, id
- On.. tester les actions par ressource en donnant les permissions






- Pour autoriser le bypass du `ROLE_ADMIN_GARE` on.. `ApiUser::hasPermission()` puis `PermissionVoter`











Maintenant la section Inventaire, Propriétaire et Personnel :Maintenant la section Administration — masquer Rôles & Permissions et Entreprise pour `ROLE_ADMIN_GARE` :Vérifions que le résultat est propre :Propre et complet. Voici le code final du `base.html.twig` pour la sidebar :

---

### `base.html.twig` — sidebar (extrait modifié)

```twig
{% set isAdminGare = is_granted('ROLE_ADMIN_GARE') %} {#
    - On calcule une seule fois pour toute la sidebar.
      ROLE_ADMIN_GARE bypass le PermissionVoter donc sans cette variable
      il verrait toute la sidebar comme un ROLE_ADMIN.
#}

{# Tableau de bord — visible pour tous sauf ROLE_SUPER_ADMIN #}
{% if not is_granted('ROLE_SUPER_ADMIN') %}
<a href="{{ path('home') }}" ...>Tableau de bord</a>
{% endif %}

{# Stock & Approvisionnement — masqué pour ROLE_ADMIN_GARE #}
{% if not isAdminGare %}
    <button data-submenu="sub-stock">Stock & Approvisionnement</button>
    <div class="submenu" id="sub-stock">...</div>
{% endif %}

{# Flotte & Maintenance — masqué pour ROLE_ADMIN_GARE #}
{% if not isAdminGare %}
    <button data-submenu="sub-flotte">Flotte & Maintenance</button>
    <div class="submenu" id="sub-flotte">...</div>
{% endif %}

{# Exploitation — visible mais items filtrés #}
<button data-submenu="sub-exploitation">Exploitation</button>
<div class="submenu" id="sub-exploitation">
    {# Gares, Tarifs de trajet, Tarif de bagage, Trajets : masqués pour ROLE_ADMIN_GARE #}
    {% if not isAdminGare and is_granted('GARE_VOIR') %}..Gares..{% endif %}
    {% if not isAdminGare and is_granted('TARIF_VOIR') %}..Tarifs..{% endif %}
    {% if not isAdminGare and is_granted('TARIFBAGAGE_VOIR') %}..Tarif bagage..{% endif %}
    {% if not isAdminGare and is_granted('TRAJET_VOIR') %}..Trajets..{% endif %}
    {# Voyages et Bagages : visibles pour ROLE_ADMIN_GARE #}
    {% if is_granted('VOYAGE_VOIR') %}..Voyages..{% endif %}
    {% if is_granted('BAGAGE_VOIR') %}..Bagage..{% endif %}
</div>

{# Billetterie — entièrement visible pour ROLE_ADMIN_GARE #}
<button data-submenu="sub-billetterie">Billetterie</button>
<div class="submenu" id="sub-billetterie">...</div>

{# Courrier — visible mais Tarif courrier masqué pour ROLE_ADMIN_GARE #}
<button data-submenu="sub-courrier">Courrier</button>
<div class="submenu" id="sub-courrier">
    {% if not isAdminGare and is_granted('TARIFCOURRIER_VOIR') %}..Tarif courrier..{% endif %}
    {% if is_granted('COURRIER_VOIR') %}..Courrier..{% endif %}
</div>

{# Inventaire — masqué pour ROLE_ADMIN_GARE #}
{% if not isAdminGare and is_granted('INVENTAIRE_VOIR') %}..Inventaire..{% endif %}

{# Propriétaire — masqué pour ROLE_ADMIN_GARE #}
{% if is_granted('ROLE_ADMIN') and not isAdminGare %}..Propriétaire..{% endif %}

{# Personnel — masqué pour ROLE_ADMIN_GARE #}
{% if not isAdminGare %}
    <button data-submenu="sub-personnel">Personnel</button>
    <div class="submenu" id="sub-personnel">...</div>
{% endif %}

{# Administration — items filtrés #}
<button data-submenu="sub-admin">Administration</button>
<div class="submenu" id="sub-admin">
    {# Rôles & Permissions : masqué pour ROLE_ADMIN_GARE #}
    {% if not isAdminGare and is_granted('ROLE_VOIR') %}..Rôles..{% endif %}
    {# Utilisateurs : visible pour ROLE_ADMIN_GARE #}
    {% if is_granted('USER_VOIR') or is_granted('ROLE_SUPER_ADMIN') %}..Utilisateurs..{% endif %}
    {# Entreprise : masqué pour ROLE_ADMIN_GARE #}
    {% if not isAdminGare %}
        {% if is_granted('ROLE_SUPER_ADMIN') %}..Entreprises..{% endif %}
        {% if is_granted('ROLE_ADMIN') %}..Entreprise..{% endif %}
    {% endif %}
</div>
```

---

## Récapitulatif Étape 4 ✅

Un seul `{% set isAdminGare %}` calculé en tête de sidebar, utilisé partout — zéro appel `is_granted` dupliqué.

**Ce que voit un `ROLE_ADMIN_GARE` :** Tableau de bord, Exploitation (Voyages + Bagage uniquement), Billetterie, Courrier (sans les tarifs), Administration (Utilisateurs uniquement).

**Ce qu'il ne voit pas :** Stock, Flotte, Inventaire, Personnel, Propriétaire, Gares/Tarifs/Trajets, Rôles & Permissions, Entreprise.

---

On a couvert toutes les étapes prévues (1, 3, 4 — l'étape 2 GareScopeExtension étant reportée). Tu veux qu'on attaque autre chose ?


- Si l'utilisateur est lié à une gare on empêche qu'il soit admin entreprise









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