<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Form\TrajetEditFormType;
use App\Form\TrajetFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/trajet', name: 'trajet.')]
#[IsGranted('ROLE_USER')]
final class TrajetController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    /**
     * Le select renvoie l'ID de la gare.
     * L'API attend le libellé (string) → on résout ici.
     */
    private function resolveGareLibelle(array $gares, mixed $gareId): string
    {
        foreach($gares as $g) {
            if((string) $g['id'] === (string) $gareId) {
                return $g['libelle'];
            }
        }
        return '';
    }

    /**
     * En édition, l'API renvoie le libellé stocké.
     * On retrouve l'ID correspondant pour pré-sélectionner le bon <option>.
     */
    private function resolveGareId(array $gares, string $libelle): ?int
    {
        foreach($gares as $g) {
            if(strtolower($g['libelle']) === strtolower($libelle)) {
                return (int)$g['id'];
            }
        }
        return null;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('TRAJET_VOIR')]
    public function index(): Response
    {
        try {
            $trajets = $this->api->collection('/api/trajets');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        return $this->render('trajet/index.html.twig', [
            'trajets' => $trajets
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TRAJET_VOIR')]
    public function show(int $id, Request $request, TableHelper $tableHelper): Response
    {
        try {
            $trajet = $this->api->item('/api/trajets/' . $id);
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'trajet.index');
            if($response) {
                return $response;
            }
        }

        $voyages = $tableHelper->handleRelated(
            endpoint: '/api/voyages',
            queryParams: $request->query->all(),
            fixedFilters: ['trajet.id' => $id],
            allowedFilters: [],
            allowedSorts: [
                'id',
                'provenance',
                'destination',
                'datedebut',
                'placestotal',
                'createdAt'
            ],
            defaultPerPage: 25,
        );

        return $this->render('trajet/show.html.twig', [
            'trajet' => $trajet,
            'voyages' => $voyages['items'],
            'voyagesMeta' => $voyages['meta'],
            'voyagesParams' => $voyages['queryParams']
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('TRAJET_CREER')]
    public function new(Request $request): Response
    {
        try {
            $gares = $this->api->collection('/api/gares');
            $tarifs = $this->api->collection('/api/tarifs');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'trajet.index');
            if($response) {
                return $response;
            }
        }

        $refs = [
            'gares' => $gares,
            'tarifs' => $tarifs
        ];
        $form = $this->createForm(TrajetFormType::class, null, $refs);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'provenance'  => $this->resolveGareLibelle($refs['gares'], $form->get('provenance')->getData()),
                'destination' => $this->resolveGareLibelle($refs['gares'], $form->get('destination')->getData()),
                'tarifId' => $form->get('tarifId')->getData()
                // 'datedebut' => $form->get('datedebut')->getData()?->format('Y-m-d\TH:i:s.v\Z')
            ];
            /*
                $carId = $form->get('carId')->getData();
                if($carId) {
                    $payload['carId'] = $carId;
                }
            */
            try {
                $this->api->post('/api/trajets', $payload);
                $this->addFlash('success', 'Le trajet a été créé avec succès');
                return $this->redirectToRoute('trajet.index');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'trajet.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('trajet/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TRAJET_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        $tarifs = [];
        try {
            $trajet = $this->api->item('/api/trajets/' . $id);
            // $gares = $this->api->collection('/api/gares');
            $tarifs = $this->api->collection('/api/tarifs');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'trajet.index');
            if($response) {
                return $response;
            }
        }

        // Pré-remplir le formulaire avec les valeurs actuelles du trajet
        // On résout les libellés → IDs de gare pour pré-sélectionner les selects
        $defaultData = [
            // 'provenance' => $this->resolveGareId($gares, $trajet['provenance'] ?? ''),
            // 'destination' => $this->resolveGareId($gares, $trajet['destination'] ?? ''),
            'tarifId' => $trajet['tarif']['id'] ?? null
        ];

        $form = $this->createForm(TrajetEditFormType::class, $defaultData, [
            // 'gares' => $gares,
            'tarifs' => $tarifs
        ]);

        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $payload = [
                    // 'provenance' => $this->resolveGareLibelle($gares, (int) $data['provenance']),
                    // 'destination' => $this->resolveGareLibelle($gares, (int) $data['destination']),
                    'tarif' => '/api/tarifs/' . (int)$data['tarifId']
                ];
                $this->api->patch('/api/trajets/' . $id, $payload);
                $this->addFlash('success', 'Trajet modifié avec succès');
                return $this->redirectToRoute('trajet.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'trajet.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('trajet/edit.html.twig', [
            'form' => $form,
            'trajet' => $trajet
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TRAJET_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_trajet', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/trajets/' . $id . '/remove');
                $this->addFlash('success', 'Le trajet a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'trajet.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('trajet.index');
    }
}
