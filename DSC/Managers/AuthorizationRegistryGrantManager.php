<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\AuthorizationRegistryGrant;

class AuthorizationRegistryGrantManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(AuthorizationRegistryGrant $authorizationRegistryGrant): void
    {
        $entity = $authorizationRegistryGrant->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): AuthorizationRegistryGrant
    {
        $entity = $this->entityManager->readOne($id);
        $authorizationRegistryGrant = new AuthorizationRegistryGrant();
        $authorizationRegistryGrant->fromEntity($entity);
        return $authorizationRegistryGrant;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, AuthorizationRegistryGrant::TYPE, $query);

        $authorizationRegistryGrants = [];
        foreach ($entities as $entity) {
            $authorizationRegistryGrant = new AuthorizationRegistryGrant();
            $authorizationRegistryGrant->fromEntity($entity);
            if ($idAsKey) $authorizationRegistryGrants[$authorizationRegistryGrant->id] = $authorizationRegistryGrant;
            else $authorizationRegistryGrants[] = $authorizationRegistryGrant;
        }

        return $authorizationRegistryGrants;
    }

    public function update(AuthorizationRegistryGrant $authorizationRegistryGrant): void
    {
        $entity = $authorizationRegistryGrant->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(AuthorizationRegistryGrant $authorizationRegistryGrant): void
    {
        $entity = $authorizationRegistryGrant->toEntity();
        $this->entityManager->delete($entity);
    }
}
