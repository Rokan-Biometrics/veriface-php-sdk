<?php
/**
 * VeriFace PHP API client
 *
 * @version     1.0.0
 * @copyright   2023, Rokan Biometrics, s.r.o.
 * @license     MIT
 * @link        https://veriface.eu
 */

namespace veriface;

use Exception;
use RuntimeException;
use veriface\Dto\CreateVerificationResponseDTO;
use veriface\Dto\ExtendedReferenceDto;
use veriface\Dto\MonitoringDto;
use veriface\Dto\MonitoringWebhookDto;

class VeriFace
{
    private const VERSION = "1.0.20230402";
    private const VERIFICATION_API_PREFIX = "/public-api/v1/verification/";
    const API_URL = "https://api.veriface.eu";
    private bool $dieOnCurlError = false;
    private string $apiKey;
    private string $apiUrl;


    public function __construct()
    {
        //Use static constructors
    }


    /**
     * @param string $apiKey
     * @param string|null $apiUrl
     * @return VeriFace
     */
    public static function byApiKey(
        string  $apiKey,
        ?string $apiUrl = self::API_URL,
        bool    $dieOnCurlError = false
    ): VeriFace
    {
        $v = new VeriFace();
        $v->apiKey = $apiKey;
        $v->apiUrl = !empty($apiUrl) ? $apiUrl : self::API_URL;
        $v->dieOnCurlError = $dieOnCurlError;
        return $v;
    }


    public function getSettings(): array
    {
        return [
            "apiKey" => $this->apiKey,
            "apiUrl" => $this->apiUrl,
        ];
    }

    public function setSettings($settings)
    {
        $this->apiKey = $settings['apiKey'];
        $this->apiUrl = !empty($settings['apiUrl']) ? $settings['apiUrl'] : self::API_URL;
    }


