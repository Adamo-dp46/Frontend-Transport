<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class HomeController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if($this->isGranted('ROLE_SUPER_ADMIN')) {
            return $this->redirectToRoute('admin.user.index');
        }

        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $exploitation = $this->api->item('/api/stats/exploitation?' . $periode);
            $financiere = $this->api->item('/api/stats/financiere?' . $periode);
            $stock = $this->api->item('/api/stats/stock?' . $periode);
            $flotte = $this->api->item('/api/stats/flotte?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $exploitation = [
            'totalVoyages' => $exploitation['totalVoyages'] ?? 0,
            'tauxRemplissage'  => [
                'moyenneGlobale' => $exploitation['tauxRemplissage']['moyenneGlobale'] ?? 0,
                'detailParVoyage' => $exploitation['tauxRemplissage']['detailParVoyage'] ?? [],
            ],
            'voyagesParPeriode' => $exploitation['voyagesParPeriode'] ?? [],
            'voyagesParStatut'  => $exploitation['voyagesParStatut'] ?? []
        ];

        $financiere = [
            'recettesTotales' => $financiere['recettesTotales'] ?? 0,
            'recettesTickets' => $financiere['recettesTickets'] ?? 0,
            'recettesCourriers' => $financiere['recettesCourriers'] ?? 0,
            'recettesBagages' => $financiere['recettesBagages'] ?? 0,
            'coutDepannages' => $financiere['coutDepannages'] ?? 0,
            'coutApprovisionnements' => $financiere['coutApprovisionnements'] ?? 0,
            'beneficeNet' => $financiere['beneficeNet'] ?? 0,
            'recettesParJour' => $financiere['recettesParJour'] ?? [],
            'coutsParJour' => $financiere['coutsParJour'] ?? []
        ];

        $stock = [
            'totalPieces' => $stock['totalPieces'] ?? 0,
            'piecesCritiques' => $stock['piecesCritiques'] ?? 0,
            'stockParPiece' => $stock['stockParPiece'] ?? [],
            'mouvementsRecents'=> $stock['mouvementsRecents'] ?? []
        ];

        $flotte = [
            'totalVehicules' => $flotte['totalVehicules'] ?? 0,
            'vehiculesParEtat' => $flotte['vehiculesParEtat'] ?? [],
            'vehiculesParDepannage' => $flotte['vehiculesParDepannage'] ?? [],
            'coutMaintenanceParVehicule'=> $flotte['coutMaintenanceParVehicule'] ?? []
        ];

        return $this->render('home/index.html.twig', [
            'exploitation' => $exploitation,
            'financiere' => $financiere,
            'stock' => $stock,
            'flotte' => $flotte,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/proprietaire', name: 'owner', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function owner(): Response
    {
        return $this->render('home/owner.html.twig');
    }

    #[Route('/billetterie', name: 'billetterie', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function billetterie(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $billetterie = $this->api->item('/api/stats/billetterie?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $billetterie = [
            'totalTickets' => $billetterie['totalTickets'] ?? 0,
            'recetteTotale' => $billetterie['recetteTotale'] ?? 0,
            'recettesParJour' => $billetterie['recettesParJour'] ?? [],
            'recettesParTrajet' => $billetterie['recettesParTrajet'] ?? [],
            'recettesParCar' => $billetterie['recettesParCar'] ?? []
        ];

        return $this->render('home/billetterie.html.twig', [
            'billetterie' => $billetterie,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/agents', name: 'agent', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function agent(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $agents = $this->api->item('/api/stats/agent?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $agents = [
            'totalAgents' => $agents['totalAgents'] ?? 0,
            'agentsActifs' => $agents['agentsActifs'] ?? 0,
            'performances' => $agents['performances'] ?? []
        ];

        return $this->render('home/agent.html.twig', [
            'agents' => $agents,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/personnels', name: 'personnels', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function personnels(Request $request): Response
    {
        $debut = $request->query->get('debut', date('Y-m-01'));
        $fin = $request->query->get('fin', date('Y-m-t'));
        $periode = http_build_query([
            'debut' => $debut,
            'fin' => $fin
        ]);

        try {
            $personnel = $this->api->item('/api/stats/personnel?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $personnel = [
            'totalPersonnels' => $personnel['totalPersonnels'] ?? 0,
            'personnelsActifs' => $personnel['personnelsActifs'] ?? 0,
            'performances' => $personnel['performances'] ?? []
        ];

        return $this->render('home/personnel.html.twig', [
            'personnel' => $personnel,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/flotte', name: 'flotte', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function flotte(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $flotte = $this->api->item('/api/stats/flotte/activite?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $flotte = [
            'totalVehicules' => $flotte['totalVehicules'] ?? 0,
            'vehiculesEnVoyage' => $flotte['vehiculesEnVoyage'] ?? 0,
            'activiteParVehicule' => $flotte['activiteParVehicule'] ?? []
        ];

        return $this->render('home/flotte.html.twig', [
            'flotteActivite' => $flotte,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/trajets', name: 'trajets', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function trajet(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $trajet = $this->api->item('/api/stats/trajet/performance?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $trajet = [
            'totalTrajets' => $trajet['totalTrajets'] ?? 0,
            'performances' => $trajet['performances'] ?? []
        ];

        return $this->render('home/trajet.html.twig', [
            'trajets' => $trajet,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/caisse', name: 'caisse', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function caisse(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $caisse = $this->api->item('/api/stats/caisse?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $caisse = [
            'totalTickets' => $caisse['totalTickets'] ?? 0,
            'recetteTickets' => $caisse['recetteTickets'] ?? 0,
            'totalCourriers' => $caisse['totalCourriers'] ?? 0,
            'recetteCourriers' => $caisse['recetteCourriers'] ?? 0,
            'totalBagages' => $caisse['totalBagages'] ?? 0,
            'recetteBagages' => $caisse['recetteBagages'] ?? 0,
            'recetteTotale' => $caisse['recetteTotale'] ?? 0,
            'parAgent' => $caisse['parAgent'] ?? [],
            'parJour' => $caisse['parJour'] ?? []
        ];

        return $this->render('home/caisse.html.twig', [
            'caisse' => $caisse,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/stats/courrier', name: 'stats.courrier', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function courrier(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $courrier = $this->api->item('/api/stats/courriers?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $courrier = [
            'totalCourriers' => $courrier['totalCourriers'] ?? 0,
            'enAttente' => $courrier['enAttente'] ?? 0,
            'enTransit' => $courrier['enTransit'] ?? 0,
            'receptionnes' => $courrier['receptionnes'] ?? 0,
            'livres' => $courrier['livres'] ?? 0,
            'perdus' => $courrier['perdus'] ?? 0,
            'annules' => $courrier['annules'] ?? 0,
            'recetteTotale' => $courrier['recetteTotale'] ?? 0,
            'recettesParJour' => $courrier['recettesParJour'] ?? [],
            'recettesParTrajet' => $courrier['recettesParTrajet'] ?? []
        ];

        return $this->render('home/courrier.html.twig', [
            'courrier' => $courrier,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/stats/bagage', name: 'stats.bagage', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    public function bagage(Request $request): Response
    {
        ['debut' => $debut, 'fin' => $fin, 'periode' => $periode] = $this->getPeriode($request);

        try {
            $bagage = $this->api->item('/api/stats/bagages?' . $periode);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $bagage = [
            'totalBagages' => $bagage['totalBagages'] ?? 0,
            'enregistres' => $bagage['enregistres'] ?? 0,
            'embarques' => $bagage['embarques'] ?? 0,
            'livres' => $bagage['livres'] ?? 0,
            'perdus' => $bagage['perdus'] ?? 0,
            'recetteTotale' => $bagage['recetteTotale'] ?? 0,
            'poidsTotal' => $bagage['poidsTotal'] ?? 0,
            'recettesParJour' => $bagage['recettesParJour'] ?? []
        ];

        return $this->render('home/bagage.html.twig', [
            'bagage' => $bagage,
            'debut' => $debut,
            'fin' => $fin
        ]);
    }

    #[Route('/aide', name: 'aide', methods: ['GET'])]
    public function aide(): Response
    {
        return $this->render('home/aide.html.twig', []);
    }

    #[Route('/ui', name: 'ui', methods: ['GET'])]
    public function ui(): Response
    {
        return $this->render('home/ui.html.twig', []);
    }

    private function getPeriode(Request $request): array
    {
        $debut = $request->query->get('debut', date('Y-m-01')); // '01' pour le premier jour du mois
        $fin = $request->query->get('fin', date('Y-m-t'));

        return [
            'debut' => $debut,
            'fin' => $fin,
            'periode' => http_build_query([
                'debut' => $debut,
                'fin' => $fin
            ])
        ];
    }
}
