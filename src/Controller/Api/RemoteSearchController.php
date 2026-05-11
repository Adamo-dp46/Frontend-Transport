<?php

namespace App\Controller\Api;

use App\Domain\Helper\ApiHelper;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class RemoteSearchController extends AbstractController
{
    private const RESOURCES = [
        'fournisseurs' => [
            'endpoint' => '/api/fournisseurs',
            'searchField' => 'nom',
            'labelFields' => ['nom'] /*
                - Les champs retournés pour le label
            */
        ],
        'pieces' => [
            'endpoint' => '/api/pieces',
            'searchField' => 'libelle',
            'labelFields' => ['libelle']
        ],
        'cars' => [
            'endpoint' => '/api/cars',
            'searchField' => 'matricule',
            'labelFields' => ['matricule']
        ],
        'personnels' => [
            'endpoint' => '/api/personnels',
            'searchField' => 'nom',
            'labelFields' => ['prenom', 'nom']
        ],
        'trajets' => [
            'endpoint' => '/api/trajets',
            'searchField' => 'provenance',
            'labelFields' => ['provenance', 'destination'],
            'labelSep' => ' → '
        ],
        'voyages' => [
            'endpoint' => '/api/voyages',
            'searchField' => 'provenance',
            'labelFields' => ['provenance', 'destination'],
            'labelSep' => ' → '
        ],
        'tarifs' => [
            'endpoint' => '/api/tarifs',
            'searchField' => 'libelle',
            'labelFields' => ['libelle']
        ],
        'gares' => [
            'endpoint' => '/api/gares',
            'searchField' => 'libelle',
            'labelFields' => ['libelle', 'ville'],
            'labelSep' => ' - '
        ],
    ];

    public function __construct(
        private readonly ApiHelper $api
    )
    {
    }

    #[Route('/search/{resource}', name: 'app_remote_search', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(string $resource, Request $request): JsonResponse
    {
        if(!isset(self::RESOURCES[$resource])) {
            return $this->json(['error' => 'Ressource non autorisée'], 403);
        }

        $cfg = self::RESOURCES[$resource];
        $q = trim($request->query->get('q', ''));
        $limit = min((int)$request->query->get('limit', 10), 50);

        $params = ['itemsPerPage' => $limit];
        if($q !== '') {
            $params[$cfg['searchField']] = $q;
        }
        $queryString = http_build_query($params);
        $endpoint = $cfg['endpoint'] . '?' . $queryString;

        try {
            $items = $this->api->collection($endpoint);
        } catch(ApiException $e) {
            return $this->json(['error' => $e->getMessage()], $e->getCode() ?: 500);
        }

        $sep = $cfg['labelSep'] ?? ' '; /*
            - On normalise en '{ id, label }' pour la vue
        */
        $result = array_map(function ($item) use ($cfg, $sep) {
            $labelParts = array_map(
                fn($field) => $item[$field] ?? '',
                $cfg['labelFields']
            );
            return [
                'id' => $item['id'],
                'label' => implode($sep, array_filter($labelParts)),
                'raw' => $item // Les données brutes si besoin côté javascript
            ];
        }, $items);

        return $this->json($result);
    }
}
