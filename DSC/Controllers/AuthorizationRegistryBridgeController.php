<?php

namespace API\Modules\DSC\Controllers;

use API\Managers\TypeManager;
use API\Managers\WorkspaceManager;
use API\Modules\DSC\Managers\AuthorizationRegistryGrantManager;
use API\Modules\DSC\Managers\AuthorizationRegistryManager;
use API\Modules\DSC\Managers\ContractDetailManager;
use API\Modules\DSC\Managers\ContractManager;
use API\Modules\DSC\Managers\OfferManager;
use API\Modules\DSC\Models\Contract;
use API\Modules\DSC\Models\ContractDetail;
use API\Modules\DSC\Models\DelegationEvidence;
use API\Modules\DSC\Models\Offer;
use API\StaticClasses\Validation;
use API\StaticClasses\Utils;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;
use FaubourgNumerique\IShareToolsForI4Trust\IShareToolsForI4Trust;

class AuthorizationRegistryBridgeController extends Controller
{
    private WorkspaceManager $workspaceManager;
    private TypeManager $typeManager;
    private AuthorizationRegistryManager $authorizationRegistryManager;
    private AuthorizationRegistryGrantManager $authorizationRegistryGrantManager;
    private OfferManager $offerManager;
    private ContractManager $contractManager;
    private ContractDetailManager $contractDetailManager;
    private ContractController $contractController;

    public function __construct()
    {
        global $systemEntityManager;
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
        $this->typeManager = new TypeManager($systemEntityManager);
        $this->authorizationRegistryManager = new AuthorizationRegistryManager($systemEntityManager);
        $this->authorizationRegistryGrantManager = new AuthorizationRegistryGrantManager($systemEntityManager);
        $this->offerManager = new OfferManager($systemEntityManager);
        $this->contractManager = new ContractManager($systemEntityManager);
        $this->contractDetailManager = new ContractDetailManager($systemEntityManager);
        $this->contractController = new ContractController();
    }

