<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\TrustedIssuersList;

class TrustedIssuersListManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(TrustedIssuersList $trustedIssuersList): void
    {
        $entity = $trustedIssuersList->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): TrustedIssuersList
    {
        $entity = $this->entityManager->readOne($id);
        $trustedIssuersList = new TrustedIssuersList();
        $trustedIssuersList->fromEntity($entity);
        return $trustedIssuersList;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, TrustedIssuersList::TYPE, $query);

        $trustedIssuersLists = [];
        foreach ($entities as $entity) {
            $trustedIssuersList = new TrustedIssuersList();
            $trustedIssuersList->fromEntity($entity);
            if ($idAsKey) $trustedIssuersLists[$trustedIssuersList->id] = $trustedIssuersList;
            else $trustedIssuersLists[] = $trustedIssuersList;
        }

        return $trustedIssuersLists;
    }

    public function update(TrustedIssuersList $trustedIssuersList): void
    {
        $entity = $trustedIssuersList->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(TrustedIssuersList $trustedIssuersList): void
    {
        $entity = $trustedIssuersList->toEntity();
        $this->entityManager->delete($entity);
    }
}
