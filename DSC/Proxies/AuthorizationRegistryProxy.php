<?php

namespace API\Modules\DSC\Proxies;

use API\Enums\MimeType;
use API\Modules\DSC\Models\AuthorizationRegistry;
use API\Modules\DSC\Models\AuthorizationRegistryGrant;
use API\Modules\DSC\Models\DelegationEvidence;
use API\Modules\DSC\Models\DelegationRequest;
use API\StaticClasses\Utils;
use Core\Helpers\RequestHelper;
use Core\HttpRequestMethods;
use Core\HttpResponseStatusCodes;
use Exception;
use FaubourgNumerique\IShareToolsForI4Trust\IShareToolsForI4Trust;

class AuthorizationRegistryProxy
{
    private AuthorizationRegistry $authorizationRegistry;
    private AuthorizationRegistryGrant $authorizationRegistryGrant;

    public function __construct(AuthorizationRegistry $authorizationRegistry, AuthorizationRegistryGrant $authorizationRegistryGrant)
    {
        $this->authorizationRegistry = $authorizationRegistry;
        $this->authorizationRegistryGrant = $authorizationRegistryGrant;
    }

    public function getAccessToken(): string
    {
        $config = [
            "issuer" => $this->authorizationRegistryGrant->identifier,
            "subject" => $this->authorizationRegistryGrant->identifier,
            "audience" => $this->authorizationRegistry->identifier,
            "x5c" => $this->authorizationRegistryGrant->certificates,
            "privateKey" => Utils::formatPrivateKey($this->authorizationRegistryGrant->privateKey)
        ];

        $iShareJWT = IShareToolsForI4Trust::generateIShareJWT($config);

        $config = [
            "arTokenURL" => $this->authorizationRegistry->getOauth2TokenUrl(),
            "clientId" => $this->authorizationRegistryGrant->identifier,
            "iShareJWT" => $iShareJWT
        ];

        $accessToken = IShareToolsForI4Trust::getAccessToken($config);

        return $accessToken;
    }

    public function createPolicy(DelegationEvidence $delegationEvidence): void
    {
        $accessToken =  $this->getAccessToken();

        $config = [
            "arPolicyURL" => $this->authorizationRegistry->getPolicyUrl(),
            "delegationEvidence" => (array) $delegationEvidence,
            "accessToken" => $accessToken
        ];

        IShareToolsForI4Trust::createPolicy($config);
    }

    public function createPolicyOdrl($policy)
    {
        try {
            $request = new RequestHelper();
            $request->setMethod(HttpRequestMethods::DELETE);
            $request->setUrl("{$this->authorizationRegistry->getPolicyUrl()}/{$policy["@id"]}");
            $request->setTimeout($_ENV["REQUESTS_TIMEOUT"]);
            $request->send();
            $request = null;
        } catch (\Exception $exception) {
        }

        $request = new RequestHelper();
        $request->setMethod(HttpRequestMethods::PUT);
        $request->setUrl("{$this->authorizationRegistry->getPolicyUrl()}/{$policy["@id"]}");
        $request->setHeader("Content-Type", MimeType::Json->value);
        $request->setJsonBody($policy);
        $request->setTimeout($_ENV["REQUESTS_TIMEOUT"]);
        $response = $request->send();

        if ($response->getError()) {
            throw new \Exception(json_encode($response));
        }

        return $response->getBody();
    }

    public function requestDelegation(DelegationRequest $delegationRequest): DelegationEvidence
    {
        $accessToken =  $this->getAccessToken();

        $config = [
            "arDelegationURL" => $this->authorizationRegistry->getDelegationUrl(),
            "delegationRequest" => (array) $delegationRequest,
            "accessToken" => $accessToken
        ];

        $delegationToken = IShareToolsForI4Trust::getDelegationToken($config);

        //$decodedDelegationToken = IShareToolsForI4Trust::decodeJWT($delegationToken, Utils::formatCertificate($this->authorizationRegistryGrant->certificates[0]));

        // Unsafe decoding
        $payload = json_decode(base64_decode(explode(".", $delegationToken)[1]), true);

        return new DelegationEvidence($payload);
    }
}
