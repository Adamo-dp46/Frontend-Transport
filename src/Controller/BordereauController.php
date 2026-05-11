<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Service\PdfService;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bordereau', name: 'bordereau.')]
#[IsGranted('ROLE_USER')]
final class BordereauController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly PdfService $pdfService
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    // #[IsGranted('VOYAGE_VOIR')]
    public function bordereau(Request $request): Response
    {
        $trajetId = $request->query->get('trajet');
        $gareId = $request->query->get('gare');
        $debut = $request->query->get('debut', date('Y-m-01'));
        $fin = $request->query->get('fin', date('Y-m-t'));

        try {
            $trajets = $this->api->collection('/api/trajets');
            $gares = $this->api->collection('/api/gares');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $voyages = [];
        if($trajetId) {
            try {
                $params = http_build_query(['debut' => $debut, 'fin' => $fin, 'itemsPerPage' => 100]);
                $result = $this->api->item('/api/trajets/' . $trajetId . '/voyages?' . $params);
                $voyages = $result['member'] ?? [];
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('bordereau/index.html.twig', [
            'trajets' => $trajets,
            'gares' => $gares,
            'voyages' => $voyages,
            'trajetId'  => $trajetId,
            'gareId' => $gareId,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/{voyageId}/bordereau/{gareId}', name: 'print', methods: ['GET'], requirements: ['voyageId' => '\d+', 'gareId' => '\d+'])]
    // #[IsGranted('VOYAGE_VOIR')]
    public function gare(int $voyageId, int $gareId): Response
    {
        try {
            $bordereau = $this->api->item('/api/voyages/' . $voyageId . '/bordereau?gare=' . $gareId);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        return $this->pdfService->generate(
            'mails/bordereau/bordereau.html.twig',
            [
                'bordereau' => $bordereau
            ],
            'bordereau-' . $bordereau['voyage']['codevoyage'] . '.pdf',
            'A4'
        );
    }

    #[Route('/{voyageId}/bordereau/chauffeur', name: 'chauffeur', methods: ['GET'], requirements: ['voyageId' => '\d+'])]
    #[IsGranted('VOYAGE_VOIR')]
    public function chauffeur(int $voyageId): Response
    {
        try {
            $bordereau = $this->api->item('/api/voyages/' . $voyageId . '/bordereau/chauffeur');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if ($response) return $response;
        }

        return $this->pdfService->generate(
            'mails/bordereau/bordereau_chauffeur.html.twig',
            [
                'bordereau' => $bordereau
            ],
            'bordereau-chauffeur-' . $bordereau['voyage']['codevoyage'] . '.pdf',
            'A4'
        );
    }
}
