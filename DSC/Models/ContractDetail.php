<?php

namespace API\Modules\DSC\Models;

use API\Models\Entity;
use API\Traits\Updatable;
use Core\Model;

class ContractDetail extends Model
{
    use Updatable;

    const TYPE = "ContractDetail";

    public string $id;
    public string $hasRole;
    public string $hasContract;
    public string $hasWorkspace;

    public function toEntity(): Entity
    {
        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setRelationship("hasRole", $this->hasRole);
        $entity->setRelationship("hasContract", $this->hasContract);
        $entity->setRelationship("hasWorkspace", $this->hasWorkspace);
        return $entity;
    }

    public function fromEntity(Entity $entity): void
    {
        $this->id = $entity->getId();
        $this->hasRole = $entity->getRelationship("hasRole");
        $this->hasContract = $entity->getRelationship("hasContract");
        $this->hasWorkspace = $entity->getRelationship("hasWorkspace");
    }
}
