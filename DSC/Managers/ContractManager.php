<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\Contract;

class ContractManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(Contract $contract): void
    {
        $entity = $contract->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): Contract
    {
        $entity = $this->entityManager->readOne($id);
        $contract = new Contract();
        $contract->fromEntity($entity);
        return $contract;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, Contract::TYPE, $query);

        $contracts = [];
        foreach ($entities as $entity) {
            $contract = new Contract();
            $contract->fromEntity($entity);
            if ($idAsKey) $contracts[$contract->id] = $contract;
            else $contracts[] = $contract;
        }

        return $contracts;
    }

    public function update(Contract $contract): void
    {
        $entity = $contract->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(Contract $contract): void
    {
        $entity = $contract->toEntity();
        $this->entityManager->delete($entity);
    }
}
