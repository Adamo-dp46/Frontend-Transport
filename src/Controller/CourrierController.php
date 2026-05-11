<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Domain\Service\PdfService;
use App\Entity\ApiUser;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/courrier', name: 'courrier.')]
#[IsGranted('ROLE_USER')]
final class CourrierController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('COURRIER_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/courriers', $request->query->all(),
            [
                'search' => 'codecourrier',
                'statut' => 'statut',
                'garedepart' => 'garedepart.id',
                'garearrivee'=> 'garearrivee.id',
                'voyage' => 'voyage.id',
                'date_from' => 'createdAt[after]',
                'date_to' => 'createdAt[before]'
            ],
            [
                'id',
                'codecourrier',
                'montant',
                'statut',
                'createdAt'
            ],
            [
                'gares' => $this->api->collection('/api/gares'),
            ]
        );

        return $this->render('courrier/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_VOIR')]
    public function show(int $id): Response
    {
        try {
            $courrier = $this->api->item('/api/courriers/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.index');
            if ($response) return $response;
        }

        return $this->render('courrier/show.html.twig', [
            'courrier' => $courrier
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('COURRIER_CREER')]
    public function new(Request $request): Response
    {
        try {
            $gares = $this->api->collection('/api/gares');
            $voyages = $this->api->collection('/api/voyages?exists[datefin]=false');
            $tarifcourriers = $this->api->collection('/api/tarifcourriers');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $details = $this->buildDetails($data);
            if(empty($details)) {
                $this->addFlash('error', 'Veuillez ajouter au moins un colis');
            } else {
                $payload = [
                    'nomexpediteur' => $data['nomexpediteur'] ?? '',
                    'contactexpediteur' => $data['contactexpediteur'] ?? '',
                    'nomdestinataire' => $data['nomdestinataire'] ?? '',
                    'contactdestinataire'=> $data['contactdestinataire'] ?? '',
                    'gareDepart' => (int)($data['gareDepart'] ?? 0), // !!
                    'gareArrivee' => (int)($data['gareArrivee'] ?? 0),
                    'voyage' => !empty($data['voyage']) ? (int) $data['voyage'] : null,
                    'fraissuivi' => !empty($data['fraissuivi']) ? (int) $data['fraissuivi'] : null,
                    'modepaiement' => $data['modepaiement'] ?? 'ENVOI',
                    'details' => $details
                ];

                try {
                    $this->api->post('/api/courriers', $payload);
                    $this->addFlash('success', 'Le courrier a été enregistré avec succès');
                    return $this->redirectToRoute('courrier.index');
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, null, 'courrier.new');
                    if($response) {
                        return $response;
                    }
                }
            }
        }

        return $this->render('courrier/new.html.twig', [
            'gares' => $gares,
            'voyages' => $voyages,
            'tarifcourriers' => $tarifcourriers
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $courrier = $this->api->item('/api/courriers/' . $id);
            $gares = $this->api->collection('/api/gares');
            $voyages = $this->api->collection('/api/voyages?exists[datefin]=false');
            $tarifcourriers = $this->api->collection('/api/tarifcourriers');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.index');
            if($response) {
                return $response;
            }
        }

        if($request->isMethod('POST')) {
            $data = $request->request->all();
            $details = $this->buildDetails($data);
            if (empty($details)) {
                $this->addFlash('error', 'Veuillez ajouter au moins un colis');
            } else {
                $payload = [
                    'nomexpediteur' => $data['nomexpediteur'] ?? '',
                    'contactexpediteur' => $data['contactexpediteur'] ?? '',
                    'nomdestinataire' => $data['nomdestinataire'] ?? '',
                    'contactdestinataire'=> $data['contactdestinataire'] ?? '',
                    'gareDepart' => (int)($data['gareDepart'] ?? 0),
                    'gareArrivee' => (int)($data['gareArrivee'] ?? 0),
                    'voyage' => !empty($data['voyage']) ? (int)$data['voyage'] : null,
                    'fraissuivi' => !empty($data['fraissuivi']) ? (int)$data['fraissuivi'] : null,
                    'modepaiement' => $data['modepaiement'] ?? 'ENVOI',
                    'details' => $details
                ];

                try {
                    $this->api->patch('/api/courriers/' . $id, $payload);
                    $this->addFlash('success', 'Le courrier a été modifié avec succès');
                    return $this->redirectToRoute('courrier.show', ['id' => $id]);
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, null, 'courrier.edit', ['id' => $id]);
                    if($response) {
                        return $response;
                    }
                }
            }
        }

        return $this->render('courrier/edit.html.twig', [
            'courrier' => $courrier,
            'gares' => $gares,
            'voyages' => $voyages,
            'tarifcourriers' => $tarifcourriers
        ]);
    }

    #[Route('/{id}/livrer', name: 'livrer', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_MODIFIER')]
    public function livrer(int $id): Response
    {
        try {
            $this->api->patch('/api/courriers/' . $id . '/livrer');
            $this->addFlash('success', 'Le courrier a été marqué comme livré');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->redirectToRoute('courrier.show', ['id' => $id]);
    }

    #[Route('/{id}/annuler', name: 'annuler', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_MODIFIER')]
    public function annuler(int $id): Response
    {
        try {
            $this->api->patch('/api/courriers/' . $id . '/annuler');
            $this->addFlash('success', 'Le courrier a été annulé');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->redirectToRoute('courrier.show', ['id' => $id]);
    }

    #[Route('/{id}/perdu', name: 'perdu', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_MODIFIER')]
    public function perdu(int $id): Response
    {
        try {
            $this->api->patch('/api/courriers/' . $id . '/perdu');
            $this->addFlash('success', 'Le courrier a été déclaré perdu');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        return $this->redirectToRoute('courrier.show', ['id' => $id]);
    }

    #[Route('/{id}/print', name: 'print', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_VOIR')]
    public function print(int $id, PdfService $pdfService, ParameterBagInterface $params): Response
    {
        $courrier = null;
        $entreprise = null;
        try {
            $courrier = $this->api->item('/api/courriers/' . $id);
            $entreprise = $this->api->item('/api/me/entreprise');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'courrier.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }

        $logoBase64 = null;
        if(!empty($entreprise['image'])) { /*
            - On récupère l'image 'glide' depuis le backend et encodage base64
        */
            try {
                $imageUrl = $entreprise['image']['contentUrl'];
                $response = HttpClient::create()->request('GET', $params->get('api.endpoint') . '/media' . $imageUrl . '?w=400&h=400&fm=jpg&fit=crop');
                $content = $response->getContent();
                $mimeType = $response->getHeaders()['content-type'][0] ?? 'image/jpeg';
                $logoBase64 = 'data:' . $mimeType . ';base64,' . base64_encode($content);
            } catch(\Throwable) {
                $logoBase64 = null;
            }
        }

        return $pdfService->generate(
            'mails/courrier/ticket.html.twig',
            [
                'courrier' => $courrier,
                'entreprise' => $entreprise,
                'logoBase64' => $logoBase64
            ],
            'courrier-' . ($courrier['codecourrier'] ?? $id) . date('YmdHis') . '.pdf'
        );
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('COURRIER_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_courrier', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/courriers/' . $id . '/remove');
                $this->addFlash('success', 'Le courrier a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'courrier.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('courrier.index');
    }

    private function buildDetails(array $data): array
    {
        $details = [];
        $natures = $data['detail_nature'] ?? [];
        $designations= $data['detail_designation'] ?? [];
        $emballages = $data['detail_emballage'] ?? [];
        $types = $data['detail_type'] ?? [];
        $poids = $data['detail_poids'] ?? [];
        $valeurs = $data['detail_valeur'] ?? [];
        $tarifs = $data['detail_tarif'] ?? []; // !!

        foreach($natures as $i => $nature) {
            if(!empty($nature) && !empty($valeurs[$i])) {
                $details[] = [
                    'nature' => $nature,
                    'designation' => $designations[$i] ?? '',
                    'emballage' => !empty($emballages[$i]) ? $emballages[$i] : null,
                    'type' => $types[$i] ?? 'NORMAL',
                    'poids' => !empty($poids[$i]) ? (int) $poids[$i] : null,
                    'valeur' => (int) $valeurs[$i] /*
                        'detail_tarif' est l'id du 'TarifCourrier', pas utilisé directement par le processor qui recalcule via 'findTarifForValeur' mais utile pour valider côté client que la valeur est dans la bonne tranche
                    */
                ];
            }
        }

        return $details;
    }
}
