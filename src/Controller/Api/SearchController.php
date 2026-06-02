<?php

namespace App\Controller\Api;

use App\Domain\Helper\ApiHelper;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class SearchController extends AbstractController
{
    private const RESOURCES = [
        'fournisseurs' => ['search' => 'nom', 'label' => 'nom', 'permission' => 'FOURNISSEUR_VOIR'],
        'pieces' => ['search' => 'libelle', 'label' => 'libelle', 'permission' => 'PIECE_VOIR'],
        'cars' => ['search' => 'matricule', 'label' => 'matricule', 'permission' => 'CAR_VOIR'],
        'voyages' => ['search' => 'provenance', 'label' => 'codevoyage', 'permission' => 'VOYAGE_VOIR'],
        'gares' => ['search' => 'libelle', 'label' => 'libelle', 'permission' => 'GARE_VOIR'],
        'personnels' => ['search' => 'nom', 'label' => 'nom', 'permission' => 'PERSONNEL_VOIR'],
        'trajets' => ['search' => 'provenance', 'label' => 'codetrajet', 'permission' => 'TRAJET_VOIR'],
        'users' => ['search' => 'nom', 'label' => 'nom', 'permission' => 'USER_VOIR']
    ];

    public function __construct(
        private readonly ApiHelper $api
    )
    {
    }

    #[Route('/search', name: 'app_search')]
    #[IsGranted('ROLE_USER')]
    public function index(Request $request): JsonResponse
    {
        $resource = $request->query->getString('resource');
        $q = $request->query->getString('q', '');
        $limit = min((int)$request->query->get('limit', 20), 50);
        if(!isset(self::RESOURCES[$resource])) {
            return $this->json(['error' => 'Ressource non autorisée'], 403);
        }
        $config = self::RESOURCES[$resource];

        if(!$this->isGranted($config['permission'])) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        if($q !== '' && mb_strlen($q) < 2) {
            return $this->json([]);
        }

        try {
            $params = http_build_query([
                $config['search'] => $q ?: null, /*
                    - 'null' pour n'envoyer le filtre que si 'q' est vide
                */
                'itemsPerPage' => $limit,
                'page' => 1
            ]);
            $items = $this->api->collection("/api/{$resource}?{$params}");
            $results = array_map(fn($item) => [ /*
                - On normalise en '{ value, label, raw }'
            */
                'value' => (string)$item['id'],
                'label' => $item[$config['label']] ?? "#{$item['id']}",
                'raw' => $item // Les données complètes si besoin côté client
            ], $items);
            return $this->json($results);
        } catch(ApiException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }
    }
}
