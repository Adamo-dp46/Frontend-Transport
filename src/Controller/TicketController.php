<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Domain\Service\PdfService;
use App\Form\TicketEditFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/ticket', name: 'ticket.')]
#[IsGranted('ROLE_USER')]
final class TicketController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly PdfService $pdfService,
        private readonly TableHelper $tableHelper
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('TICKET_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/tickets',  $request->query->all(),
            [
                'search' => 'codeticket',
                'voyage' => 'voyage.id'
            ],
            [
                'id',
                'codeticket',
                'prix',
                'createdAt'
            ],
            [
                'voyages' => $this->api->collection('/api/voyages?exists[datefin]=false')
            ]
        );

        return $this->render('ticket/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_VOIR')]
    public function show(int $id): Response
    {
        try {
            $ticket = $this->api->item('/api/tickets/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'ticket.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('ticket/show.html.twig', [
            'ticket' => $ticket
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('TICKET_CREER')]
    public function new(Request $request): Response
    {
        if($request->isMethod('GET')) {
            $voyages = $this->api->collection('/api/voyages?exists[datefin]=false');
            $gares = $this->api->collection('/api/gares');

            return $this->render('ticket/new.html.twig', [
                'voyages' => $voyages,
                'gares' => $gares,
                'preselect_voyage' => $request->query->getInt('voyage')
            ]);
        }

        // POST : payload = tableau de tickets (vente groupée)
        // POST - ajouter gare dans le payload de chaque ticket
        try {
            $payload = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $this->json(['detail' => 'Corps de requête JSON invalide'], 400);
        }

        // payload attendu : { tickets: [{ voyage, siege, nomclient, contactclient }] }
        $tickets = $payload['tickets'] ?? [];
        if (empty($tickets)) {
            return $this->json(['detail' => 'Aucun ticket à créer'], 422);
        }

        $created = [];
        $errors  = [];
        foreach($tickets as $index => $t) {
            if(empty($t['voyage']) || empty($t['siege']) || empty($t['gare'])) {
                $errors[] = "Ticket #" . ($index + 1) . " : voyage, siège et gare obligatoires";
                continue;
            }
            try {
                $ticket = $this->api->post('/api/tickets', [
                    'voyage' => $t['voyage'], // IRI /api/voyages/{id}
                    'siege' => $t['siege'], // IRI /api/sieges/{id}
                    'gare' => $t['gare'],      // ← nouveau IRI
                    'nomclient' => $t['nomclient']     ?? null,
                    'contactclient' => $t['contactclient'] ?? null
                ]);
                $created[] = $ticket['id'];
            } catch (ApiException $e) {
                $errors[] = "Siège #" . ($index + 1) . " : " . $e->getMessage();
            }
        }

        if (!empty($errors) && empty($created)) {
            return $this->json(['detail' => implode(' | ', $errors)], 422);
        }

        return $this->json(['created' => $created, 'errors' => $errors]);
    }

    #[Route('/sieges/{id}', name: 'sieges', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_VOIR')]
    public function sieges(int $id): JsonResponse
    {
        try {
            // 1. Charger le voyage pour récupérer le car
            $voyage = $this->api->item('/api/voyages/' . $id);

            $carId = $voyage['car']['id'] ?? null;

            if (!$carId) {
                return $this->json([
                    'siegesGauche' => 0,
                    'siegesDroite' => 0,
                    'sieges' => []
                ]);
            }

            // 2. Appeler le SiegeStateProvider : sièges du car + statut pour ce voyage
            $sieges = $this->api->collection(
                '/api/sieges?car=' . urlencode('/api/cars/' . $carId)
                . '&voyage=' . $id
            );

            return $this->json([
                'siegesGauche' => $voyage['car']['siegesGauche'] ?? 2,
                'siegesDroite' => $voyage['car']['siegesDroite'] ?? 2,
                'sieges'       => $sieges,
            ]);

        } catch (ApiException $e) {
            return $this->json(
                ['error' => 'Impossible de charger les sièges : ' . $e->getMessage()],
                $e->getCode() ?: 500
            );
        }
    }

    #[Route('/{id}/json', name: 'json', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_VOIR')]
    public function jsons(int $id): JsonResponse
    {
        try {
            $ticket = $this->api->item('/api/tickets/' . $id);
            return $this->json($ticket);
        } catch (ApiException $e) {
            return $this->json(['detail' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_MODIFIER')]
    public function edit(int $id, Request $request)
    {
        try {
            $ticket = $this->api->item('/api/tickets/' . $id);
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'ticket.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(TicketEditFormType::class, $ticket);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            try {
                $this->api->patch('/api/tickets/' . $id, [
                    'nomclient' => $data['nomclient'],
                    'contactclient' => $data['contactclient']
                ]);
                $this->addFlash('success', 'Ticket modifié avec succès');
                return $this->redirectToRoute('ticket.show', ['id' => $id]);
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'ticket.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('ticket/edit.html.twig', [
            'form' => $form,
            'ticket' => $ticket
        ]);
    }
    /*
        #[Route('/{id}/print', name: 'print', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
        #[IsGranted('TICKET_VOIR')]
        public function print(int $id)
        {
            try {
                $content = $this->api->raw('/api/tickets/' . $id . '/print'); /*
                    - L'impression venant de l'api
                *
                return new Response(
                    $content['body'],
                    200,
                    [
                        'Content-Type' => $content['content_type'],
                        'Content-Disposition' => 'inline; filename="ticket-' . $id . '.pdf"'
                    ]
                );
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'ticket.index');
                if($response) {
                    return $response;
                }
                -- Ou.. return $this->redirectToRoute('ticket.pdf', ['id' => $id]);
            }
        }
    */
    #[Route('/{id}/pdf', name: 'pdf', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_VOIR')]
    public function pdf(int $id)
    {
        try {
            $ticket = $this->api->item('/api/tickets/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'ticket.index');
            if($response) {
                return $response;
            }
        }

        return $this->pdfService->generate(
            'mails/ticket/thermalpdf.html.twig',
            ['ticket' => $ticket],
            'ticket-' . ($ticket['codeticket'] ?? $id) . '.pdf'
        );
    }

    #[Route('/batch/print', name: 'batch_print', methods: ['POST'])]
    #[IsGranted('TICKET_VOIR')]
    public function batchPrint(Request $request): Response
    {
        try {
            $ids = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR)['ids'] ?? [];
        } catch (\JsonException) {
            return $this->json(['detail' => 'Requête invalide'], 400);
        }

        if (empty($ids) || count($ids) > 50) {
            return $this->json(['detail' => 'Sélection invalide (1 à 50 tickets)'], 422);
        }

        $tickets = [];
        foreach ($ids as $id) {
            try {
                $tickets[] = $this->api->item('/api/tickets/' . (int)$id);
            } catch (ApiException) {
                // On ignore les tickets inaccessibles
            }
        }

        if (empty($tickets)) {
            return $this->json(['detail' => 'Aucun ticket trouvé'], 404);
        }

        return $this->pdfService->generate(
            'mails/ticket/thermalpdf.html.twig',
            ['tickets' => $tickets],
            'tickets-lot-' . date('YmdHis') . '.pdf'
        );
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TICKET_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_ticket', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/tickets/' . $id . '/remove');
                $this->addFlash('success', 'Le ticket a été supprimé avec succès');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'ticket.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('ticket.index');
    }
}
