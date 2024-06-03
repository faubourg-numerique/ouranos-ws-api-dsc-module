<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\AuthorizationRegistry;

class AuthorizationRegistryManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(AuthorizationRegistry $authorizationRegistry): void
    {
        $entity = $authorizationRegistry->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): AuthorizationRegistry
    {
        $entity = $this->entityManager->readOne($id);
        $authorizationRegistry = new AuthorizationRegistry();
        $authorizationRegistry->fromEntity($entity);
        return $authorizationRegistry;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, AuthorizationRegistry::TYPE, $query);

        $authorizationRegistries = [];
        foreach ($entities as $entity) {
            $authorizationRegistry = new AuthorizationRegistry();
            $authorizationRegistry->fromEntity($entity);
            if ($idAsKey) $authorizationRegistries[$authorizationRegistry->id] = $authorizationRegistry;
            else $authorizationRegistries[] = $authorizationRegistry;
        }

        return $authorizationRegistries;
    }

    public function update(AuthorizationRegistry $authorizationRegistry): void
    {
        $entity = $authorizationRegistry->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(AuthorizationRegistry $authorizationRegistry): void
    {
        $entity = $authorizationRegistry->toEntity();
        $this->entityManager->delete($entity);
    }
}
