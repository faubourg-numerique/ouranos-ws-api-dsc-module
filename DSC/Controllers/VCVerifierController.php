<?php

namespace API\Modules\DSC\Controllers;

use API\StaticClasses\Utils;
use API\StaticClasses\Validation;
use API\Enums\MimeType;
use API\Modules\DSC\Managers\VCVerifierManager;
use API\Modules\DSC\Models\VCVerifier;
use Core\API;
use Core\Controller;
use Core\HttpResponseStatusCodes;

class VCVerifierController extends Controller
{
    private VCVerifierManager $vcVerifierManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->vcVerifierManager = new VCVerifierManager($systemEntityManager);
    }

    public function index(): void
    {
        $vcVerifiers = $this->vcVerifierManager->readMultiple();

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($vcVerifiers, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function store(): void
    {
        $data = API::request()->getDecodedJsonBody();

        Validation::validateVCVerifier($data);

        $vcVerifier = new VCVerifier($data);
        $vcVerifier->id = Utils::generateUniqueNgsiLdUrn(VCVerifier::TYPE);
        $this->vcVerifierManager->create($vcVerifier);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_CREATED);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($vcVerifier, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function show(string $id): void
    {
        $vcVerifier = $this->vcVerifierManager->readOne($id);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($vcVerifier, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function update(string $id): void
    {
        $vcVerifier = $this->vcVerifierManager->readOne($id);

        $data = API::request()->getDecodedJsonBody();

        Validation::validateVCVerifier($data);

        $vcVerifier->update($data);

        $this->vcVerifierManager->update($vcVerifier);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::Json->value);
        API::response()->setJsonBody($vcVerifier, JSON_UNESCAPED_SLASHES);
        API::response()->send();
    }

    public function destroy(string $id): void
    {
        $vcVerifier = $this->vcVerifierManager->readOne($id);

        $this->vcVerifierManager->delete($vcVerifier);

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }
}
