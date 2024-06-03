<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\VCVerifier;

class VCVerifierManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(VCVerifier $vcVerifier): void
    {
        $entity = $vcVerifier->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): VCVerifier
    {
        $entity = $this->entityManager->readOne($id);
        $vcVerifier = new VCVerifier();
        $vcVerifier->fromEntity($entity);
        return $vcVerifier;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, VCVerifier::TYPE, $query);

        $vcVerifiers = [];
        foreach ($entities as $entity) {
            $vcVerifier = new VCVerifier();
            $vcVerifier->fromEntity($entity);
            if ($idAsKey) $vcVerifiers[$vcVerifier->id] = $vcVerifier;
            else $vcVerifiers[] = $vcVerifier;
        }

        return $vcVerifiers;
    }

    public function update(VCVerifier $vcVerifier): void
    {
        $entity = $vcVerifier->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(VCVerifier $vcVerifier): void
    {
        $entity = $vcVerifier->toEntity();
        $this->entityManager->delete($entity);
    }
}
