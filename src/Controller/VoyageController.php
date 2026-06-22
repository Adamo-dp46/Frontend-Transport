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

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('VOYAGE_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/voyages',  $request->query->all(),
            [
                'search' => 'codevoyage',
                'ligne' => 'ligne.id',
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
                'lignes' => $this->api->collection('/api/lignes'),
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
            // itemsPerPage élevé : on veut TOUS les tickets du voyage (sinon paginés à 25).
            // statut=VALIDE : les billets désistés (reportés/annulés) ne comptent ni dans la recette ni dans l'occupation.
            $tickets = $this->api->collection('/api/tickets', ['voyage' => $id, 'statut' => 'VALIDE', 'itemsPerPage' => 500]);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        }

        // Calcul recette côté Symfony
        $recette = array_sum(array_column($tickets, 'prix'));
        $nbrTickets = count($tickets);
        $placestotal = (int)($voyage['placestotal'] ?? 0);

        // ── Occupation PAR TRONÇON ─────────────────────────────────────────
        // On récupère les arrêts ordonnés de la ligne pour situer chaque ticket
        $ordreParGare = [];
        $labelsParOrdre = []; // ordre => libellé gare
        try {
            if(!empty($voyage['ligne']['id'])) {
                $ligne = $this->api->item('/api/lignes/' . $voyage['ligne']['id']);
                foreach($ligne['arrets'] ?? [] as $a) {
                    $ordreParGare[$a['gare']['id']] = $a['ordre'];
                    $labelsParOrdre[$a['ordre']] = $a['gare']['libelle'];
                }
                ksort($labelsParOrdre);
            }
        } catch(ApiException) {
            // ligne indisponible -> repli plus bas
        }

        $segments = [];      // [{depart, arrivee, occupees, taux}]
        $picOccupation = 0;  // tronçon le plus chargé

        if(count($labelsParOrdre) >= 2) {
            $ordres = array_keys($labelsParOrdre);
            $maxOrdre = (int) end($ordres);
            $labels = array_values($labelsParOrdre);

            // Un ticket [montée, descente) couvre les segments montée..descente-1
            $occSegment = array_fill(0, $maxOrdre, 0);
            foreach($tickets as $t) {
                $m = $ordreParGare[$t['gare']['id'] ?? null] ?? null;
                $d = !empty($t['garedescente']['id'])
                    ? ($ordreParGare[$t['garedescente']['id']] ?? $maxOrdre)
                    : $maxOrdre; // ancien ticket sans descente = jusqu'au terminus
                if($m === null) {
                    continue;
                }
                for($i = $m; $i < $d; $i++) {
                    if(isset($occSegment[$i])) {
                        $occSegment[$i]++;
                    }
                }
            }

            for($i = 0; $i < $maxOrdre; $i++) {
                $occ = $occSegment[$i] ?? 0;
                $segments[] = [
                    'depart' => $labels[$i] ?? '?',
                    'arrivee' => $labels[$i + 1] ?? '?',
                    'occupees' => $occ,
                    'taux' => $placestotal > 0 ? (int) round(($occ / $placestotal) * 100) : 0,
                ];
                $picOccupation = max($picOccupation, $occ);
            }
        } else {
            // Pas d'info ligne (repli) : on compte les tickets
            $picOccupation = $nbrTickets;
        }

        $placesRestantes = max(0, $placestotal - $picOccupation);
        $tauxRemplissage = $placestotal > 0 ? (int) round(($picOccupation / $placestotal) * 100) : 0;

        return $this->render('voyage/show.html.twig', [
            'voyage' => $voyage,
            'recette' => $recette,
            'nbr_tickets' => $nbrTickets,
            'taux_remplissage' => $tauxRemplissage,
            'places_occupees' => $picOccupation,
            'places_restantes' => $placesRestantes,
            'segments' => $segments
        ]);
    }

    #[Route('/{id}/manifeste', name: 'manifeste', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('VOYAGE_VOIR')]
    public function manifeste(int $id): Response
    {
        try {
            $manifeste = $this->api->item('/api/voyages/' . $id . '/manifeste');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->render('voyage/manifeste.html.twig', [
            'm' => $manifeste,
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('VOYAGE_CREER')]
    public function new(Request $request): Response
    {
        try {
            $lignes = $this->api->collection('/api/lignes');
            $cars = $this->api->get('/api/cars', ['etat' => 'DISPONIBLE']);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'voyage.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(VoyageFormType::class, null, [
            'lignes' => $lignes,
            'cars' => $cars['member']
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            // provenance/destination sont dérivés de la ligne côté backend (VoyageProcessor)
            $payload = [
                'datedebut' => $form->get('datedebut')->getData()?->format('Y-m-d\TH:i:s.v\Z'),
                'ligne' => '/api/lignes/' . $form->get('ligne')->getData()
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
        try {
            $voyage = $this->api->item('/api/voyages/' . $id);
            $cars = $this->api->get('/api/cars', ['etat' => 'DISPONIBLE']);
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
            - Pré-remplissage ; provenance/destination dérivent de la ligne (non éditables)
        */
            'datedebut' => new \DateTimeImmutable($voyage['datedebut']),
            'car' => $voyage['car']['id'] ?? null
        ];

        $form = $this->createForm(VoyageEditFormType::class, $defaultData, [
            'cars' => $cars['member']
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
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

    #[Route('/{id}/receptionner', name: 'receptionner', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('ROLE_USER')]
    public function receptionner(int $id, Request $request): Response
    {
        if ($this->isCsrfTokenValid('receptionner_voyage', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/voyages/' . $id . '/receptionner');
                $this->addFlash('success', 'Voyage réceptionné à votre gare : les courriers et bagages qui y descendent ont été mis à jour');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'voyage.show', ['id' => $id]);
                if ($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('voyage.show', ['id' => $id]);
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
            $personnels = $this->api->collection('/api/personnels', ['statut' => 'ACTIF']);
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