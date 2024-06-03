<?php

namespace API\Modules\DSC\Models;

use API\Models\Entity;
use API\Traits\Updatable;
use Core\Model;
use Defuse\Crypto\Crypto;

class AuthorizationRegistryGrant extends Model
{
    use Updatable;

    const TYPE = "AuthorizationRegistryGrant";

    public string $id;
    public string $name;
    public ?string $description = null;
    public string $identifier;
    public array $certificates;
    public ?string $privateKey = null;

    public function toEntity(): Entity
    {
        global $encryptionKey;

        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setProperty("name", $this->name);
        if (!is_null($this->description)) {
            $entity->setProperty("description", $this->description);
        }
        $entity->setProperty("identifier", $this->identifier);
        $entity->setProperty("certificates", $this->certificates);
        $entity->setProperty("privateKey", Crypto::encrypt($this->privateKey, $encryptionKey, false));
        return $entity;
    }

    public function fromEntity(Entity $entity): void
    {
        global $encryptionKey;

        $this->id = $entity->getId();
        $this->name = $entity->getProperty("name");
        if ($entity->propertyExists("description")) {
            $this->description = $entity->getProperty("description");
        }
        $this->identifier = $entity->getProperty("identifier");
        $this->certificates = $entity->getProperty("certificates");
        $this->privateKey = Crypto::decrypt($entity->getProperty("privateKey"), $encryptionKey, false);
    }
}
