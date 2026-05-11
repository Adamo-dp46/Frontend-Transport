<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/inventaire', name: 'inventaire.')]
#[IsGranted('ROLE_USER')]
final class InventaireController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('INVENTAIRE_VOIR')]
    public function index(Request $request): Response
    {
        $data = $this->tableHelper->handleIndex('/api/inventaires',  $request->query->all(),
            [
                'piece' => 'piece.id',
                'typemouvement' => 'typemouvement',
                'reference_type' => 'reference_type',
                'date_from' => 'datemouvement[after]',
                'date_to' => 'datemouvement[before]'
            ],
            [
                'id',
                'datemouvement',
                'quantite',
                'typemouvement',
                'createdAt'
            ],
            [
                'pieces' => $this->api->collection('/api/pieces'),
                'typesmouvement'  => ['ENTREE', 'SORTIE'],
                'typesreference'  => ['APPROVISIONNEMENT', 'DEPANNAGE', 'AJUSTEMENT']
            ]
        );

        return $this->render('inventaire/index.html.twig', $data);
    }
    /*
        #[Route('', name: 'index', methods: ['GET'])]
        #[IsGranted('INVENTAIRE_VOIR')]
        public function index(): Response
        {
            try {
                $inventaires = $this->api->get('/api/inventaires');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e);
                if($response) {
                    return $response;
                }
            }

            $entrees = array_filter($inventaires, fn($m) => ($m['typemouvement'] ?? '') === 'ENTREE');
            $sorties = array_filter($inventaires, fn($m) => ($m['typemouvement'] ?? '') === 'SORTIE');
            $totalEntrees = array_sum(array_column($entrees, 'quantite'));
            $totalSorties = array_sum(array_column($sorties, 'quantite'));

            return $this->render('inventaire/index.html.twig', [
                'inventaires' => $inventaires,
                'total_entrees' => $totalEntrees,
                'total_sorties' => $totalSorties,
                'nb_mouvements' => count($inventaires)
            ]);
        }
    */
    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('INVENTAIRE_VOIR')]
    public function show(int $id): Response
    {
        try {
            $inventaire = $this->api->item('/api/inventaires/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'inventaire.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('inventaire/show.html.twig', [
            'inventaire' => $inventaire
        ]);
    }
}
