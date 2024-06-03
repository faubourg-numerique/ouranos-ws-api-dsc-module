<?php

namespace API\Modules\DSC\Controllers;

use API\StaticClasses\Utils;
use API\Enums\MimeType;
use API\Managers\ServiceManager;
use API\Modules\DSC\Managers\VCVerifierManager;
use API\Managers\WorkspaceManager;
use Core\API;
use Core\Controller;
use Core\Helpers\RequestHelper;
use Core\HttpRequestMethods;
use Core\HttpResponseStatusCodes;

class SIOP2Controller extends Controller
{
    private VCVerifierManager $vcVerifierManager;
    private ServiceManager $serviceManager;
    private WorkspaceManager $workspaceManager;

    public function __construct()
    {
        global $systemEntityManager;
        $this->vcVerifierManager = new VCVerifierManager($systemEntityManager);
        $this->serviceManager = new ServiceManager($systemEntityManager);
        $this->workspaceManager = new WorkspaceManager($systemEntityManager);
    }

    public function callback(): void
    {
        $state = API::request()->getUrlQueryParameter("state");
        $code = API::request()->getUrlQueryParameter("code");

        if (!$state || !$code) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        $tempSIOP2CodesFilePath = implode(DIRECTORY_SEPARATOR, [ROOT_DIRECTORY_PATH, "temp", "siop2-codes.json"]);

        if (!is_file($tempSIOP2CodesFilePath)) {
            file_put_contents($tempSIOP2CodesFilePath, json_encode([]));
        }

        $tempSIOP2Codes = json_decode(file_get_contents($tempSIOP2CodesFilePath), true);

        $tempSIOP2Codes[$state] = $code;

        file_put_contents($tempSIOP2CodesFilePath, json_encode($tempSIOP2Codes));

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_NO_CONTENT);
        API::response()->send();
    }

    public function poll(): void
    {
        $state = API::request()->getUrlQueryParameter("state");
        $workspaceId = API::request()->getUrlQueryParameter("workspaceId");

        if (!$state || !$workspaceId) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        $workspace = $this->workspaceManager->readOne($workspaceId);
        $service = $this->serviceManager->readOne($workspace->hasService);

        if (!$service->authorizationRequired || $service->authorizationMode != "siop2") {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        $vcVerifier = $this->vcVerifierManager->readOne($service->hasVCVerifier);

        $tempSIOP2CodesFilePath = implode(DIRECTORY_SEPARATOR, [ROOT_DIRECTORY_PATH, "temp", "siop2-codes.json"]);

        if (!is_file($tempSIOP2CodesFilePath)) {
            file_put_contents($tempSIOP2CodesFilePath, json_encode([]));
        }

        $tempSIOP2Codes = json_decode(file_get_contents($tempSIOP2CodesFilePath), true);

        if (!array_key_exists($state, $tempSIOP2Codes)) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_SERVICE_UNAVAILABLE);
            API::response()->send();
        }

        $data = [
            "code" => $tempSIOP2Codes[$state],
            "grant_type" => "authorization_code",
            "redirect_uri" => "{$_ENV["API_URL"]}/siop2/callback"
        ];

        $request = new RequestHelper();
        $request->setMethod(HttpRequestMethods::POST);
        $request->setUrl("{$vcVerifier->getUrl()}/token");
        $request->setHeader("Accept", MimeType::Json->value);
        $request->setHeader("Content-Type", MimeType::XWWWFormUrlEncoded->value);
        $request->setBody(http_build_query($data));
        $request->setTimeout($_ENV["REQUESTS_TIMEOUT"]);
        $response = $request->send();

        $data = $response->getDecodedJsonBody();

        if (!array_key_exists("access_token", $data)) {
            API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_BAD_REQUEST);
            API::response()->send();
        }

        API::response()->setStatusCode(HttpResponseStatusCodes::HTTP_OK);
        API::response()->setHeader("Content-Type", MimeType::TextPlain->value);
        API::response()->setBody($data["access_token"]);
        API::response()->send();
    }
}
