<?php

namespace App\Entity;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Va représenter l'utilisateur connecté côté front hydraté depuis les données retournées par l'api et stocké dans le token
 */
class ApiUser implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(
        private readonly array $data
    )
    {
    }

    public function getId(): int
    {
        return $this->data['id'];
    }

    public function getNom(): string
    {
        return $this->data['nom'] ?? '';
    }

    public function getPrenom(): string
    {
        return $this->data['prenom'] ?? '';
    }

    public function getEmail(): string
    {
        return $this->data['email'] ?? '';
    }

    public function getFileUrl(): ?string
    {
        return $this->data['fileUrl'] ?? null;
    }

    public function getEntrepriseId(): ?int
    {
        return $this->data['entreprise']['id'] ?? null;
    }

    public function getGare(): ?array
    {
        return $this->data['gare'] ?? null;
    }

    /**
     * Va retourner les rôles métier de symfony
     */
    public function getRoles(): array
    {
        return $this->data['roles'];
    }

    public function isFounder(): ?bool
    {
        return $this->data['founder'] ?? null;
    }

    /**
     * Va retourner les rôles 'RBAC' de l'utilisateur
     */
    public function getMetierRoles(): array
    {
        return $this->data['userRoles'] ?? [];
    }

    /**
     * Permet de vérifier si l'utilisateur possède une permission métier
     */
    public function hasPermission(string $entity, string $action): bool
    {
        if(in_array('ROLE_ADMIN', $this->getRoles(), true)) {
            return true;
        }

        if(in_array('ROLE_ADMIN_GARE', $this->getRoles(), true)) {
            return true;
        }

        foreach($this->getMetierRoles() as $userRole) {
            $permissions = $userRole['role']['permissions'] ?? [];
            foreach($permissions as $permission) {
                if(strtolower($permission['entity']) === strtolower($entity) && strtoupper($permission['action']) === strtoupper($action)) {
                    return true;
                }
            }
        }

        return false;
    }

    public function eraseCredentials(): void
    {
        // On n'a rien à effacer vu qu'on n'a pas de mot de passe stocké
    }

    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Pour le 'remember_me' pour générer la signature du cookie vu qu'il est réquis
     * @return null
     */
    public function getPassword(): ?string
    {
        return null; /*
            - Si 'null' il utilisera 'getUserIdentifier()' pour la signature
        */
    }
}