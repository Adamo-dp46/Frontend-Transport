<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Form\PersonnelEditFormType;
use App\Form\PersonnelFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/personnel', name: 'personnel.')]
#[IsGranted('ROLE_USER')]
final class PersonnelController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    #[IsGranted('PERSONNEL_VOIR')]
    public function index(): Response
    {
        try {
            $personnels = $this->api->collection('/api/personnels');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e);
            if($response) {
                return $response;
            }
        }

        return $this->render('personnel/index.html.twig', [
            'personnels' => $personnels
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('PERSONNEL_VOIR')]
    public function show(int $id): Response
    {
        try {
            $personnel = $this->api->item('/api/personnels/' . $id);
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'personnel.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('personnel/show.html.twig', [
            'personnel' => $personnel
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('PERSONNEL_CREER')]
    public function new(Request $request): Response
    {
        $types = [];
        try {
            $types = $this->api->collection('/api/typepersonnels');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'personnel.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(PersonnelFormType::class, null, [
            'types' => $types,
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $typeId = $form->get('typepersonnel')->getData();

            /**
             * @var UploadedFile|null
             */
            $file = $form->get('image')->getData();
            $mediaObject = null;
            if($file) {
                try {
                    $mediaObject = $this->api->postMediaObject($file);
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, null, 'personnel.new');
                    if($response) {
                        return $response;
                    }
                }
            }

            $payload = [
                'nom' => $form->get('nom')->getData(),
                'prenom' => $form->get('prenom')->getData(),
                'contact' => (string)$form->get('contact')->getData(),
                'typepersonnel' => '/api/typepersonnels/' . $typeId,
                'image' => $mediaObject['@id'] ?? null
            ];
  
            try {
                $this->api->post('/api/personnels', $payload);
                $this->addFlash('success', 'Le personnel a été créé avec succès');
                return $this->redirectToRoute('personnel.index');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'personnel.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('personnel/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('PERSONNEL_MODIFIER')]
    public function edit(int $id, Request $request): Response
    {
        $types = [];
        $personnel = [];
        try {
            $personnel = $this->api->item('/api/personnels/' . $id);
            $types = $this->api->collection('/api/typepersonnels');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'personnel.index');
            if($response) {
                return $response;
            }
        }

        $currentTypeId = isset($personnel['typepersonnel']) ? (int)$personnel['typepersonnel']['id'] : null;

        $form = $this->createForm(PersonnelEditFormType::class, array_merge(
            $personnel ?? [],
            ['typepersonnel' => $currentTypeId]
        ), [
            'types' => $types,
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $typeId = $form->get('typepersonnel')->getData();

            // Gestion de l'image — on garde l'ancienne si pas de nouvelle
            $mediaObjectIri = $personnel['image']['@id'] ?? null;

            /** @var UploadedFile|null $file */
            $file = $form->get('image')->getData();
            if($file) {
                try {
                    $mediaObject = $this->api->postMediaObject($file);
                    $mediaObjectIri = $mediaObject['@id'] ?? null;
                } catch(ApiException $e) {
                    $response = $this->apiExceptionHandler->handle($e, $form, 'personnel.edit', ['id' => $id]);
                    if($response) {
                        return $response;
                    }
                }
            }

            $payload = [
                'nom' => $form->get('nom')->getData(),
                'prenom' => $form->get('prenom')->getData(),
                'contact' => $form->get('contact')->getData(),
                'typepersonnel' => '/api/typepersonnels/' . $typeId,
                'image' => $mediaObjectIri // nouvelle IRI ou ancienne conservée
            ];

            try {
                $this->api->patch('/api/personnels/' . $id, $payload);
                $this->addFlash('success', 'Le personnel a été modifié avec succès');
                return $this->redirectToRoute('personnel.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'personnel.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('personnel/edit.html.twig', [
            'form' => $form,
            'personnel' => $personnel,
            'api_url' => $this->getParameter('api.endpoint')
        ]);
    }

    #[Route('/{id}/supprimer', name: 'delete', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('PERSONNEL_SUPPRIMER')]
    public function delete(int $id, Request $request): Response
    {
        if($this->isCsrfTokenValid('delete_personnel', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/personnels/' . $id . '/remove');
                $this->addFlash('success', 'Le personnel a été supprimé avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'personnel.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('personnel.index');
    }
}
