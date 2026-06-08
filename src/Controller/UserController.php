<?php

namespace App\Controller;

use App\Domain\Helper\ApiExceptionHandlerHelper;
use App\Domain\Helper\ApiHelper;
use App\Domain\Helper\TableHelper;
use App\Form\UserEditFormType;
use App\Form\UserFormType;
use App\Security\Exception\ApiException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/utilisateurs', name: 'admin.user.')]
#[IsGranted('ROLE_USER')]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly ApiHelper $api,
        private readonly ApiExceptionHandlerHelper $apiExceptionHandler,
        private readonly TableHelper $tableHelper
    )
    {
    }

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        if(!$this->isGranted('USER_VOIR') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $data = $this->tableHelper->handleIndex('/api/users',  $request->query->all(),
            [
                'search' => 'nom',
                'email' => 'email',
                'statut' => 'statut'
            ],
            [
                'id',
                'nom',
                'prenom'
            ],
            []
        );

        return $this->render('admin/user/index.html.twig', $data);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'], requirements: ['id' => Requirement::DIGITS])]
    public function show(int $id): Response
    {
        if(!$this->isGranted('USER_VOIR') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        try {
            $user = $this->api->item('/api/users/' . $id);
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'admin.user.index');
            if($response) {
                return $response;
            }
        }

        return $this->render('admin/user/show.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/nouveau', name: 'new', methods: ['GET', 'POST'])]
    #[IsGranted('USER_CREER')]
    public function new(Request $request): Response
    {
        $availableRoles = [];
        try {
            $availableRoles = $this->api->collection('/api/roles');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'admin.user.index');
            if($response) {
                return $response;
            }
        }

        $form = $this->createForm(UserFormType::class, null, [
            'available_roles' => $availableRoles
        ]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $selectedRoleIds = $form->get('roles')->getData() ?? [];
            $userRolesPayload = array_map(
                fn(int $roleId) => ['role' => '/api/roles/' . $roleId], /*
                    - On transforme les 'id' en 'iri' ApiPlatform [{ "role": "/api/roles/{id}" }]
                */
                $selectedRoleIds
            );

            $payload = [
                'nom' => $form->get('nom')->getData(),
                'prenom' => $form->get('prenom')->getData(),
                'email' => $form->get('email')->getData(),
                'plainPassword' => $form->get('password')->getData(),
                'userRoles' => $userRolesPayload
            ];

            try {
                $this->api->post('/api/users', $payload);
                $this->addFlash('success', 'L\'utilisateur a été créé avec succès');
                return $this->redirectToRoute('admin.user.index');
            } catch (ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'admin.user.new');
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('admin/user/new.html.twig', [
            'form' => $form
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', methods: ['GET', 'POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('USER_MODIFIER')]
    public function edit(int $id, Request $request): RedirectResponse|Response
    {
        $availableRoles = [];
        try {
            $user = $this->api->item('/api/users/' . $id);
            $availableRoles = $this->api->collection('/api/roles');
        } catch (ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'admin.user.index');
            if($response) {
                return $response;
            }
        }

        $userRoleIds = array_map(
            fn($r) => $r['role']['id'],
            $user['userRoles'] ?? []
        ); /*
            - On précoche les rôles déjà attribués en extractant l'id depuis l'iri '/api/roles/{id}'
        */
        $form = $this->createForm(UserEditFormType::class, array_merge(
            $user ?? [],
            ['roles' => $userRoleIds]
        ), ['available_roles' => $availableRoles]);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {
            $selectedRoleIds = $form->get('roles')->getData() ?? [];
            $userRolesPayload = array_map(
                fn(int $roleId) => ['role' => '/api/roles/' . $roleId],
                $selectedRoleIds
            );

            $payload = [
                'nom' => $form->get('nom')->getData(),
                'prenom' => $form->get('prenom')->getData(),
                'email' => $form->get('email')->getData(),
                'userRoles' => $userRolesPayload
            ];

            $newPassword = $form->get('password')->getData();
            if($newPassword) {
                $payload['plainPassword'] = $newPassword;
            }

            try {
                $this->api->patch('/api/users/' . $id, $payload);
                $this->addFlash('success', 'L\'utilisateur a été modifié avec succès');
                return $this->redirectToRoute('admin.user.index');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, $form, 'admin.user.edit', ['id' => $id]);
                if($response) {
                    return $response;
                }
            }
        }

        return $this->render('admin/user/edit.html.twig', [
            'form' => $form,
            'user' => $user
        ]);
    }

    #[Route('/{id}/suspendre', name: 'suspendre', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    public function suspendre(int $id, Request $request): Response
    {
        if(!$this->isGranted('USER_MODIFIER') && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if($this->isCsrfTokenValid('delete_user', $request->request->get('_token'))) {
            try {
                $this->api->patch('/api/users/' . $id . '/suspendre');
                $this->addFlash('success', 'Le statut de l\'utilisateur a été modifié avec succès');
            } catch(ApiException $e) {
                $response = $this->apiExceptionHandler->handle($e, null, 'admin.user.index');
                if($response) {
                    return $response;
                }
            }
        }
        return $this->redirectToRoute('admin.user.index');
    }

    #[Route('/{id}/promouvoir', name: 'promouvoir', methods: ['POST'], requirements: ['id' => Requirement::DIGITS])]
    #[IsGranted('ROLE_ADMIN')]
    public function promouvoir(int $id): Response
    {
        try {
            $this->api->patch('/api/users/' . $id . '/promouvoir');
            $this->addFlash('success', 'Le rôle de l\'utilisateur a été mis à jour');
        } catch(ApiException $e) {
            $response = $this->apiExceptionHandler->handle($e, null, 'admin.user.show', ['id' => $id]);
            if($response) {
                return $response;
            }
        }
        return $this->redirectToRoute('admin.user.show', ['id' => $id]);
    }
}