    public function createPolicy(string $workspaceId): void
    {
        $workspace = $this->workspaceManager->readOne($workspaceId);
        $authorizationRegistry = $this->authorizationRegistryManager->readOne($workspace->hasAuthorizationRegistry);
        $authorizationRegistryGrant = $this->authorizationRegistryGrantManager->readOne($workspace->hasAuthorizationRegistryGrant);

        $authorizationHeader = API::request()->getHeader("Authorization");
        if (!$authorizationHeader || count(explode(" ", $authorizationHeader, 2)) !== 2) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_FORBIDDEN);
            API::response()->send();
        }

        list(, $bearerToken) = explode(" ", $authorizationHeader, 2);

        try {
            IShareToolsForI4Trust::decodeJWT($bearerToken, Utils::formatCertificate($authorizationRegistryGrant->certificates[0]));
        } catch (\Exception $e) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_FORBIDDEN);
            API::response()->send();
        }

        $data = API::request()->getDecodedJsonBody();

        Validation::validateDelegationEvidence($data);

        $delegationEvidence = new DelegationEvidence($data);

        $delegationEvidencePolicyIssuer = $delegationEvidence->delegationEvidence["policyIssuer"];
        $delegationEvidenceAccessSubject = $delegationEvidence->delegationEvidence["target"]["accessSubject"];

        if ($delegationEvidencePolicyIssuer !== $authorizationRegistry->identifier) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        if (count($delegationEvidence->delegationEvidence["policySets"]) !== 1) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        $delegationEvidenceType = $delegationEvidence->delegationEvidence["policySets"][0]["policies"][0]["target"]["resource"]["type"];
        $delegationEvidenceIdentifiers = $delegationEvidence->delegationEvidence["policySets"][0]["policies"][0]["target"]["resource"]["identifiers"];
        $delegationEvidenceAttributes = $delegationEvidence->delegationEvidence["policySets"][0]["policies"][0]["target"]["resource"]["attributes"];
        $delegationEvidenceActions = $delegationEvidence->delegationEvidence["policySets"][0]["policies"][0]["target"]["actions"];
        $delegationEvidenceEffect = $delegationEvidence->delegationEvidence["policySets"][0]["policies"][0]["rules"][0]["effect"];

        $query = "hasWorkspace==\"{$workspace->id}\"";
        $offers = $this->offerManager->readMultiple($query);

        $offer = null;
        foreach ($offers as $element) {
            $type = $this->typeManager->readOne($element->hasType);

            $sortedOfferIdentifiers = $element->identifiers;
            $sortedOfferAttributes = $element->attributes;
            $sortedOfferActions = $element->actions;
            $sortedDelegationEvidenceIdentifiers = $delegationEvidenceIdentifiers;
            $sortedDelegationEvidenceAttributes = $delegationEvidenceAttributes;
            $sortedDelegationEvidenceActions = $delegationEvidenceActions;

            sort($sortedOfferIdentifiers);
            sort($sortedOfferAttributes);
            sort($sortedOfferActions);
            sort($sortedDelegationEvidenceIdentifiers);
            sort($sortedDelegationEvidenceAttributes);
            sort($sortedDelegationEvidenceActions);

            if ($type->name !== $delegationEvidenceType) continue;
            if ($element->dataServiceProviderId !== $delegationEvidencePolicyIssuer) continue;
            if ($sortedOfferIdentifiers !== $sortedDelegationEvidenceIdentifiers) continue;
            if ($sortedOfferAttributes !== $sortedDelegationEvidenceAttributes) continue;
            if ($sortedOfferActions !== $sortedDelegationEvidenceActions) continue;

            $offer = $element;
        }

        if (!$offer) {
            $types = $this->typeManager->readMultiple();

            $type = null;
            foreach ($types as $element) {
                if ($element->name !== $delegationEvidenceType) continue;
                $type = $element;
            }

            if (!$type) {
                API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
                API::response()->send();
            }

            $offer = new Offer();
            $offer->id = Utils::generateUniqueNgsiLdUrn(Offer::TYPE);
            $offer->name = implode("|", $delegationEvidenceActions) . " " . implode(",", $delegationEvidenceAttributes) . " attributes of " . implode(",", $delegationEvidenceIdentifiers) . " entities of " . $delegationEvidenceType . " type";
            $offer->dataServiceProviderId = $delegationEvidencePolicyIssuer;
            $offer->identifiers = $delegationEvidenceIdentifiers;
            $offer->attributes = $delegationEvidenceAttributes;
            $offer->actions = $delegationEvidenceActions;
            $offer->hasType = $type->id;
            $offer->hasWorkspace = $workspace->id;
            $this->offerManager->create($offer);
        }

        $query = "hasWorkspace==\"{$workspace->id}\"";
        $contracts = $this->contractManager->readMultiple($query);

        $contract = null;
        foreach ($contracts as $element) {
            if ($element->dataServiceProviderId !== $delegationEvidencePolicyIssuer) continue;
            if ($element->dataServiceConsumerId !== $delegationEvidenceAccessSubject) continue;
            if ($element->accessSubject !== $delegationEvidenceAccessSubject) continue;
            $contract = $element;
        }

        if (!$contract) {
            $contract = new Contract();
            $contract->id = Utils::generateUniqueNgsiLdUrn(Contract::TYPE);
            $contract->notBefore = time();
            $contract->notOnOrAfter = strtotime("+10 years");
            $contract->synchronized = false;
            $contract->synchronizationTime = 0;
            $contract->dataServiceProviderId = $delegationEvidencePolicyIssuer;
            $contract->dataServiceConsumerId = $delegationEvidenceAccessSubject;
            $contract->accessSubject = $delegationEvidenceAccessSubject;
            $contract->hasWorkspace = $workspace->id;
            $this->contractManager->create($contract);
        }

        $query = "hasWorkspace==\"{$workspace->id}\";hasOffer==\"{$offer->id}\";hasContract==\"{$contract->id}\"";
        $contractDetails = $this->contractDetailManager->readMultiple($query);

        $contractDetail = null;
        if ($contractDetails) {
            $contractDetail = $contractDetails[0];
        }

        if (!$contractDetail) {
            $contractDetail = new ContractDetail();
            $contractDetail->id = Utils::generateUniqueNgsiLdUrn(ContractDetail::TYPE);
            $contractDetail->permit = $delegationEvidenceEffect === "Permit";
            $contractDetail->hasOffer = $offer->id;
            $contractDetail->hasContract = $contract->id;
            $contractDetail->hasWorkspace = $workspace->id;
            $this->contractDetailManager->create($contractDetail);
        } else {
            $contractDetail->permit = $delegationEvidenceEffect === "Permit";
            $this->contractDetailManager->update($contractDetail);
        }

        $this->contractController->synchronize($workspace->id, $contract->id);
    }
}
