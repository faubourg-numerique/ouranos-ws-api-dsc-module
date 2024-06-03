<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Managers\TypeManager;
use API\Managers\WorkspaceManager;
use API\Managers\ServiceManager;
use API\Modules\DSC\Managers\VCVerifierManager;
use API\Modules\DSC\Managers\TrustedIssuersListManager;
use API\Modules\DSC\Managers\AuthorizationRegistryGrantManager;
use API\Modules\DSC\Managers\AuthorizationRegistryManager;
use API\Modules\DSC\Managers\ContractDetailManager;
use API\Modules\DSC\Managers\ContractManager;
use API\Modules\DSC\Managers\RoleManager;
use API\Modules\DSC\Models\Contract;
use API\Modules\DSC\Models\DelegationEvidence;
use API\Modules\DSC\Proxies\AuthorizationRegistryProxy;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;
use Core\HttpRequestMethods;
use Core\Helpers\RequestHelper;

class ContractController extends Controller
{
    private WorkspaceManager $workspaceManager;
    private ContractManager $contractManager;
    private ContractDetailManager $contractDetailManager;
    private TypeManager $typeManager;
    private AuthorizationRegistryManager $authorizationRegistryManager;
    private AuthorizationRegistryGrantManager $authorizationRegistryGrantManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
        $this->contractManager = new ContractManager($systemEntityManager);
        $this->contractDetailManager = new ContractDetailManager($systemEntityManager);
        $this->typeManager = new TypeManager($systemEntityManager);
        $this->authorizationRegistryManager = new AuthorizationRegistryManager($systemEntityManager);
        $this->authorizationRegistryGrantManager = new AuthorizationRegistryGrantManager($systemEntityManager);
        $this->serviceManager = new ServiceManager($systemEntityManager);
        $this->vcVerifierManager = new VCVerifierManager($systemEntityManager);
        $this->trustedIssuersListManager = new TrustedIssuersListManager($systemEntityManager);
        $this->roleManager = new RoleManager($systemEntityManager);
    }

    public function index(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $query = "hasWorkspace==\"{$workspace->id}\"";
        $contracts = $this->contractManager->readMultiple($query);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contracts, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $data = API::request()->getDecodedJsonBody();

        //Validation::validateContract($data);

        $contract = new Contract($data);
        $contract->id = Utils::generateUniqueNgsiLdUrn(Contract::TYPE);

        //if ($contract->hasWorkspace !== $workspace->id) {
        //    throw new ContractControllerException\BadWorkspaceException();
        //}

        $this->contractManager->create($contract);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contract, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contract = $this->contractManager->readOne($id);

        //if ($contract->hasWorkspace !== $workspace->id) {
        //    throw new ContractControllerException\BadWorkspaceException();
        //}

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contract, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contract = $this->contractManager->readOne($id);

        //if ($contract->hasWorkspace !== $workspace->id) {
        //    throw new ContractControllerException\BadWorkspaceException();
        //}

        $data = API::request()->getDecodedJsonBody();

        // Validation::validateContract($data);

        $contract->update($data);

        $this->contractManager->update($contract);

        $this->contractManager->update($contract);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($contract, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);

        $contract = $this->contractManager->readOne($id);

        //if ($contract->hasWorkspace !== $workspace->id) {
        //    throw new ContractControllerException\BadWorkspaceException();
        //}

        $query = "hasContract==\"{$contract->id}\"";
        $contractDetails = $this->contractDetailManager->readMultiple($query);

        //if ($contractDetails) {
        //    throw new ContractControllerException\RelationshipException();
        //}

        $this->contractManager->delete($contract);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }

    public function synchronize(string $workspaceId, string $id): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);
        $service = $this->serviceManager->readOne($workspace->hasService);
        $vcVerifier = $this->vcVerifierManager->readOne($service->hasVCVerifier);
        $trustedIssuersList = $this->trustedIssuersListManager->readOne($service->hasTrustedIssuersList);

        $contract = $this->contractManager->readOne($id);

        $query = "hasContract==\"{$contract->id}\"";
        $contractDetails = $this->contractDetailManager->readMultiple($query);

        $request = new RequestHelper();
        $request->setMethod(HttpRequestMethods::DELETE);
        $request->setUrl("{$trustedIssuersList->getUrl()}/issuer/{$contract->stakeholderDid}");
        $request->setTimeout($_ENV["REQUESTS_TIMEOUT"]);
        if ($trustedIssuersList->disableCertificateVerification) {
            $request->setDisableCertificateVerification(true);
        }
        $request->send();

        $data = [
            "did" => $contract->stakeholderDid,
            "credentials" => [
                [
                    "credentialsType" => $contract->verifiableCredentialType,
                    "claims" => [
                        [
                            "name" => "roles",
                            "allowedValues" => [
                                [
                                    [
                                        "names" => [],
                                        "target" => $vcVerifier->did
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        foreach ($contractDetails as $contractDetail) {
            $role = $this->roleManager->readOne($contractDetail->hasRole);
            $data["credentials"][0]["claims"][0]["allowedValues"][0][0]["names"][] = $role->name;
        }

        $request = new RequestHelper();
        $request->setMethod(HttpRequestMethods::POST);
        $request->setUrl("{$trustedIssuersList->getUrl()}/issuer");
        $request->setHeader("Content-Type", MimeType::Json->value);
        $request->setJsonBody($data, JSON_UNESCAPED_SLASHES);
        $request->setTimeout($_ENV["REQUESTS_TIMEOUT"]);
        if ($trustedIssuersList->disableCertificateVerification) {
            $request->setDisableCertificateVerification(true);
        }
        $response = $request->send();

        if ($response->getError()) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_GATEWAY);
            API::response()->send();
        }

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