    private function setCurlHeaders($ch)
    {
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'VeriFace PHP Client ' . self::VERSION);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->apiKey,
        ]);
    }


    private function apiGet(string $url): string
    {
        $ch = curl_init($this->apiUrl . $url);
        $this->setCurlHeaders($ch);
        return $this->executeAndProcessCurlResponse($ch);
    }

    private function apiDelete(string $url): string
    {
        $ch = curl_init($this->apiUrl . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        $this->setCurlHeaders($ch);
        return $this->executeAndProcessCurlResponse($ch);
    }

    private function apiPost(string $url, $vars, $isArray = false): string
    {
        $ch = curl_init($this->apiUrl . $url);

        curl_setopt(
            $ch,
            CURLOPT_POSTFIELDS,
            json_encode($vars, !$isArray && count($vars) == 0 ? JSON_FORCE_OBJECT : 0));

        curl_setopt($ch, CURLOPT_POST, 1);
        $this->setCurlHeaders($ch);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'apikey: ' . $this->apiKey,
            'Content-Type: application/json'
        ]);
        return $this->executeAndProcessCurlResponse($ch);
    }

    public function getDefaultSettings(): array
    {
        return json_decode($this->apiPost("/public-api/v1/get-settings", []), true);
    }

    /**
     * Create verification
     *
     * @param $type string must be one of LINK_LONG LINK_SHORT INVITE_EMAIL
     * @param string|null $companyId
     * @param string|null $templateId
     * @param string|null $personId
     * @param string|null $referenceId
     * @param string|null $email
     * @param string|null $redirectUri
     * @param ExtendedReferenceDto[]|array|null $extendedReferences
     * @return CreateVerificationResponseDTO
     */
    public function createVerification(
        string  $type,
        ?string $companyId = null,
        ?string $templateId = null,
        ?string $personId = null,
        ?string $referenceId = null,
        ?string $email = null,
        ?string $redirectUri = null,
        ?array  $extendedReferences = null
    ): Dto\CreateVerificationResponseDto
    {
        $params = [
            "type" => $type,
            "companyId" => $companyId ?? null,
            "personId" => $personId ?? null,
            "templateId" => $templateId ?? null,
            "referenceId" => $referenceId,
            "email" => $email,
            "redirectUri" => $redirectUri,
            "extendedReferences" => $extendedReferences
        ];
        $params = $this->modifyCreateVerificationParams($params);
        $response = json_decode($this->apiPost("/public-api/v1/verification/create", $params), true);
        $out = new Dto\CreateVerificationResponseDto();
        $out->openCode = $response['openCode'];
        $out->sessionId = $response['sessionId'];
        $out->referenceId = $response['referenceId'];
        $out->extendedReferences = self::mapExtendedReferences($response['extendedReferences'] ?? []);
        return $out;
    }

    /**
     * Adds extended reference to existing verification
     * @param string $sessionId
     * @param string $type
     * @param string $value
     * @return string
     */
    public function addExtendedReference(string $sessionId, string $type, string $value): string
    {
        $params = new ExtendedReferenceDto($type, $value);
        return $this->apiPost("/public-api/v1/verification/" . $sessionId . "/extended-reference", $params);
    }


    /**
     * @param $referenceId
     * @return \veriface\Dto\VerificationListDTO[]
     */
    public function findVerificationsByReferenceId($referenceId): array
    {
        return $this->findByParam(['referenceId' => $referenceId]);
    }

    /**
     * @param $type
     * @param $value
     * @return \veriface\Dto\VerificationListDTO[]
     */
    public function findVerificationsByExtendedReference($type, $value): array
    {
        return $this->findByParam(['extendedReferenceType' => $type, 'extendedReferenceValue' => $value]);
    }

    /**
     * @param $sessionId
     * @return \veriface\Dto\VerificationListDTO[]
     */
    public function findVerificationBySessionId($sessionId): array
    {
        return $this->findByParam(['sessionId' => $sessionId]);
    }

    private function findByParam($param): array
    {
        return array_map(function ($x) {
            return self::toVerificationListDTO($x);
        }, json_decode($this->apiPost("/public-api/v1/verification/find", $param), true));
    }

    public function getVerification($verificationId, $locale = 'sk_SK'): ?Dto\VerificationResultDto
    {
        $x = json_decode($this->apiGet(self::VERIFICATION_API_PREFIX . $verificationId . '?locale=' . $locale), true);
        try {
            if ($x) {
                $target = new Dto\VerificationResultDto();
                self::extractListData($x, $target);
                $target->userStarted = $x['userStarted'] ? new \DateTime($x['userStarted']) : null;
                $target->userFinished = $x['userFinished'] ? new \DateTime($x['userFinished']) : null;
                $target->name = $x['name'] ?? null;
                $target->birthDate = $x['birthDate'] ?? null;
                $target->documentNumber = $x['documentNumber'] ?? null;
                $target->personalNumber = $x['personalNumber'] ?? null;
                $target->documentCountry = $x['documentCountry'] ?? null;
                $target->documentType = $x['documentType'] ?? null;
                $target->summaryStatus = $x['summaryStatus'] ?? null;
                $target->documentStatus = $x['documentStatus'] ?? null;
                $target->selfieStatus = $x['selfieStatus'] ?? null;
                $target->livenessCheckStatus = $x['livenessCheckStatus'] ?? null;
                $target->amlStatus = $x['amlStatus'] ?? null;
                $target->monitoringStatus = $x['monitoringStatus'] ?? null;
                $target->stabilizedResult = $x['stabilizedResult'] ?? null;
                $target->waitingAction = $x['waitingAction'] ?? null;
                $target->waitingActionConfirmed = $x['waitingActionConfirmed'] ?? null;
                $target->waitingManualResult = $x['waitingManualResult'] ?? null;
                $target->incorrectResultReported = $x['incorrectResultReported'] ?? null;
                $target->extractedData = self::mapExtractedData($x['extractedData']);
                $target->indicators = self::mapIndicators($x['indicators']);
                return $target;
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw new VeriFaceApiException("Error parsing verification data", 0, $e);
        }
    }

    public function getVerificationMonitoringData($verificationId, $locale = 'sk_SK'): ?MonitoringDto
    {
        $x = json_decode(
            $this->apiGet(self::VERIFICATION_API_PREFIX . $verificationId . '/monitoring?locale=' . $locale),
            true
        );
        try {
            if ($x) {
                $target = new MonitoringDto();
                $target->status = $x['status'] ?? null;
                $target->sessionId = $x['status'] ?? null;
                $target->name = $x['status'] ?? null;
                $target->verificationDate = !empty($x['verificationDate']) ?
                    new \DateTime($x['verificationDate']) : null;
                $target->monitoringStart = !empty($x['monitoringStart']) ? new \DateTime($x['monitoringStart']) : null;
                $target->monitoringEnd = !empty($x['monitoringEnd']) ? new \DateTime($x['monitoringEnd']) : null;
                $target->monitoringLastChange = !empty($x['monitoringLastChange']) ?
                    new \DateTime($x['monitoringLastChange']) : null;
                $target->monitoringVariant = $x['monitoringVariant'] ?? null;
                $target->detail = self::mapMonitoringDetails($x['detail']);
                return $target;
            } else {
                return null;
            }
        } catch (Exception $e) {
            throw new RuntimeException("Error parsing monitoring data", 0, $e);
        }
    }

    public function getPdfReport($sessionId, $locale = 'sk_SK'): string
    {
        return $this->apiGet(self::VERIFICATION_API_PREFIX . $sessionId . '/pdf?locale=' . $locale);
    }

    public function getDocumentImages($sessionId, $all = false): string
    {
        return $this->apiGet(self::VERIFICATION_API_PREFIX . $sessionId . '/images/document-all?all=' . $all);
    }


    public function delete($sessionId): string
    {
        return $this->apiDelete(self::VERIFICATION_API_PREFIX . $sessionId);
    }


    private static function toVerificationListDTO($x): Dto\VerificationListDto
    {
        $target = new Dto\VerificationListDto();
        self::extractListData($x, $target);
        return $target;
    }


    private static function mapExtendedReferences($extendedReferences): array
    {
        if (!empty($extendedReferences)) {
            return array_map(function ($ref) {
                return new ExtendedReferenceDto($ref['type'], $ref['value']);
            }, $extendedReferences);
        } else {
            return [];
        }
    }


    public function processVerificationWebhook(string $inputData): Dto\VerificationWebhookDto
    {
        $x = json_decode($inputData, true);
        $target = new Dto\VerificationWebhookDto();
        $target->sessionId = $x['sessionId'] ?? null;
        $target->referenceId = $x['referenceId'] ?? null;
        $target->extendedReferences = self::mapExtendedReferences($x['extendedReferences'] ?? []);
        $target->status = $x['status'] ?? null;
        $target->verificationEndUserStatus = $x['verificationEndUserStatus'] ?? null;
        return $target;
    }


    public function processMonitoringWebhook(string $inputData): MonitoringWebhookDTO
    {
        $x = json_decode($inputData, true);
        $target = new MonitoringWebhookDTO();
        $target->sessionId = $x['sessionId'] ?? null;
        $target->referenceId = $x['referenceId'] ?? null;
        $target->extendedReferences = self::mapExtendedReferences($x['extendedReferences'] ?? []);
        $target->status = $x['status'] ?? null;
        $target->service = $x['service'] ?? null;
        return $target;
    }


    /**
     * @return bool
     */
    public function isDieOnCurlError(): bool
    {
        return $this->dieOnCurlError;
    }

    /**
     * Toggle curl error behaviour, when true the script dies with text message, otherwise the RuntimeException
     * is thrown
     * @param bool $dieOnCurlError
     * @return VeriFace
     */
    public function setDieOnCurlError(bool $dieOnCurlError): VeriFace
    {
        $this->dieOnCurlError = $dieOnCurlError;
        return $this;
    }

    private function executeAndProcessCurlResponse($ch)
    {
        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($code != 200) {
            if ($this->dieOnCurlError) {
                http_response_code(400);
                die("Curl ${code} error when calling " . curl_getinfo($ch, CURLINFO_EFFECTIVE_URL) . ":" . $response);
            } else {
                $responseDecoded = json_decode($response, true);
                if (!json_last_error() && !empty($responseDecoded['code'])) {
                    throw new VeriFaceApiException($responseDecoded['code']);
                } else {
                    throw new VeriFaceApiException('UNKNOWN');
                }
            }
        }
        curl_close($ch);
        return $response;
    }

    private static function extractListData($x, Dto\VerificationListDto $target): void
    {
        try {
            $target->sessionId = $x['sessionId'] ?? null;
            $target->referenceId = $x['referenceId'] ?? null;
            $target->status = $x['status'] ?? null;
            $target->verificationEndUserStatus = $x['verificationEndUserStatus'];
            $target->created = !empty($x['created']) ? new \DateTime($x['created']) : null;
            if (!empty($x['finished'])) {
                $target->finished = new \DateTime($x['finished']);
            } elseif (!empty($x['userFinished'])) {
                $target->finished = new \DateTime($x['userFinished']);
            } else {
                $target->finished = null;
            }
            $target->deleted = !empty($x['deleted']) ? new \DateTime($x['deleted']) : null;
            $target->extendedReferences = self::mapExtendedReferences($x['extendedReferences'] ?? []);
        } catch (Exception $e) {
            throw new RuntimeException("Error extracting data list", 0, $e);
        }
    }


    private static function mapExtractedData($extendedReferences): array
    {
        if (!empty($extendedReferences)) {
            return array_map(function ($x) {
                $o = new Dto\ExtractedDataDto();
                $o->key = $x['key'] ?? null;
                $o->customKey = $x['customKey'] ?? null;
                $o->value = $x['value'] ?? null;
                $o->customValue = $x['customValue'] ?? null;
                $o->endUserValue = $x['endUserValue'] ?? null;
                return $o;
            }, $extendedReferences);
        } else {
            return [];
        }
    }

    private static function mapIndicators($indicators): array
    {
        if (!empty($indicators)) {
            return array_map(function ($x) {
                $o = new Dto\IndicatorDto();
                $o->code = $x['code'] ?? null;
                $o->localizedMessage = $x['localizedMessage'] ?? null;
                $o->params = $x['params'] ?? null;
                $o->status = $x['status'] ?? null;
                $o->section = $x['section'] ?? null;
                return $o;
            }, $indicators);
        }
        return [];

    }

    private static function mapMonitoringDetails($monitoringDetails): array
    {
        if (!empty($monitoringDetails)) {
            try {

                return array_map(function ($x) {
                    $o = new Dto\MonitoringDataDto();
                    $o->service = $x['service'] ?? null;
                    $o->status = $x['status'] ?? null;

                    $o->created = !empty($x['created']) ? new \DateTime($x['created']) : null;
                    $o->updated = !empty($x['updated']) ? new \DateTime($x['updated']) : null;

                    $o->validUntil = !empty($x['validUntil']) ? new \DateTime($x['validUntil']) : null;

                    $o->previousCode = $x['previousCode'] ?? null;
                    $o->previousLocalizedLabel = $x['previousLocalizedLabel'] ?? null;

                    $o->code = $x['code'] ?? null;
                    $o->localizedLabel = $x['localizedLabel'] ?? null;
                    return $o;
                }, $monitoringDetails);
            } catch (Exception $e) {
                throw new RuntimeException("Error extracting monitoring details", 0, $e);
            }
        } else {
            return [];
        }
    }

    /**
     * @return string
     */
    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    /**
     * @param string $apiKey
     * @return VeriFace
     */
    public function setApiKey(string $apiKey): VeriFace
    {
        $this->apiKey = $apiKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    /**
     * @param string $apiUrl
     * @return VeriFace
     */
    public function setApiUrl(string $apiUrl): VeriFace
    {
        $this->apiUrl = $apiUrl;
        return $this;
    }

    public function modifyCreateVerificationParams(array $params): array
    {
        return $params;
    }

}
