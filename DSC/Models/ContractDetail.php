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
    public ?string $scopeType;
    public ?string $scopeEntity;

    public function toEntity(): Entity
    {
        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setRelationship("hasRole", $this->hasRole);
        $entity->setRelationship("hasContract", $this->hasContract);
        $entity->setRelationship("hasWorkspace", $this->hasWorkspace);
        if (!is_null($this->scopeType)) {
            $entity->setRelationship("scopeType", $this->scopeType);
        }
        if (!is_null($this->scopeEntity)) {
            $entity->setRelationship("scopeEntity", $this->scopeEntity);
        }
        return $entity;
    }

    public function fromEntity(Entity $entity): void
    {
        $this->id = $entity->getId();
        $this->hasRole = $entity->getRelationship("hasRole");
        $this->hasContract = $entity->getRelationship("hasContract");
        $this->hasWorkspace = $entity->getRelationship("hasWorkspace");
        if ($entity->relationshipExists("scopeType")) {
            $this->scopeType = $entity->getRelationship("scopeType");
        }
        if ($entity->relationshipExists("scopeEntity")) {
            $this->scopeEntity = $entity->getRelationship("scopeEntity");
        }
    }
}
