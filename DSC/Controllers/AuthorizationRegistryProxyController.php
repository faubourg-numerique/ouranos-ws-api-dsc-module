<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Modules\DSC\Managers\AuthorizationRegistryGrantManager;
use API\Modules\DSC\Managers\AuthorizationRegistryManager;
use API\Modules\DSC\Models\DelegationEvidence;
use API\Modules\DSC\Models\DelegationRequest;
use API\Modules\DSC\Proxies\AuthorizationRegistryProxy;
use API\StaticClasses\Validation;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class AuthorizationRegistryProxyController extends Controller
{
    private AuthorizationRegistryProxy $authorizationRegistryProxy;

    public function __construct()
    {
        global $systemEntityManager;

        $authorizationRegistryId = API::request()->getUrlQueryParameter("authorizationRegistryId");
        $authorizationRegistryGrantId = API::request()->getUrlQueryParameter("authorizationRegistryGrantId");

        $authorizationRegistryManager = new AuthorizationRegistryManager($systemEntityManager);
        $authorizationRegistryGrantManager = new AuthorizationRegistryGrantManager($systemEntityManager);

        $authorizationRegistry = $authorizationRegistryManager->readOne($authorizationRegistryId);
        $authorizationRegistryGrant = $authorizationRegistryGrantManager->readOne($authorizationRegistryGrantId);

        $this->authorizationRegistryProxy = new AuthorizationRegistryProxy($authorizationRegistry, $authorizationRegistryGrant);
    }

    public function createPolicy(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateDelegationEvidence($data);

        $delegationEvidence = new DelegationEvidence($data);
        $this->authorizationRegistryProxy->createPolicy($delegationEvidence);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->send();
    }

    public function requestDelegation(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateDelegationRequest($data);

        $delegationRequest = new DelegationRequest($data);
        $delegationEvidence = $this->authorizationRegistryProxy->requestDelegation($delegationRequest);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($delegationEvidence, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }
}
