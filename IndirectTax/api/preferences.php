<?php
declare(strict_types=1);

// Basic REST v3 Preferences proxy using session tokens
session_start();

header('Content-Type: application/json');

$accessToken = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
$realmId = isset($_SESSION['realm_id']) ? $_SESSION['realm_id'] : null;

if (!$accessToken || !$realmId) {
    http_response_code(400);
    echo json_encode(array('error' => 'Missing access token or realm_id in session'));
    exit;
}

$baseUrl = getenv('QB_ENVIRONMENT') && strtolower(getenv('QB_ENVIRONMENT')) === 'sandbox'
    ? 'https://sandbox-quickbooks.api.intuit.com'
    : 'https://quickbooks.api.intuit.com';

$url = $baseUrl . '/v3/company/' . rawurlencode((string)$realmId) . '/preferences?minorversion=65';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Authorization: Bearer ' . $accessToken,
    'Accept: application/json'
));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_TIMEOUT, 20);

$raw = curl_exec($ch);
$curlErr = curl_error($ch);
$info = curl_getinfo($ch);
$status = (int)($info['http_code'] ?? 0);
$headerSize = (int)($info['header_size'] ?? 0);
curl_close($ch);

$respHeaders = is_string($raw) ? substr($raw, 0, $headerSize) : '';
$respBody = is_string($raw) ? substr($raw, $headerSize) : '';

if ($curlErr) {
    http_response_code(502);
    echo json_encode(array(
        'error' => 'cURL error',
        'message' => $curlErr,
        'status' => $status
    ));
    exit;
}

http_response_code($status);
echo $respBody !== '' ? $respBody : json_encode(array('status' => $status, 'headers' => $respHeaders));



