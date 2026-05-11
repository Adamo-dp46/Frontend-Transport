<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Form\GareFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/gare', name: 'gare.')]
#[IsGranted('ROLE_USER')]
final class GareController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('GARE_VOIR')]
    public function index(): Response
    {
        try {
            $gares = $this->api->collection('/api/gares');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        return $this->render('gare/index.html.twig', [
            'gares' => $gares
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('GARE_VOIR')]
    public function show(int $id): Response
    {
        try {
            $gare = $this->api->item('/api/gares/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'gare.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('gare/show.html.twig', [
            'gare' => $gare
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('GARE_CREER')]
    public function new(Request $request): Response
    {
        $form = $this->createForm(GareFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'chefgare' => $form->get('chefgare')->getData(),
                'ville' => $form->get('ville')->getData(),
                'libelle' => $form->get('libelle')->getData(),
                'description' => $form->get('description')->getData(),
                'contact1' => $form->get('contact1')->getData(),
                'contact2' => $form->get('contact2')->getData()
            ];
            try {
                $this->api->post('/api/gares', $payload);
                $this->addFlash('success', 'La gare a été créée avec succès');
                return $this->redirectToRoute('gare.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'gare.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('gare/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('GARE_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $gare = $this->api->item('/api/gares/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'gare.index');
            if($response) {
                return $response;
            }
        }
        $form = $this->createForm(GareFormType::class, $gare);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'chefgare' => $form->get('chefgare')->getData(),
                'ville' => $form->get('ville')->getData(),
                'libelle' => $form->get('libelle')->getData(),
                'description' => $form->get('description')->getData(),
                'contact1' => $form->get('contact1')->getData(),
                'contact2' => $form->get('contact2')->getData()
            ];
            try {
                $this->api->patch('/api/gares/' . $id, $payload);
                $this->addFlash('success', 'La gare a été modifiée avec succès');
                return $this->redirectToRoute('gare.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'gare.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('gare/edit.html.twig', [
            'form' => $form,
            'gare' => $gare
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('GARE_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_gare', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/gares/' . $id . '/remove');
                $this->addFlash('success', 'La gare a été supprimée avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'gare.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('gare.index');
    }
}
