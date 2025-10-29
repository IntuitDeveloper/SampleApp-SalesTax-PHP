<?php
declare(strict_types=1);

namespace IndirectTax;

class GraphQLClient
{
    public static function buildRequest(string $query, $variables, string $accessToken, array $options = array()): array
    {
        if (empty($variables)) {
            $variables = new \stdClass();
        }

        $endpoint = isset($options['endpoint']) ? $options['endpoint'] : 'https://qb.api.intuit.com/graphql';
        $userAgent = isset($options['user_agent']) ? $options['user_agent'] : 'PHPSampleProjectsAPI/1.0';
        $host = isset($options['host']) ? $options['host'] : 'qb.api.intuit.com';
        $includeRealm = !empty($options['include_realm_header']);
        $realmId = isset($options['realm_id']) ? $options['realm_id'] : null;

        $requestBody = array(
            'query' => $query,
            'variables' => $variables
        );
        $requestBodyJson = json_encode($requestBody);

        $headers = array(
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
            'Accept: application/json;charset=UTF-8',
            'User-Agent: ' . $userAgent,
            'Host: ' . $host,
        );

        if ($includeRealm && !empty($realmId)) {
            $headers[] = 'intuit-realm-id: ' . $realmId;
        }

        $maskedToken = substr($accessToken, 0, 20) . '...';
        $requestHeadersUi = array(
            'Authorization' => 'Bearer ' . $maskedToken,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json;charset=UTF-8',
            'User-Agent' => $userAgent,
            'Host' => $host,
        );
        if ($includeRealm && !empty($realmId)) {
            $requestHeadersUi['intuit-realm-id'] = $realmId;
        }

        return array(
            'request_body_json' => $requestBodyJson,
            'request_headers' => $headers,
            'request_headers_ui' => $requestHeadersUi,
            'endpoint' => $endpoint,
        );
    }

    public static function execute(string $query, $variables, string $accessToken, array $options = array()): array
    {
        $built = self::buildRequest($query, $variables, $accessToken, $options);

        $endpoint = $built['endpoint'];
        $data = $built['request_body_json'];
        $headers = $built['request_headers'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $endpoint);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_HEADER, true);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        $httpCode = (int)$curlInfo['http_code'];
        $headerSize = isset($curlInfo['header_size']) ? (int)$curlInfo['header_size'] : 0;
        curl_close($ch);

        $responseStr = is_string($response) ? $response : '';
        $respHeaders = substr($responseStr, 0, $headerSize);
        $respBody = substr($responseStr, $headerSize);

        $logFile = __DIR__ . '/../logs/graphql_requests.log';
        @mkdir(dirname($logFile), 0777, true);
        $logData = array(
            'timestamp' => date('Y-m-d H:i:s'),
            'url' => $endpoint,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'request_data' => $data,
            'response_headers' => $respHeaders,
            'response_body' => $respBody,
            'curl_info' => $curlInfo,
        );
        @file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);

        if ($curlError) {
            throw new \RuntimeException('cURL error: ' . $curlError . ' | HTTP Code: ' . $httpCode);
        }
        if ($httpCode < 200 || $httpCode >= 300) {
            throw new HttpException('GraphQL request failed | HTTP Code: ' . $httpCode, $httpCode, $respBody, $respHeaders);
        }

        $decoded = json_decode($respBody, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid JSON response from GraphQL endpoint.');
        }

        return array(
            'data' => $decoded,
            'http_code' => $httpCode,
            'response_raw' => $respBody,
            'response_headers' => $respHeaders,
            'curl_info' => $curlInfo,
            'endpoint' => $endpoint,
            'request_body_json' => $built['request_body_json'],
            'request_headers' => $built['request_headers'],
            'request_headers_ui' => $built['request_headers_ui'],
        );
    }
}


