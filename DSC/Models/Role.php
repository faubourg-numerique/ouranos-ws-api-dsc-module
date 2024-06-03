<?php

namespace API\Modules\DSC\Models;

use API\Models\Entity;
use API\Traits\Updatable;
use Core\Model;

class Role extends Model
{
    use Updatable;

    const TYPE = "Role";

    public string $id;
    public string $name;
    public int $notBefore;
    public int $notOnOrAfter;
    public bool $synchronized;
    public int $synchronizationTime;
    public string $dataServiceProviderId;
    public ?array $lastDelegationEvidence = null;
    public string $verifiableCredentialType;
    public string $hasWorkspace;

    public function toEntity(): Entity
    {
        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setProperty("name", $this->name);
        $entity->setProperty("notBefore", $this->notBefore);
        $entity->setProperty("notOnOrAfter", $this->notOnOrAfter);
        $entity->setProperty("synchronized", $this->synchronized);
        $entity->setProperty("synchronizationTime", $this->synchronizationTime);
        $entity->setProperty("dataServiceProviderId", $this->dataServiceProviderId);
        if (!is_null($this->lastDelegationEvidence)) {
            $entity->setProperty("lastDelegationEvidence", $this->lastDelegationEvidence);
        }
        $entity->setProperty("verifiableCredentialType", $this->verifiableCredentialType);
        $entity->setRelationship("hasWorkspace", $this->hasWorkspace);
        return $entity;
    }

    public function fromEntity(Entity $entity): void
    {
        $this->id = $entity->getId();
        $this->name = $entity->getProperty("name");
        $this->notBefore = $entity->getProperty("notBefore");
        $this->notOnOrAfter = $entity->getProperty("notOnOrAfter");
        $this->synchronized = $entity->getProperty("synchronized");
        $this->synchronizationTime = $entity->getProperty("synchronizationTime");
        $this->dataServiceProviderId = $entity->getProperty("dataServiceProviderId");
        if ($entity->propertyExists("lastDelegationEvidence")) {
            $this->lastDelegationEvidence = $entity->getProperty("lastDelegationEvidence");
        }
        $this->verifiableCredentialType = $entity->getProperty("verifiableCredentialType");
        $this->hasWorkspace = $entity->getRelationship("hasWorkspace");
    }
}
