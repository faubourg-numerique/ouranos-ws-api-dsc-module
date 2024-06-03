<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Modules\DSC\Managers\AuthorizationRegistryManager;
use API\Modules\DSC\Models\AuthorizationRegistry;
use API\StaticClasses\Validation;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class AuthorizationRegistryController extends Controller
{
    private AuthorizationRegistryManager $authorizationRegistryManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->authorizationRegistryManager = new AuthorizationRegistryManager($systemEntityManager);
    }

    public function index(): void
    {
        $authorizationRegistries = $this->authorizationRegistryManager->readMultiple();

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistries, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateAuthorizationRegistry($data);

        $authorizationRegistry = new AuthorizationRegistry($data);
        $authorizationRegistry->id = Utils::generateUniqueNgsiLdUrn(AuthorizationRegistry::TYPE);
        $this->authorizationRegistryManager->create($authorizationRegistry);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistry, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $id): void
    {
        $authorizationRegistry = $this->authorizationRegistryManager->readOne($id);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistry, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $id): void
    {
        $authorizationRegistry = $this->authorizationRegistryManager->readOne($id);

        $data = API::request()->getDecodedJsonBody();

        Validation::validateAuthorizationRegistry($data);

        $authorizationRegistry->update($data);

        $this->authorizationRegistryManager->update($authorizationRegistry);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistry, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $id): void
    {
        $authorizationRegistry = $this->authorizationRegistryManager->readOne($id);

        $this->authorizationRegistryManager->delete($authorizationRegistry);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
