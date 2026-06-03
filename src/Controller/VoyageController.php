<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Form\VoyageAffectationCarFormType;
use App\Form\VoyageAffectationPersonnelFormType;
use App\Form\VoyageclotureFormType;
use App\Form\VoyageEditFormType;
use App\Form\VoyageFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/voyage', name: 'voyage.')]
#[IsGranted('ROLE_USER')]
final class VoyageController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper
    )
    {
    }

    private function resolveGareLibelle(array $gares, mixed $gareId): string
    {
        foreach($gares as $g) {
            if((string) $g['id'] === (string) $gareId) {
                return $g['libelle'];
            }
        }
        return '';
    }

    private function resolveGareId(array $gares, string $libelle): ?int
    {
        foreach($gares as $g) {
            if($g['libelle'] === $libelle) {
                return (int) $g['id'];
            }
        }
        return null;
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('VOYAGE_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/voyages',  $request->query->all(),
            [
                'search' => 'codevoyage',
                'trajet' => 'trajet.id',
                'car' => 'car.id',
                'date_from' => 'datedebut[after]',
                'date_to' => 'datedebut[before]',
            ],
            [
                'id',
                'provenance',
                'destination',
                'datedebut',
                'placestotal',
                'createdAt'
            ],
            [
                'trajets' => $this->api->collection('/api/trajets'),
                'cars' => $this->api->collection('/api/cars')
            ]
        );

        return $this->render('voyage/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_VOIR')]
    public function show(int $id): Response
    {
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $tickets = $this->api->collection('/api/tickets?voyage=' . $id); // Ou.. 'urlencode('/api/voyages/' . $id)'
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        }

        // Calcul recette côté Symfony
        $recette = array_sum(array_column($tickets, 'prix'));
        $nbrTickets = count($tickets);
        $tauxRemplissage = $voyage['placestotal'] > 0 ? round(($voyage['placesoccupees'] / $voyage['placestotal']) * 100) : 0;

        return $this->render('voyage/show.html.twig', [
            'voyage' => $voyage,
            'recette' => $recette,
            'nbr_tickets' => $nbrTickets,
            'taux_remplissage' => $tauxRemplissage
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('VOYAGE_CREER')]
    public function new(Request $request): Response
    {
        try {
            $gares = $this->api->collection('/api/gares');
            $trajets = $this->api->collection('/api/trajets');
            $cars = $this->api->get('/api/cars', ['etat' => 'DISPONIBLE']);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'trajet.index');
            if($response) {
                return $response;
            }
        }

        $refs = [
            'gares' => $gares,
            'trajets' => $trajets,
            'cars' => $cars['member']
        ];
        $form = $this->createForm(VoyageFormType::class, null, $refs);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'provenance' => $this->resolveGareLibelle($refs['gares'], $form->get('provenance')->getData()),
                'destination' => $this->resolveGareLibelle($refs['gares'], $form->get('destination')->getData()),
                'datedebut' => $form->get('datedebut')->getData()?->format('Y-m-d\TH:i:s.v\Z'),
                'trajet' => '/api/trajets/' . $form->get('trajet')->getData()
            ];

            $carId = $form->get('car')->getData();
            if($carId) {
                $payload['car'] = '/api/cars/' . $carId;
            }

            try {
                $this->api->post('/api/voyages', $payload);
                $this->addFlash('success', 'Le voyage a été créé avec succès');
                return $this->redirectToRoute('voyage.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'voyage.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('voyage/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        $cars = [];
        $gares = [];
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $cars = $this->api->get('/api/cars', ['etat' => 'DISPONIBLE']);
            $gares = $this->api->collection('/api/gares');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        } /*
            - On a vérifié si le voyage est terminé au niveau du backend
        */
        if(!empty($voyage['car'])) { /*
            - On ajoute le car actuel du voyage s'il existe et n'est pas déjà dans la liste
        */
            $currentCarId = $voyage['car']['id'];
            $alreadyInList = array_filter($cars['member'], fn($c) => $c['id'] === $currentCarId);
            if(empty($alreadyInList)) {
                $currentCar = $this->api->item('/api/cars/' . $currentCarId);
                array_unshift($cars['member'], $currentCar); // On le met en 1er
            }
        }
        $defaultData = [ /*
            - Pour pré-remplir avec les valeurs actuelles
        */
            'provenance' => $this->resolveGareId($gares, $voyage['provenance'] ?? ''),
            'destination' => $this->resolveGareId($gares, $voyage['destination'] ?? ''),
            'datedebut' => new \DateTimeImmutable($voyage['datedebut']),
            'car' => $voyage['car']['id'] ?? null
        ];

        $form = $this->createForm(VoyageEditFormType::class, $defaultData, [
            'cars' => $cars['member'],
            'gares' => $gares
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $payload = [
                'provenance' => $this->resolveGareLibelle($gares, (int)$data['provenance']),
                'destination' => $this->resolveGareLibelle($gares, (int)$data['destination']),
                'datedebut' => $form->get('datedebut')->getData()?->format('Y-m-d\TH:i:s.v\Z')
            ];
            $carId = $form->get('car')->getData();
            if($carId) {
                $payload['car'] = '/api/cars/' . $carId;
            } /* - Ou..
                if(!empty($data['car'])) {
                    $payload['car'] = '/api/cars/' . (int) $data['car'];
                } else {
                    $payload['car'] = null;
                }
            */
            try {
                $this->api->patch('/api/voyages/' . $id, $payload);
                $this->addFlash('success', 'Le voyage a été modifié avec succès');
                return $this->redirectToRoute('voyage.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'voyage.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('voyage/edit.html.twig', [
            'form' => $form,
            'voyage' => $voyage
        ]);
    }

    #[Route('/{id}/cloturer', name: 'cloturer', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_MODIFIER')]
    public function cloturer(int $id, Request $request): Response
    {
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        } /*
            - On a vérifié si le voyage est terminé au niveau du backend
        */
        $form = $this->createForm(VoyageclotureFormType::class);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'datefin' => $form->get('datefin')->getData()?->format('Y-m-d\TH:i:s.v\Z')
            ];
            try {
                $this->api->patch('/api/voyages/' . $id, $payload);
                $this->addFlash('success', 'Le voyage a été clôturé avec succès');
                return $this->redirectToRoute('voyage.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'voyage.cloturer', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('voyage/cloture.html.twig', [
            'form' => $form,
            'voyage' => $voyage
        ]);
    }

    #[Route('/{id}/car', name: 'affect.car', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_MODIFIER')]
    public function car(int $id, Request $request): Response
    {
        $cars = [];
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $cars = $this->api->get('/api/cars', ['etat' => 'DISPONIBLE']);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(VoyageAffectationCarFormType::class, null, [
            'cars' => $cars['member']
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = ['car' => '/api/cars/' . $form->get('car')->getData()];
            try {
                $this->api->patch('/api/voyages/' . $id . '/car', $payload);
                $this->addFlash('success', 'Le véhicule a été affecté avec succès');
                return $this->redirectToRoute('voyage.show', ['id' => $id]);
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'voyage.index');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('voyage/car.html.twig', [
            'form' => $form,
            'voyage' => $voyage
        ]);
    }

    #[Route('/{id}/personnel', name: 'affect.personnel', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_MODIFIER')]
    public function personnel(int $id, Request $request): Response
    {
        $personnels = [];
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $personnels = $this->api->collection('/api/personnels');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(VoyageAffectationPersonnelFormType::class, null, [
            'personnels' => $personnels,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'personnel' => $form->get('personnel')->getData(),
                'motif' => $form->get('motif')->getData()
            ];
            try {
                $this->api->patch('/api/voyages/' . $id . '/personnel', $payload);
                $this->addFlash('success', 'Le personnel a été affecté avec succès');
                return $this->redirectToRoute('voyage.show', ['id' => $id]);
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'voyage.index');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('voyage/personnel.html.twig', [
            'form' => $form,
            'voyage' => $voyage
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_voyage', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/voyages/' . $id . '/remove');
                $this->addFlash('success', 'Le voyage a été supprimé avec succès');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('voyage.index');
    }
}