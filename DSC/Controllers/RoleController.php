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
        $rego = "";
        foreach ($dataServiceAccesses as $dataServiceAccess) {
            $dataService = $this->dataServiceManager->readOne($dataServiceAccess->hasDataService);
            $type = $this->typeManager->readOne($dataService->hasEntityType);
            $dataActions = $this->dataActionManager->readMultiple(null, true);

            $query = "hasDataService==\"{$dataService->id}\"";
            $dataServiceActions = $this->dataServiceActionManager->readMultiple($query);

            // $query = "hasDataService==\"{$dataService->id}\"";
            // $dataServiceProperties = $this->dataServicePropertyManager->readMultiple($query);

            // $properties = [];
            // foreach ($dataServiceProperties as $dataServiceProperty) {
            //     $properties[] = $this->propertyManager->readOne($dataServiceProperty->hasProperty);
            // }

            $actions = [];
            foreach ($dataServiceActions as $dataServiceAction) {
                $actions[] = $dataActions[$dataServiceAction->hasDataAction]->name;
            }

            $policy = [
                "@context" => [
                    "odrl" => "http://www.w3.org/ns/odrl/2/",
                    "dc" => "http://purl.org/dc/elements/1.1/",
                    "dct" => "http://purl.org/dc/terms/",
                    "owl" => "http://www.w3.org/2002/07/owl#",
                    "rdfs" => "http://www.w3.org/2000/01/rdf-schema#",
                    "skos" => "http://www.w3.org/2004/02/skos/core#"
                ],
                "@id" => md5($dataServiceAccess->id),
                "@type" => "odrl:Policy",
                "odrl:permission" => [
                    "odrl:target" => [
                        "@type" => "odrl:AssetCollection",
                        "odrl:source" => "urn:asset",
                        "odrl:refinement" => [
                            [
                                "@type" => "odrl:Constraint",
                                "odrl:leftOperand" => "ngsi-ld:entityType",
                                "odrl:operator" => [
                                    "@id" => "odrl:eq"
                                ],
                                "odrl:rightOperand" => $type->name
                            ]
                        ]
                    ],
                    "odrl:assignee" => $authorizationRegistryGrant->identifier,
                    "odrl:action" => []
                ]
            ];

            if (array_intersect(["GET"], $actions)) {
                $policy["odrl:permission"]["odrl:action"][] = "odrl:read";
            }

            if (array_intersect(["PUT", "POST", "PATCH"], $actions)) {
                $policy["odrl:permission"]["odrl:action"][] = "odrl:modify";
            }

            if (array_intersect(["DELETE"], $actions)) {
                $policy["odrl:permission"]["odrl:action"][] = "odrl:delete";
            }

            $policies[] = $policy;

            $authorizationRegistryProxy = new AuthorizationRegistryProxy($authorizationRegistry, $authorizationRegistryGrant);
            $rego .= $authorizationRegistryProxy->createPolicyOdrl($policy);
            $rego .= "\n\n-----\n\n";
        }

        $role->synchronized = true;
        $role->synchronizationTime = time();
        $role->lastDelegationEvidence = json_encode($policies);
        $this->roleManager->update($role);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::TextPlain->value);
        API::response()->setBody($rego);
        API::response()->send();
    }
}
