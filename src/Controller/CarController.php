<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Form\CarFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/car', name: 'car.')]
#[IsGranted('ROLE_USER')]
final class CarController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('CAR_VOIR')]
    public function index(): Response
    {
        try {
            $cars = $this->api->collection('/api/cars');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        $parEtat = array_count_values(array_column($cars, 'etat')); /*
            - On groupe les compteurs par état
        */

        return $this->render('car/index.html.twig', [
            'cars' => $cars,
            'parEtat' => $parEtat
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('CAR_VOIR')]
    public function show(int $id): Response
    {
        try {
            $car = $this->api->item('/api/cars/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'car.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('car/show.html.twig', [
            'car' => $car
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('CAR_CREER')]
    public function new(Request $request): Response
    {
        try {
            $marques = $this->api->collection('/api/marques');
            $modelvehicules = $this->api->collection('/api/modelvehicules');
            $typevehicules = $this->api->collection('/api/typevehicules');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'car.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(CarFormType::class, null, [
            'marques' => $marques,
            'modelvehicules' => $modelvehicules,
            'typevehicules' => $typevehicules
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'matricule' => $form->get('matricule')->getData(),
                'nbrsiege' => $form->get('nbrsiege')->getData(),
                'datearrivee' => $form->get('datearrivee')->getData()?->format('Y-m-d\TH:i:s.v\Z'),
                'etat' => $form->get('etat')->getData(),
                'sieges_gauche' => $form->get('siegesGauche')->getData(),
                'sieges_droite' => $form->get('siegesDroite')->getData()
            ];

            $marque = $form->get('marque')->getData();
            if($marque) {
                $payload['marque'] = '/api/marques/' . $marque;
            }
            $modelvehicule = $form->get('modelvehicule')->getData();
            if($modelvehicule) {
                $payload['modelvehicule'] = '/api/modelvehicules/' . $modelvehicule;
            }
            $typevehicule = $form->get('typevehicule')->getData();
            if($typevehicule) {
                $payload['typevehicule'] = '/api/typevehicules/' . $typevehicule;
            }

            try {
                $this->api->post('/api/cars', $payload);
                $this->addFlash('success', 'Le véhicule a été créé avec succès');
                return $this->redirectToRoute('car.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'car.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('car/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('CAR_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        $marques = [];
        try {
            $car = $this->api->item('/api/cars/' . $id);
            $marques = $this->api->collection('/api/marques');
            $modelvehicules = $this->api->collection('/api/modelvehicules');
            $typevehicules = $this->api->collection('/api/typevehicules');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'car.index');
            if($response) {
                return $response;
            }
        }

        $data = array_merge($car ?? [], [
            'marque' => isset($car['marque']) ? (int)$car['marque']['id'] : null,
            'modelvehicule' => isset($car['modelvehicule']) ? (int)$car['modelvehicule']['id'] : null,
            'typevehicule' => isset($car['typevehicule']) ? (int)$car['typevehicule']['id'] : null,
            'datearrivee' => isset($car['datearrivee']) ? new \DateTime($car['datearrivee']) : null,
            'siegesGauche' => $car['sieges_gauche'] ?? null,
            'siegesDroite' => $car['sieges_droite'] ?? null
        ]);

        $form = $this->createForm(CarFormType::class, $data, [
            'marques' => $marques,
            'modelvehicules' => $modelvehicules,
            'typevehicules' => $typevehicules
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'matricule' => $form->get('matricule')->getData(),
                'nbrsiege' => $form->get('nbrsiege')->getData(),
                'datearrivee' => $form->get('datearrivee')->getData()?->format('Y-m-d\TH:i:s.v\Z'),
                'etat' => $form->get('etat')->getData(),
                'sieges_gauche' => $form->get('siegesGauche')->getData(),
                'sieges_droite' => $form->get('siegesDroite')->getData()
            ];

            $marque = $form->get('marque')->getData();
            if($marque) {
                $payload['marque'] = '/api/marques/' . $marque;
            }
            $modelvehicule = $form->get('modelvehicule')->getData();
            if($modelvehicule) {
                $payload['modelvehicule'] = '/api/modelvehicules/' . $modelvehicule;
            }
            $typevehicule = $form->get('typevehicule')->getData();
            if($typevehicule) {
                $payload['typevehicule'] = '/api/typevehicules/' . $typevehicule;
            }

            try {
                $this->api->patch('/api/cars/' . $id, $payload);
                $this->addFlash('success', 'Le véhicule a été modifié avec succès');
                return $this->redirectToRoute('car.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'car.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('car/edit.html.twig', [
            'form' => $form,
            'car' => $car
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('CAR_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_car', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/cars/' . $id . '/remove');
                $this->addFlash('success', 'Le véhicule a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'car.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('car.index');
    }
}
