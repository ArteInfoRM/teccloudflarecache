<?php
/**
 * Copyright 2026 Tecnoacquisti.com
 *
 * @author    Tecnoacquisti.com <helpdesk@tecnoacquisti.com>
 * @copyright 2026 Tecnoacquisti.com
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * Performs narrowly scoped calls to the Cloudflare v4 API.
 */
class TecCloudflareCacheClient
{
    /**
     * Cloudflare API endpoint.
     *
     * @var string
     */
    private $apiBase = 'https://api.cloudflare.com/client/v4';

    /**
     * API token supplied by the merchant.
     *
     * @var string
     */
    private $token;

    /**
     * Selected Cloudflare zone ID.
     *
     * @var string
     */
    private $zoneId;

    /**
     * @param string $token Scoped Cloudflare API token
     * @param string $zoneId Cloudflare zone ID
     */
    public function __construct($token, $zoneId)
    {
        $this->token = $token;
        $this->zoneId = $zoneId;
    }

    /**
     * Verifies token access to the configured zone.
     *
     * @return array{success: bool, message: string}
     */
    public function verifyConnection()
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'API token or Zone ID is missing.'];
        }

        $response = $this->request('GET', '/zones/' . rawurlencode($this->zoneId));

        return $response['success']
            ? ['success' => true, 'message' => 'Zone access verified.']
            : ['success' => false, 'message' => $response['message']];
    }

    /**
     * Requests a complete cache purge for the configured zone.
     *
     * @return array{success: bool, message: string}
     */
    public function purgeEverything()
    {
        $response = $this->request('POST', '/zones/' . rawurlencode($this->zoneId) . '/purge_cache', ['purge_everything' => true]);

        return $response['success']
            ? ['success' => true, 'message' => 'Cloudflare accepted the purge request.']
            : ['success' => false, 'message' => $response['message']];
    }

    /**
     * Gets the zone Cache Rules entry point and its existing rules.
     *
     * @return array{success: bool, message: string, rules?: array<int, array<string, mixed>>, ruleset?: array<string, mixed>}
     */
    public function getCacheRuleset()
    {
        $response = $this->request('GET', '/zones/' . rawurlencode($this->zoneId) . '/rulesets/phases/http_request_cache_settings/entrypoint');
        if (!$response['success']) {
            if ($response['status'] === 404) {
                return ['success' => true, 'message' => 'No cache ruleset exists yet.', 'rules' => [], 'ruleset' => []];
            }

            return ['success' => false, 'message' => $response['message']];
        }

        $rules = isset($response['result']['rules']) && is_array($response['result']['rules']) ? $response['result']['rules'] : [];

        return ['success' => true, 'message' => 'Cache ruleset loaded.', 'rules' => $rules, 'ruleset' => $response['result']];
    }

    /**
     * Replaces the zone Cache Rules entry point with preserved and module rules.
     *
     * @param array<int, array<string, mixed>> $rules Complete ruleset rules
     * @param array<string, mixed> $ruleset Existing ruleset metadata, if available
     *
     * @return array{success: bool, message: string}
     */
    public function replaceCacheRules($rules, $ruleset)
    {
        $path = '/zones/' . rawurlencode($this->zoneId) . '/rulesets';
        $payload = [
            'name' => 'Tec Cloudflare Cache rules',
            'description' => 'Rules managed by Tec Cloudflare Cache.',
            'kind' => 'zone',
            'phase' => 'http_request_cache_settings',
            'rules' => $this->normalizeRulePayloads($rules),
        ];
        $method = 'POST';
        if (isset($ruleset['id']) && preg_match('/^[a-f0-9]{32}$/', (string) $ruleset['id'])) {
            $method = 'PUT';
            $path .= '/' . rawurlencode((string) $ruleset['id']);
            $payload['name'] = isset($ruleset['name']) ? (string) $ruleset['name'] : $payload['name'];
            $payload['description'] = isset($ruleset['description']) ? (string) $ruleset['description'] : $payload['description'];
        }
        $response = $this->request($method, $path, $payload);

        return $response['success']
            ? ['success' => true, 'message' => 'Bypass rules were applied.']
            : ['success' => false, 'message' => $response['message']];
    }

    /**
     * Removes read-only properties from existing rules before a ruleset update.
     *
     * @param array<int, array<string, mixed>> $rules Existing and new rules
     *
     * @return array<int, array<string, mixed>> API-safe rule payloads
     */
    private function normalizeRulePayloads($rules)
    {
        $normalized = [];
        $allowed = ['id', 'ref', 'description', 'expression', 'action', 'action_parameters', 'enabled', 'logging'];
        foreach ($rules as $rule) {
            $item = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, $rule)) {
                    $item[$field] = $rule[$field];
                }
            }
            $normalized[] = $item;
        }

        return $normalized;
    }

    /**
     * Executes a JSON Cloudflare API request and returns sanitized diagnostics.
     *
     * @param string $method HTTP method
     * @param string $path API-relative path
     * @param array<string, mixed>|null $payload Request JSON body
     *
     * @return array{success: bool, message: string, status: int, result?: array<string, mixed>}
     */
    private function request($method, $path, $payload = null)
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'API token or Zone ID is missing.', 'status' => 0];
        }
        if (!function_exists('curl_init')) {
            return ['success' => false, 'message' => 'The PHP cURL extension is required.', 'status' => 0];
        }

        $json = $payload === null ? null : json_encode($payload);
        if ($payload !== null && $json === false) {
            return ['success' => false, 'message' => 'Unable to encode the API request.', 'status' => 0];
        }

        $handle = curl_init($this->apiBase . $path);
        if ($handle === false) {
            return ['success' => false, 'message' => 'Unable to initialize the HTTP client.', 'status' => 0];
        }

        $headers = ['Authorization: Bearer ' . $this->token, 'Accept: application/json'];
        if ($json !== null) {
            $headers[] = 'Content-Type: application/json';
        }
        $options = [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if ($json !== null) {
            $options[CURLOPT_POSTFIELDS] = $json;
        }
        curl_setopt_array($handle, $options);

        $body = curl_exec($handle);
        $error = curl_error($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_HTTP_CODE);
        curl_close($handle);

        if ($body === false) {
            return ['success' => false, 'message' => 'Cloudflare request failed: ' . $this->sanitizeError($error), 'status' => $status];
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return ['success' => false, 'message' => 'Cloudflare returned an invalid response.', 'status' => $status];
        }
        if ($status < 200 || $status >= 300 || empty($decoded['success'])) {
            return ['success' => false, 'message' => $this->extractErrorMessage($decoded, $status), 'status' => $status];
        }

        return [
            'success' => true,
            'message' => 'OK',
            'status' => $status,
            'result' => isset($decoded['result']) && is_array($decoded['result']) ? $decoded['result'] : [],
        ];
    }

    /**
     * Checks whether client credentials have the expected local format.
     *
     * @return bool Whether both credentials are present
     */
    private function isConfigured()
    {
        return $this->token !== '' && preg_match('/^[a-f0-9]{32}$/', $this->zoneId) === 1;
    }

    /**
     * Extracts a short Cloudflare error without retaining raw response data.
     *
     * @param array<string, mixed> $decoded API response
     * @param int $status HTTP status
     *
     * @return string Safe error message
     */
    private function extractErrorMessage($decoded, $status)
    {
        if (isset($decoded['errors'][0]['code'])) {
            return 'Cloudflare API error ' . (int) $decoded['errors'][0]['code'] . ' (HTTP ' . $status . ').';
        }

        return 'Cloudflare API request failed (HTTP ' . $status . ').';
    }

    /**
     * Normalizes transport errors before exposing them to an administrator.
     *
     * @param string $error Transport error string
     *
     * @return string Redacted transport error
     */
    private function sanitizeError($error)
    {
        return preg_replace('/[^A-Za-z0-9 .,:;()_-]/', '', substr($error, 0, 180));
    }
}
