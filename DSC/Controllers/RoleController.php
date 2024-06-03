<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Managers\PropertyManager;
use API\Managers\TypeManager;
use API\Managers\WorkspaceManager;
use API\Modules\DSC\Managers\AuthorizationRegistryGrantManager;
use API\Modules\DSC\Managers\AuthorizationRegistryManager;
use API\Modules\DataServices\Managers\DataActionManager;
use API\Modules\DataServices\Managers\DataServiceAccessManager;
use API\Modules\DataServices\Managers\DataServiceActionManager;
use API\Modules\DataServices\Managers\DataServiceManager;
use API\Modules\DataServices\Managers\DataServicePropertyManager;
use API\Modules\DataServices\Models\DataServiceAccess;
use API\Modules\DSC\Models\DelegationEvidence;
use API\Modules\DSC\Proxies\AuthorizationRegistryProxy;
use API\Modules\DSC\Managers\RoleManager;
use API\Modules\DSC\Models\Role;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class RoleController extends Controller
{
    private WorkspaceManager $workspaceManager;
    private RoleManager $roleManager;
    private PropertyManager $propertyManager;
    private TypeManager $typeManager;
    private DataActionManager $dataActionManager;
    private DataServiceManager $dataServiceManager;
    private DataServiceActionManager $dataServiceActionManager;
    private DataServicePropertyManager $dataServicePropertyManager;
    private DataServiceAccessManager $dataServiceAccessManager;
    private AuthorizationRegistryManager $authorizationRegistryManager;
    private AuthorizationRegistryGrantManager $authorizationRegistryGrantManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
        $this->roleManager = new RoleManager($systemEntityManager);
        $this->propertyManager = new PropertyManager($systemEntityManager);
        $this->typeManager = new TypeManager($systemEntityManager);
        $this->dataActionManager = new DataActionManager($systemEntityManager);
        $this->dataServiceManager = new DataServiceManager($systemEntityManager);
        $this->dataServiceActionManager = new DataServiceActionManager($systemEntityManager);
        $this->dataServicePropertyManager = new DataServicePropertyManager($systemEntityManager);
        $this->dataServiceAccessManager = new DataServiceAccessManager($systemEntityManager);
        $this->authorizationRegistryManager = new AuthorizationRegistryManager($systemEntityManager);
        $this->authorizationRegistryGrantManager = new AuthorizationRegistryGrantManager($systemEntityManager);
    }

    public function index(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $query = "hasWorkspace==\"{$workspace->id}\"";
        $roles = $this->roleManager->readMultiple($query);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($roles, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $data = API::request()->getDecodedJsonBody();

        $role = new Role($data);
        $role->id = Utils::generateUniqueNgsiLdUrn(Role::TYPE);

        $this->roleManager->create($role);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($role, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $role = $this->roleManager->readOne($id);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($role, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $role = $this->roleManager->readOne($id);

        $data = API::request()->getDecodedJsonBody();

        $role->update($data);

        $this->roleManager->update($role);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($role, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $role = $this->roleManager->readOne($id);

        $this->roleManager->delete($role);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }

    public function synchronize(string $workspaceId, string $id): void
    {
        $data = API::request()->getDecodedJsonBody();

        $workspace = $this->workspaceManager->readOne($workspaceId);

        $authorizationRegistry = $this->authorizationRegistryManager->readOne($workspace->hasAuthorizationRegistry);
        $authorizationRegistryGrant = $this->authorizationRegistryGrantManager->readOne($workspace->hasAuthorizationRegistryGrant);

        $role = $this->roleManager->readOne($id);

        $query = "hasRole==\"{$role->id}\"";
        $dataServiceAccesses = $this->dataServiceAccessManager->readMultiple($query);

        $policies = [];
        foreach ($dataServiceAccesses as $dataServiceAccess) {
            $dataService = $this->dataServiceManager->readOne($dataServiceAccess->hasDataService);
            $type = $this->typeManager->readOne($dataService->hasEntityType);
            $dataActions = $this->dataActionManager->readMultiple(null, true);

            $query = "hasDataService==\"{$dataService->id}\"";
            $dataServiceActions = $this->dataServiceActionManager->readMultiple($query);

            $query = "hasDataService==\"{$dataService->id}\"";
            $dataServiceProperties = $this->dataServicePropertyManager->readMultiple($query);

            $properties = [];
            foreach ($dataServiceProperties as $dataServiceProperty) {
                $properties[] = $this->propertyManager->readOne($dataServiceProperty->hasProperty);
            }

            $actions = [];
            foreach ($dataServiceActions as $dataServiceAction) {
                $actions[] = $dataActions[$dataServiceAction->hasDataAction]->name;
            }

            $policies[] = [
                "target" => [
                    "resource" => [
                        "type" => $type->name,
                        "identifiers" => ["*"],
                        "attributes" => ["*"] // array_column($properties, "name")
                    ],
                    "actions" => $actions
                ],
                "rules" => [
                    [
                        "effect" => $data["effect"]
                    ]
                ]
            ];
        }

        $delegationEvidence = new DelegationEvidence();
        $delegationEvidence->delegationEvidence = [
            "notBefore" => $role->notBefore,
            "notOnOrAfter" => $role->notOnOrAfter,
            "policyIssuer" => $authorizationRegistry->identifier,
            "target" => [
                "accessSubject" => $role->name
            ],
            "policySets" => [
                [
                    "target" => [
                        "environment" => [
                            "licenses" => [
                                "ISHARE.0001"
                            ]
                        ]
                    ],
                    "policies" => $policies
                ]
            ]
        ];

        $authorizationRegistryProxy = new AuthorizationRegistryProxy($authorizationRegistry, $authorizationRegistryGrant);
        $authorizationRegistryProxy->createPolicy($delegationEvidence);

        $role->synchronized = true;
        $role->synchronizationTime = time();
        $role->lastDelegationEvidence = (array) $delegationEvidence;
        $this->roleManager->update($role);

        // API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
