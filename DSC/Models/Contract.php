<?php

namespace API\Modules\DSC\Models;

use API\Models\Entity;
use API\Traits\Updatable;
use Core\Model;

class Contract extends Model
{
    use Updatable;

    const TYPE = "Contract";

    public string $id;
    public string $contractType;
    public string $stakeholderDid;
    public string $stakeholderName;
    public int $validFromTime;
    public int $validToTime;
    public string $verifiableCredentialType;
    public string $hasWorkspace;
    public ?string $scopeType;
    public ?string $scopeEntity;

    public function toEntity(): Entity
    {
        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setProperty("contractType", $this->contractType);
        $entity->setProperty("stakeholderDid", $this->stakeholderDid);
        $entity->setProperty("stakeholderName", $this->stakeholderName);
        $entity->setProperty("validFromTime", $this->validFromTime);
        $entity->setProperty("validToTime", $this->validToTime);
        $entity->setProperty("verifiableCredentialType", $this->verifiableCredentialType);
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
        $this->contractType = $entity->getProperty("contractType");
        $this->stakeholderDid = $entity->getProperty("stakeholderDid");
        $this->stakeholderName = $entity->getProperty("stakeholderName");
        $this->validFromTime = $entity->getProperty("validFromTime");
        $this->validToTime = $entity->getProperty("validToTime");
        $this->verifiableCredentialType = $entity->getProperty("verifiableCredentialType");
        $this->hasWorkspace = $entity->getRelationship("hasWorkspace");
        if ($entity->relationshipExists("scopeType")) {
            $this->scopeType = $entity->getRelationship("scopeType");
        }
        if ($entity->relationshipExists("scopeEntity")) {
            $this->scopeEntity = $entity->getRelationship("scopeEntity");
        }
    }
}
