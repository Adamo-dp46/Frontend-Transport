<?php

namespace App\Domain\Service;

use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfService
{
    public function __construct(
        private readonly Environment $twig
    )
    {
    }

    public function generate(string $template, array $data, string $filename = 'ticket.pdf', string $format = 'thermal')
    {
        $html = $this->twig->render($template, $data);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);

        // Format rouleau thermique : 80mm de large, hauteur auto
        // DOMPDF ne supporte pas 'auto' en hauteur → on estime 160mm
        // pour un ticket standard (ajuster si le contenu déborde)
        // $dompdf->setPaper([0, 0, 226.77, 453.54], 'portrait'); // 80mm × 160mm en points

        $paper = match($format) {
            'A4' => 'A4',
            'A4-land' => [0, 0, 841.89, 595.28],
            default => [0, 0, 226.77, 453.54], // thermique 80mm x 160mm
        };
        $dompdf->setPaper($paper, 'portrait');
        $dompdf->render();
        $pdfContent = $dompdf->output();

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
            'Content-Length' => strlen($pdfContent)
        ]);
    }

    /**
     * Permet de forçer le téléchargement du pdf
     */
    public function download(string $template, array $data, string $filename = 'rapport.pdf'): Response
    {
        $response = $this->generate($template, $data, $filename);
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');
        return $response;
    }
}