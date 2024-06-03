<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Modules\DSC\Managers\AuthorizationRegistryGrantManager;
use API\Modules\DSC\Models\AuthorizationRegistryGrant;
use API\StaticClasses\Validation;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class AuthorizationRegistryGrantController extends Controller
{
    private AuthorizationRegistryGrantManager $authorizationRegistryGrantManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->authorizationRegistryGrantManager = new AuthorizationRegistryGrantManager($systemEntityManager);
    }

    public function index(): void
    {
        $authorizationRegistryGrants = $this->authorizationRegistryGrantManager->readMultiple();

        foreach (array_keys($authorizationRegistryGrants) as $index) {
            $authorizationRegistryGrants[$index]->privateKey = null;
        }

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistryGrants, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateAuthorizationRegistryGrant($data);

        $authorizationRegistryGrant = new AuthorizationRegistryGrant($data);
        $authorizationRegistryGrant->id = Utils::generateUniqueNgsiLdUrn(AuthorizationRegistryGrant::TYPE);
        $this->authorizationRegistryGrantManager->create($authorizationRegistryGrant);

        $authorizationRegistryGrant->privateKey = null;

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistryGrant, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $id): void
    {
        $authorizationRegistryGrant = $this->authorizationRegistryGrantManager->readOne($id);

        $authorizationRegistryGrant->privateKey = null;

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistryGrant, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $id): void
    {
        $authorizationRegistryGrant = $this->authorizationRegistryGrantManager->readOne($id);

        $data = API::request()->getDecodedJsonBody();

        Validation::validateAuthorizationRegistryGrant($data);

        $authorizationRegistryGrant->update($data);

        $this->authorizationRegistryGrantManager->update($authorizationRegistryGrant);

        $authorizationRegistryGrant->privateKey = null;

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($authorizationRegistryGrant, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $id): void
    {
        $authorizationRegistryGrant = $this->authorizationRegistryGrantManager->readOne($id);

        $this->authorizationRegistryGrantManager->delete($authorizationRegistryGrant);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
