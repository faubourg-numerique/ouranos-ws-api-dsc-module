<?php

namespace API\Modules\DSC\Controllers;

use API\Enums\MimeType;
use API\Managers\WorkspaceManager;
use API\Modules\DSC\Managers\ContractDetailManager;
use API\Modules\DSC\Managers\ContractManager;
use API\Modules\DSC\Models\ContractDetail;
use API\StaticClasses\Validation;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class ContractDetailController extends Controller
{
    private WorkspaceManager $workspaceManager;
    private ContractDetailManager $contractDetailManager;
    private ContractManager $contractManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
        $this->contractDetailManager = new ContractDetailManager($systemEntityManager);
        $this->contractManager = new ContractManager($systemEntityManager);
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
}
