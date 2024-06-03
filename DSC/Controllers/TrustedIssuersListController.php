<?php

namespace API\Modules\DSC\Controllers;

use API\StaticClasses\Utils;
use API\StaticClasses\Validation;
use API\Enums\MimeType;
use API\Modules\DSC\Managers\TrustedIssuersListManager;
use API\Modules\DSC\Models\TrustedIssuersList;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class TrustedIssuersListController extends Controller
{
    private TrustedIssuersListManager $trustedIssuersListManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->trustedIssuersListManager = new TrustedIssuersListManager($systemEntityManager);
    }

    public function index(): void
    {
        $trustedIssuersLists = $this->trustedIssuersListManager->readMultiple();

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($trustedIssuersLists, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateTrustedIssuersList($data);

        $trustedIssuersList = new TrustedIssuersList($data);
        $trustedIssuersList->id = Utils::generateUniqueNgsiLdUrn(TrustedIssuersList::TYPE);
        $this->trustedIssuersListManager->create($trustedIssuersList);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($trustedIssuersList, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $id): void
    {
        $trustedIssuersList = $this->trustedIssuersListManager->readOne($id);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($trustedIssuersList, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $id): void
    {
        $trustedIssuersList = $this->trustedIssuersListManager->readOne($id);

        $data = API::request()->getDecodedJsonBody();

        Validation::validateTrustedIssuersList($data);

        $trustedIssuersList->update($data);

        $this->trustedIssuersListManager->update($trustedIssuersList);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($trustedIssuersList, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $id): void
    {
        $trustedIssuersList = $this->trustedIssuersListManager->readOne($id);

        $this->trustedIssuersListManager->delete($trustedIssuersList);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
