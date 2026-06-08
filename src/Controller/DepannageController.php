<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Form\DepannageAffectationFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/depannage', name: 'depannage.')]
#[IsGranted('ROLE_USER')]
final class DepannageController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('DEPANNAGE_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/depannages',  $request->query->all(),
            [
                'search' => 'lieudepannage',
                'car' => 'car.id',
                'date_from' => 'datedepannage[after]',
                'date_to' => 'datedepannage[before]'
            ],
            [
                'id',
                'datedepannage',
                'lieudepannage',
                'couttotal',
                'createdAt'
            ],
            [
                'cars' => $this->api->collection('/api/cars')
            ]
        );

        return $this->render('depannage/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('DEPANNAGE_VOIR')]
    public function show(int $id): Response
    {
        try {
            $depannage = $this->api->item('/api/depannages/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'depannage.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('depannage/show.html.twig', [
            'depannage' => $depannage
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('DEPANNAGE_CREER')]
    public function new(Request $request): Response
    {
        try {
            $cars = $this->api->collection('/api/cars');
            $pieces = $this->api->collection('/api/pieces');
            $typepannes = $this->api->collection('/api/typepannes');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'depannage.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $details = [];
            $pieceIds = $data['detail_piece']    ?? [];
            $quantites = $data['detail_quantite'] ?? [];

            foreach($pieceIds as $i => $pieceId) {
                if(!empty($pieceId)) {
                    $details[] = [
                        'piece'    => $pieceId,
                        'quantite' => $quantites[$i] ?? 1,
                    ];
                }
            }

            if(empty($details)) {
                $this->addFlash('danger', 'Veuillez ajouter au moins une pièce');
            } else {
                $payload = [
                    'lieudepannage' => $data['lieudepannage'] ?? '',
                    'description' => $data['description']   ?? '',
                    'car' => (int)($data['car'] ?? 0),
                    'typepanne' => (int)($data['typepanne'] ?? 0),
                    'details' => $details
                ];
                try {
                    $this->api->post('/api/depannages', $payload);
                    $this->addFlash('success', 'Le dépannage a été enregistré avec succès');
                    return $this->redirectToRoute('depannage.index');
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, null, 'depannage.new');
                    if($response) {
                        return $response;
                    }
                }
            }
        }

        return $this->render('depannage/new.html.twig', [
            'cars' => $cars,
            'pieces' => $pieces,
            'typepannes' => $typepannes
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('DEPANNAGE_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $depannage  = $this->api->item('/api/depannages/' . $id);
            $cars = $this->api->collection('/api/cars');
            $pieces = $this->api->collection('/api/pieces');
            $typepannes = $this->api->collection('/api/typepannes');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'depannage.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $details = [];
            $pieceIds = $data['detail_piece']    ?? [];
            $quantites = $data['detail_quantite'] ?? [];

            foreach($pieceIds as $i => $pieceId) {
                if(!empty($pieceId)) {
                    $details[] = [
                        'piece' => $pieceId,
                        'quantite' => $quantites[$i] ?? 1
                    ];
                }
            }

            if(empty($details)) {
                $this->addFlash('danger', 'Veuillez ajouter au moins une pièce');
            } else {
                $payload = [
                    'lieudepannage' => $data['lieudepannage'] ?? '',
                    'description' => $data['description'] ?? '',
                    'car' => (int)($data['car'] ?? 0),
                    'typepanne' => (int)($data['typepanne'] ?? 0),
                    'details' => $details
                ];
                try {
                    $this->api->patch('/api/depannages/' . $id, $payload);
                    $this->addFlash('success', 'Le dépannage a été modifié avec succès');
                    return $this->redirectToRoute('depannage.index');
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, null, 'depannage.edit', ['id' => $id]);
                    if($response) {
                        return $response;
                    }
                }
            }
        }

        return $this->render('depannage/edit.html.twig', [
            'depannage' => $depannage,
            'cars' => $cars,
            'pieces' => $pieces,
            'typepannes' => $typepannes
        ]);
    }

    #[Route('/{id}/affecter/personnel', name: 'affect.personnel', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('DEPANNAGE_MODIFIER')]
    public function personnel(int $id, Request $request): Response
    {
        $personnels = [];
        try {
            $depannage = $this->api->item('/api/depannages/' . $id);
            $personnels = $this->api->collection('/api/personnels', ['statut' => 'ACTIF']);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'depannage.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(DepannageAffectationFormType::class, null, [
            'personnels' => $personnels,
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'personnel' => $form->get('personnel')->getData(),
                'motif' => $form->get('motif')->getData()
            ];
            try {
                $this->api->patch('/api/depannages/' . $id . '/personnel', $payload);
                $this->addFlash('success', 'Le personnel a été affecté avec succès');
                return $this->redirectToRoute('depannage.show', ['id' => $id]);
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'depannage.index');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('depannage/personnel.html.twig', [
            'form' => $form,
            'depannage' => $depannage
        ]);
    }

    #[Route('/{id}/cloturer', name: 'cloturer', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('DEPANNAGE_MODIFIER')]
    public function cloturer(int $id): Response
    {
        try {
            $this->api->patch('/api/depannages/' . $id . '/cloturer');
            $this->addFlash('success', 'Le dépannage a été clôturé avec succès');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'depannage.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }
        return $this->redirectToRoute('depannage.index');
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('DEPANNAGE_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_depannage', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/depannages/' . $id . '/remove');
                $this->addFlash('success', 'Le dépannage a été supprimé avec succès');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'depannage.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('depannage.index');
    }
}