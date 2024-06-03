<?php

namespace API\Modules\DSC\Managers;

use API\Managers\EntityManager;
use API\Modules\DSC\Models\ContractDetail;

class ContractDetailManager
{
    private EntityManager $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function create(ContractDetail $contractDetail): void
    {
        $entity = $contractDetail->toEntity();
        $this->entityManager->create($entity);
    }

    public function readOne(string $id): ContractDetail
    {
        $entity = $this->entityManager->readOne($id);
        $contractDetail = new ContractDetail();
        $contractDetail->fromEntity($entity);
        return $contractDetail;
    }

    public function readMultiple(?string $query = null, bool $idAsKey = false): array
    {
        $entities = $this->entityManager->readMultiple(null, ContractDetail::TYPE, $query);

        $contractDetails = [];
        foreach ($entities as $entity) {
            $contractDetail = new ContractDetail();
            $contractDetail->fromEntity($entity);
            if ($idAsKey) $contractDetails[$contractDetail->id] = $contractDetail;
            else $contractDetails[] = $contractDetail;
        }

        return $contractDetails;
    }

    public function update(ContractDetail $contractDetail): void
    {
        $entity = $contractDetail->toEntity();
        $this->entityManager->update($entity);
    }

    public function delete(ContractDetail $contractDetail): void
    {
        $entity = $contractDetail->toEntity();
        $this->entityManager->delete($entity);
    }
}
