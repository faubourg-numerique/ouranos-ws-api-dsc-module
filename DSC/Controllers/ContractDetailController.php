<?php

namespace API\Modules\DSC\Controllers;

use API\Controllers\EntityController;
use API\Enums\MimeType;
use API\Models\ErrorInfo;
use API\Managers\TypeManager;
use API\Managers\WorkspaceManager;
use API\Managers\IdentityManagerManager;
use API\Managers\IdentityManagerGrantManager;
use API\Modules\DataServices\Managers\DataServiceManager;
use API\Modules\DataServices\Managers\DataServiceAccessManager;
use API\Modules\DataServices\Managers\DataServiceActionManager;
use API\Modules\DataServices\Managers\DataActionManager;
use API\Modules\DSC\Managers\ContractDetailManager;
use API\Modules\DSC\Managers\ContractManager;
use API\Modules\DSC\Managers\RoleManager;
use API\Modules\DSC\Models\ContractDetail;
use API\StaticClasses\Validation;
use API\StaticClasses\Utils;
use Core\Helpers\RequestHelper;
use Core\API;
use Core\Controller;
use Core\HttpRequestMethods;
use Core\HttpResponseStatusCodes;

class ContractDetailController extends Controller
{
    private TypeManager $typeManager;
    private WorkspaceManager $workspaceManager;
    private IdentityManagerManager $identityManagerManager;
    private IdentityManagerGrantManager $identityManagerGrantManager;
    private DataServiceManager $dataServiceManager;
    private DataServiceAccessManager $dataServiceAccessManager;
    private DataServiceActionManager $dataServiceActionManager;
    private DataActionManager $dataActionManager;
    private ContractDetailManager $contractDetailManager;
    private ContractManager $contractManager;
    private RoleManager $roleManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->typeManager = new TypeManager($systemEntityManager);
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
        $this->identityManagerManager = new IdentityManagerManager($systemEntityManager);
        $this->identityManagerGrantManager = new IdentityManagerGrantManager($systemEntityManager);
        $this->dataServiceManager = new DataServiceManager($systemEntityManager);
        $this->dataServiceAccessManager = new DataServiceAccessManager($systemEntityManager);
        $this->dataServiceActionManager = new DataServiceActionManager($systemEntityManager);
        $this->dataActionManager = new DataActionManager($systemEntityManager);
        $this->contractDetailManager = new ContractDetailManager($systemEntityManager);
        $this->contractManager = new ContractManager($systemEntityManager);
        $this->roleManager = new RoleManager($systemEntityManager);
    }

    public function index(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $query = "hasWorkspace==\"{$workspace->id}\"";
        $contractDetails = $this->contractDetailManager->readMultiple($query);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contractDetails, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $data = API::request()->getDecodedJsonBody();

        //Validation::validateContractDetail($data);

        $contractDetail = new ContractDetail($data);
        $contractDetail->id = Utils::generateUniqueNgsiLdUrn(ContractDetail::TYPE);

        //if ($contractDetail->hasWorkspace !== $workspace->id) {
        //    throw new ContractDetailControllerException\BadWorkspaceException();
        //}

        $this->contractDetailManager->create($contractDetail);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contractDetail, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contractDetail = $this->contractDetailManager->readOne($id);

        //if ($contractDetail->hasWorkspace !== $workspace->id) {
        //    throw new ContractDetailControllerException\BadWorkspaceException();
        //}

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contractDetail, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contractDetail = $this->contractDetailManager->readOne($id);

        $contract = $this->contractManager->readOne($contractDetail->hasContract);

        //if ($contractDetail->hasWorkspace !== $workspace->id) {
        //    throw new ContractDetailControllerException\BadWorkspaceException();
        //}

        $data = API::request()->getDecodedJsonBody();

        // Validation::validateContractDetail($data);

        $contractDetail->update($data);

        $this->contractDetailManager->update($contractDetail);

        $this->contractManager->update($contract);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contractDetail, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contractDetail = $this->contractDetailManager->readOne($id);

        //if ($contractDetail->hasWorkspace !== $workspace->id) {
        //    throw new ContractDetailControllerException\BadWorkspaceException();
        //}

        $this->contractDetailManager->delete($contractDetail);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }

    public function synchronize(string $workspaceId, string $id): void
    {
        $errorInfo = new ErrorInfo();

        $workspace = $this->workspaceManager->readOne($workspaceId);

        if (!$workspace->hasIdentityManager || !$workspace->hasIdentityManagerGrant) {
            $errorInfo->title = "An identity manager and/or an identity manager grant have not been defined in the workspace";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $identityManager = $this->identityManagerManager->readOne($workspace->hasIdentityManager);
        $identityManagerGrant = $this->identityManagerGrantManager->readOne($workspace->hasIdentityManagerGrant);

        if ($identityManagerGrant->grantType !== "password") {
            $errorInfo->title = "The grant type of the identity manager of the workspace must be set to password";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $contractDetail = $this->contractDetailManager->readOne($id);
        $contract = $this->contractManager->readOne($contractDetail->hasContract);
        $role = $this->roleManager->readOne($contractDetail->hasRole);

        if (!$contract->scopeType || !$contractDetail->scopeType || !$contract->scopeEntity || !$contractDetail->scopeEntity) {
            $errorInfo->title = "The scope of the contract or contract details is not correctly defined";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $query = "hasRole==\"{$role->id}\"";
        $dataServiceAccesses = $this->dataServiceAccessManager->readMultiple($query);

        $contractScopeType = $this->typeManager->readOne($contract->scopeType);
        $contractDetailScopeType = $this->typeManager->readOne($contractDetail->scopeType);

        $entityController = new EntityController();
        $entityManager = $entityController->buildEntityManager($workspace);

        $contractScopeEntity = $entityManager->readOne($contract->scopeEntity);
        $contractDetailScopeEntity = $entityManager->readOne($contractDetail->scopeEntity);

        if (!$contractScopeType->scopeName || !$contractDetailScopeType->scopeName) {
            $errorInfo->title = "The scope name of the scope type of the contract or contract detail has not been defined";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        if (!$contractScopeEntity->getProperty("name") || !$contractDetailScopeEntity->getProperty("name")) {
            $errorInfo->title = "A name is missing from scope entities";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $idm = new \GuzzleHttp\Client([
            "base_uri" => $identityManager->getUrl(),
            "timeout"  => $_ENV["REQUESTS_TIMEOUT"]
        ]);

        $errorInfo->details[] = "Requesting an access token to the Keyrock API…";

        $response = null;
        try {
            $response = $idm->post("v1/auth/tokens", [
                "json" => [
                    "name" => $identityManagerGrant->username,
                    "password" => $identityManagerGrant->password
                ]
            ]);
        } catch(\Exception $exception) {
            $errorInfo->title = "Failed to obtain an access token from Keyrock";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        if (!$response->hasHeader("X-Subject-Token")) {
            $errorInfo->title = "Failed to obtain an access token from Keyrock";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $accessToken = $response->getHeader("X-Subject-Token");

        $idm = new \GuzzleHttp\Client([
            "base_uri" => $identityManager->getUrl(),
            "timeout"  => $_ENV["REQUESTS_TIMEOUT"],
            "headers" => [
                "X-Auth-Token" => $accessToken
            ]
        ]);

        $errorInfo->details[] = "Requesting the roles for the '{$identityManagerGrant->clientId}' application…";

        $response = null;
        try {
            $response = $idm->get("v1/applications/{$identityManagerGrant->clientId}/roles");
        } catch(\Exception $exception) {
            $errorInfo->title = "Failed to obtain the roles for the '{$identityManagerGrant->clientId}' application";

            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
            API::response()->setHeader("Content-Type", MimeType::Json->value);
            API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
            API::response()->send();
        }

        $data = json_decode($response->getBody(), true);
        $roles = $data["roles"];

        $errorInfo->details[] = "Looking for the role with the name '{$contractDetail->roleScopeName}'…";

        $roleId = null;
        foreach ($roles as $role) {
            if ($role["name"] === $contractDetail->roleScopeName) {
                $roleId = $role["id"];
                break;
            }
        }

        if (!$roleId) {
            $errorInfo->details[] = "Role with name '{$contractDetail->roleScopeName}' not found, creating this role…";

            $response = null;
            try {
                $response = $idm->post("v1/applications/{$identityManagerGrant->clientId}/roles", [
                    "json" => [
                        "role" => [
                            "name" => $contractDetail->roleScopeName
                        ]
                    ]
                ]);
            } catch(\Exception $exception) {
                $errorInfo->title = "Failed to obtain the roles for the '{$identityManagerGrant->clientId}' application";

                API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_INTERNAL_SERVER_ERROR);
                API::response()->setHeader("Content-Type", MimeType::Json->value);
                API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
                API::response()->send();
            }

            $data = json_decode($response->getBody(), true);
            $role = $data["role"];
            $roleId = $role["id"];
        } else {
            $errorInfo->details[] = "Role with name '{$contractDetail->roleScopeName}' found '{$roleId}', skipping…";
        }

        $errorInfo->details[] = "Requesting the permissions assigned to the '{$roleId}' role…";

        $response = null;
        try {
            $response = $idm->get("v1/applications/{$identityManagerGrant->clientId}/roles/{$roleId}/permissions");
        } catch(\Exception $exception) {
            $errorInfo->title = "Failed to obtain the permissions assigned to the '{$roleId}' role";
        }
        if ($response) {
            $data = json_decode($response->getBody(), true);
            $permissions = $data["role_permission_assignments"];

            if ($permissions) {
                $errorInfo->details[] = count($permissions) . " permission(s) found, removing…";

                foreach ($permissions as $permission) {
                    $errorInfo->details[] = "Removing '{$permission["id"]}' permission…";

                    try {
                        $idm->delete("v1/applications/{$identityManagerGrant->clientId}/permissions/{$permission["id"]}");
                    } catch(\Exception $exception) {
                        $errorInfo->details[] = "❌ Failed to remove the '{$permission["id"]}' permission";
                    }
                }
            } else {
                $errorInfo->details[] = "No permission found, skipping…";
            }
        }

        $baseEndpoint = "/{$contractScopeType->scopeName}/{$contract->scopeEntity}/{$contractDetailScopeType->scopeName}/{$contractDetail->scopeEntity}";

        foreach ($dataServiceAccesses as $dataServiceAccess) {
            $dataService = $this->dataServiceManager->readOne($dataServiceAccess->hasDataService);
            $type = $this->typeManager->readOne($dataService->hasEntityType);

            if (!$type->scopeName) {
                $errorInfo->details[] = "❌ A scope name is missing for the '{$type->name}' type";
                continue;
            }

            $query = "hasDataService==\"{$dataService->id}\"";
            $dataServiceActions = $this->dataServiceActionManager->readMultiple($query);

            foreach ($dataServiceActions as $dataServiceAction) {
                $dataAction = $this->dataActionManager->readOne($dataServiceAction->hasDataAction);

                if ($dataAction->name === "GET") {
                    $data = [
                        "permission" => [
                            "name" => "{$dataAction->name} {$contractScopeEntity->getProperty("name")} {$contractDetailScopeEntity->getProperty("name")} {$type->scopeName}",
                            "description" => "{$dataAction->name} {$contractScopeEntity->getProperty("name")} {$contractDetailScopeEntity->getProperty("name")} {$type->scopeName}",
                            "action" => $dataAction->name,
                            "resource" => $baseEndpoint . '/' . $type->scopeName,
                            "is_regex" => false
                        ]
                    ];
                } else {
                    $data = [
                        "permission" => [
                            "name" => "{$dataAction->name} {$contractScopeEntity->getProperty("name")} {$contractDetailScopeEntity->getProperty("name")} {$type->scopeName}",
                            "description" => "{$dataAction->name} {$contractScopeEntity->getProperty("name")} {$contractDetailScopeEntity->getProperty("name")} {$type->scopeName}",
                            "action" => $dataAction->name,
                            "resource" => '^' . $baseEndpoint . '/' . $type->scopeName . '/(.*)$',
                            "is_regex" => true
                        ]
                    ];
                }

                $errorInfo->details[] = "Creating a permission with the name '{$data["permission"]["name"]}'…";

                $response = null;
                try {
                    $response = $idm->post("v1/applications/{$identityManagerGrant->clientId}/permissions", [
                        "json" => $data
                    ]);
                } catch(\Exception $exception) {
                    $errorInfo->details[] = "❌ Failed to create the permission with the name '{$data["permission"]["name"]}'";
                    continue;
                }

                $data = json_decode($response->getBody(), true);
                $permission = $data["permission"];

                $errorInfo->details[] = "Assigning the '{$permission["id"]}' permission with the name '{$permission["name"]}' to the '{$roleId}' role…";

                try {
                    $idm->put("v1/applications/{$identityManagerGrant->clientId}/roles/{$roleId}/permissions/{$permission["id"]}", [
                        "headers" => [
                            "Content-Type" => "application/json"
                        ]
                    ]);
                } catch(\Exception $exception) {
                    $errorInfo->details[] = "❌ Failed to assign the '{$permission["id"]}' permission with the name '{$permission["name"]}' to the '{$roleId}' role";
                    continue;
                }
            }
        }

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($errorInfo, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }
}
