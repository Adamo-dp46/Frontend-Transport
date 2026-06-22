<?php

namespace App\Controller;

use App\Controller\Trait\GareActionTrait;
use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Domain\Service\PdfService;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/bagage', name: 'bagage.')]
#[IsGranted('ROLE_USER')]
final class BagageController extends AbstractController
{
    use GareActionTrait;

    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper,
        private readonly PdfService $pdfService
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('BAGAGE_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/bagages', $request->query->all(),
            [
                'search' => 'codebagage',
                'voyage' => 'voyage.id',
                'statut' => 'statut'
            ],
            [
                'id',
                'codebagage',
                'poids',
                'montant',
                'statut',
                'createdAt'
            ],
            [
                'voyages' => $this->api->collection('/api/voyages?exists[datefin]=false')
            ]
        );

        return $this->render('bagage/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_VOIR')]
    public function show(int $id): Response
    {
        try {
            $bagage = $this->api->item('/api/bagages/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'bagage.index');
            if($response) {
                return $response;
            }
        }

        // Gare détentrice : origine si pas encore embarqué (ENREGISTRE), sinon gare de descente
        $detentriceId = ($bagage['statut'] ?? null) === 'ENREGISTRE'
            ? ($bagage['garedepart']['id'] ?? null)
            : ($bagage['garedescente']['id'] ?? null);
        $peutAgir = $this->peutAgirSurGare($detentriceId);

        return $this->render('bagage/show.html.twig', [
            'bagage' => $bagage,
            'peutAgir' => $peutAgir
        ]);
    }

    #[Route('/arrets/{id}', name: 'arrets', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_VOIR')]
    public function arrets(int $id): JsonResponse
    {
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $ligneId = $voyage['ligne']['id'] ?? null;
            if (!$ligneId) {
                return $this->json(['arrets' => []]); // voyage non rattaché à une ligne
            }
            $ligne = $this->api->item('/api/lignes/' . $ligneId);
            $arrets = array_map(fn($a) => [
                'id' => $a['gare']['id'],
                'libelle' => $a['gare']['libelle'],
                'ville' => $a['gare']['ville'] ?? null,
                'ordre' => $a['ordre'],
            ], $ligne['arrets'] ?? []);
            usort($arrets, fn($x, $y) => $x['ordre'] <=> $y['ordre']);
            return $this->json(['arrets' => $arrets]);
        } catch (ApiException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('BAGAGE_CREER')]
    public function new(Request $request): Response
    {
        $voyageId = $request->query->get('voyage');
        try {
            $voyages = $this->api->collection('/api/voyages?exists[datefin]=false');
            $tarifbagages = $this->api->collection('/api/tarifbagages');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'bagage.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $payload = $this->buildPayload($data);
            try {
                $this->api->post('/api/bagages', $payload);
                $this->addFlash('success', 'Le bagage a été enregistré avec succès');
                return $this->redirectToRoute('bagage.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'bagage.new');
                if($response) {
                    return $response;
                }
            }
        }

        $userGare = $this->getUser()->getGare();

        return $this->render('bagage/new.html.twig', [
            'voyages' => $voyages,
            'tarifbagages' => $tarifbagages,
            'voyageId' => $voyageId,
            'userGareId' => $userGare['id'] ?? null,
            'userGareLibelle' => $userGare['libelle'] ?? null
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $bagage = $this->api->item('/api/bagages/' . $id);
            $voyages = $this->api->collection('/api/voyages?exists[datefin]=false');
            $tarifbagages = $this->api->collection('/api/tarifbagages');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'bagage.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $payload = $this->buildPayload($data);
            try {
                $this->api->patch('/api/bagages/' . $id, $payload);
                $this->addFlash('success', 'Le bagage a été modifié avec succès');
                return $this->redirectToRoute('bagage.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'bagage.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        $userGare = $this->getUser()->getGare();

        return $this->render('bagage/edit.html.twig', [
            'voyages' => $voyages,
            'bagage' => $bagage,
            'tarifbagages' => $tarifbagages,
            'userGareId' => $userGare['id'] ?? null,
            'userGareLibelle' => $userGare['libelle'] ?? null
        ]);
    }

    #[Route('/{id}/perdu', name: 'perdu', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_MODIFIER')]
    public function perdu(int $id): Response
    {
        try {
            $this->api->patch('/api/bagages/' . $id . '/perdu');
            $this->addFlash('success', 'Le bagage a été déclaré perdu');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'bagage.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->redirectToRoute('bagage.show', ['id' => $id]);
    }

    #[Route('/{id}/print', name: 'print', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_VOIR')]
    public function print(int $id): Response
    {
        try {
            $bagage = $this->api->item('/api/bagages/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'bagage.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->pdfService->generate(
            'mails/bagage/ticket.html.twig',
            ['bagage' => $bagage],
            'ticket-' . ($bagage['codebagage'] ?? $id) . '.pdf'
        );
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('BAGAGE_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_bagage', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/bagages/' . $id . '/remove');
                $this->addFlash('success', 'Le bagage a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'bagage.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('bagage.index');
    }

    private function buildPayload(array $data): array
    {
        $payload = [
            'voyage' => !empty($data['voyage']) ? (int)$data['voyage'] : null,
            'garedepart' => !empty($data['garedepart']) ? (int)$data['garedepart'] : null,
            'garedescente' => !empty($data['garedescente']) ? (int)$data['garedescente'] : null,
            'nomclient' => $data['nomclient'] ?? '',
            'contactclient' => $data['contactclient'] ?? '',
            'nature' => $data['nature'] ?? '',
            'type' => $data['type'] ?? 'LEGER',
            'poids' => (int)($data['poids'] ?? 0)
        ];

        if(!empty($data['montant_force']) && !empty($data['montant'])) { /*
            - Le montant forcé uniquement si l'agent a coché la case et saisi un montant
        */
            $payload['montant'] = (int)$data['montant'];
        }

        return $payload;
    }
}
