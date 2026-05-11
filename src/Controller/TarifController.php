<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Form\TarifFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tarif', name: 'tarif.')]
#[IsGranted('ROLE_USER')]
final class TarifController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('TARIF_VOIR')]
    public function index(): Response
    {
        try {
            $tarifs = $this->api->collection('/api/tarifs');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        return $this->render('tarif/index.html.twig', [
            'tarifs' => $tarifs
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TARIF_VOIR')]
    public function show(int $id): Response
    {
        try {
            $tarif = $this->api->item('/api/tarifs/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'tarif.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('tarif/show.html.twig', [
            'tarif' => $tarif
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('TARIF_CREER')]
    public function new(Request $request): Response
    {
        $form = $this->createForm(TarifFormType::class);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'libelle' => $form->get('libelle')->getData(),
                'montant' => $form->get('montant')->getData()
            ];
            try {
                $this->api->post('/api/tarifs', $payload);
                $this->addFlash('success', 'Le tarif a été créé avec succès');
                return $this->redirectToRoute('tarif.index');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'tarif.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('tarif/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TARIF_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        try {
            $tarif = $this->api->item('/api/tarifs/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'tarif.index');
            if($response) {
                return $response;
            }
        }
        $form = $this->createForm(TarifFormType::class, $tarif);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()) {
            $payload = [
                'libelle' => $form->get('libelle')->getData(),
                'montant' => $form->get('montant')->getData()
            ];
            try {
                $this->api->patch('/api/tarifs/' . $id, $payload);
                $this->addFlash('success', 'Le tarif a été modifié avec succès');
                return $this->redirectToRoute('tarif.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'tarif.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('tarif/edit.html.twig', [
            'form' => $form,
            'tarif' => $tarif
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('TARIF_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_tarif', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/tarifs/' . $id . '/remove');
                $this->addFlash('success', 'Le tarif a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'tarif.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('tarif.index');
    }
}
