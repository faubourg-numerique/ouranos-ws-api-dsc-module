<?php

namespace API\Modules\DSC\Models;

use API\Models\Entity;
use API\StaticClasses\Utils;
use API\Traits\Updatable;
use Core\Model;

class AuthorizationRegistry extends Model
{
    use Updatable;

    const TYPE = "AuthorizationRegistry";

    public string $id;
    public string $name;
    public ?string $description = null;
    public string $identifier;
    public array $certificates = [];
    public string $scheme;
    public string $host;
    public int $port;
    public ?string $path = null;
    public string $oauth2TokenPath;
    public string $delegationPath;
    public string $policyPath;
    public string $implementationName;
    public string $implementationVersion;

    public function getUrl(): string
    {
        return Utils::buildUrl($this->scheme, $this->host, $this->port, $this->path);
    }

    public function getOauth2TokenUrl(): string
    {
        return $this->getUrl() . $this->oauth2TokenPath;
    }

    public function getDelegationUrl(): string
    {
        return $this->getUrl() . $this->delegationPath;
    }

    public function getPolicyUrl(): string
    {
        return $this->getUrl() . $this->policyPath;
    }

    public function toEntity(): Entity
    {
        $entity = new Entity();
        $entity->setId($this->id);
        $entity->setType(self::TYPE);
        $entity->setProperty("name", $this->name);
        if (!is_null($this->description)) {
            $entity->setProperty("description", $this->description);
        }
        $entity->setProperty("identifier", $this->identifier);
        $entity->setProperty("certificates", $this->certificates);
        $entity->setProperty("scheme", $this->scheme);
        $entity->setProperty("host", $this->host);
        $entity->setProperty("port", $this->port);
        if (!is_null($this->path)) {
            $entity->setProperty("path", $this->path);
        }
        $entity->setProperty("oauth2TokenPath", $this->oauth2TokenPath);
        $entity->setProperty("delegationPath", $this->delegationPath);
        $entity->setProperty("policyPath", $this->policyPath);
        $entity->setProperty("implementationName", $this->implementationName);
        $entity->setProperty("implementationVersion", $this->implementationVersion);
        return $entity;
    }

    public function fromEntity(Entity $entity): void
    {
        $this->id = $entity->getId();
        $this->name = $entity->getProperty("name");
        if ($entity->propertyExists("description")) {
            $this->description = $entity->getProperty("description");
        }
        $this->identifier = $entity->getProperty("identifier");
        if ($entity->propertyExists("certificates")) {
            $this->certificates = $entity->getProperty("certificates");
        }
        $this->scheme = $entity->getProperty("scheme");
        $this->host = $entity->getProperty("host");
        $this->port = $entity->getProperty("port");
        if ($entity->propertyExists("path")) {
            $this->path = $entity->getProperty("path");
        }
        $this->oauth2TokenPath = $entity->getProperty("oauth2TokenPath");
        $this->delegationPath = $entity->getProperty("delegationPath");
        $this->policyPath = $entity->getProperty("policyPath");
        $this->implementationName = $entity->getProperty("implementationName");
        $this->implementationVersion = $entity->getProperty("implementationVersion");
    }
}
