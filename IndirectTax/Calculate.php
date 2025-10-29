<?php
declare(strict_types=1);

use IndirectTax\TaxGraphQLService;
use IndirectTax\HttpException;

// Simple autoload if composer is present
@include_once __DIR__ . '/../vendor/autoload.php';

// Fallback: manual include for service class
if (!class_exists(TaxGraphQLService::class)) {
    require_once __DIR__ . '/TaxGraphQLService.php';
}
if (!class_exists(HttpException::class)) {
    require_once __DIR__ . '/HttpException.php';
}

session_start();

$errors = array();
$result = null;
$taxAmount = null;
$requestMeta = null; // endpoint, headers (masked), body
$httpErrorBody = null;
$httpResponseHeaders = null;
$httpStatusCode = null;

// Attempt to retrieve access token and realm from session (caller should have set these)
$accessToken = isset($_SESSION['access_token']) ? $_SESSION['access_token'] : null;
$realmId = isset($_SESSION['realm_id']) ? $_SESSION['realm_id'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!$accessToken) {
            throw new RuntimeException('Missing access token in session. Please authenticate first.');
        }

        $transactionDate = isset($_POST['transactionDate']) ? trim($_POST['transactionDate']) : '';
        $customerId = isset($_POST['customerId']) ? trim($_POST['customerId']) : '';
        if ($customerId === '') {
            throw new RuntimeException('Customer Id is required. Please enter a valid Customer Id.');
        }
        $shipFromZip = isset($_POST['shipFromZip']) ? trim($_POST['shipFromZip']) : '';
        $shipToZip = isset($_POST['shipToZip']) ? trim($_POST['shipToZip']) : '';
        $numberOfUnits = (int)($_POST['numberOfUnits'] ?? 0);
        $unitValue = (float)($_POST['unitValue'] ?? 0);
        $productVariantId = isset($_POST['productVariantId']) ? trim($_POST['productVariantId']) : '7';

        $service = new TaxGraphQLService();
        $variables = $service->prepareVariables($transactionDate, $customerId, $shipFromZip, $shipToZip, $numberOfUnits, $unitValue, $productVariantId);

        // Pass Authorization header as-is
        $response = $service->calculateTax('Bearer ' . $accessToken, $variables);
        $result = $response;
        $taxAmount = $service->extractTaxAmount($response);
        $requestMeta = $service->getLastRequestMeta();
    } catch (Throwable $t) {
        if ($t instanceof HttpException) {
            $httpStatusCode = $t->getStatusCode();
            $httpErrorBody = $t->getResponseBody();
            $httpResponseHeaders = $t->getResponseHeaders();
            $errors[] = $t->getMessage();
        } else {
            $errors[] = $t->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Indirect Tax - GraphQL Calculation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .row { margin-bottom: 10px; }
        label { display: inline-block; width: 220px; }
        input[type=text], input[type=number] { width: 260px; padding: 6px; }
        .btn { background: #0077C5; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
        .info { background: #f0f8ff; padding: 10px; border: 1px solid #ccc; margin: 10px 0; border-radius: 4px; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .error { color: #b30000; }
        .success { background: #e6ffed; border-color: #28a745; color: #155724; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Indirect Tax - Calculate via GraphQL</h1>

        <div class="info">
            <div><strong>Realm ID:</strong> <?= htmlspecialchars((string)$realmId) ?></div>
            <div><strong>Access Token Present:</strong> <?= $accessToken ? 'Yes' : 'No' ?></div>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="info" style="background:#fff3f3;border-color:#ffb3b3;">
                <strong>Errors:</strong>
                <ul>
                    <?php foreach ($errors as $e): ?>
                        <li class="error"><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php if (is_array($requestMeta)): ?>
                    <div class="row"><strong>Request URL:</strong> <?= htmlspecialchars((string)($requestMeta['endpoint'] ?? '')) ?></div>
                    <?php if (!empty($requestMeta['request_headers_ui'])): ?>
                        <div class="row"><strong>Request Headers:</strong></div>
                        <pre><?= htmlspecialchars(json_encode($requestMeta['request_headers_ui'], JSON_PRETTY_PRINT)) ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($requestMeta['request_body_json'])): ?>
                        <div class="row"><strong>Request Body:</strong></div>
                        <pre><?= htmlspecialchars($requestMeta['request_body_json']) ?></pre>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($httpErrorBody !== null): ?>
                    <div class="row"><strong>HTTP Status:</strong> <?= htmlspecialchars((string)$httpStatusCode) ?></div>
                    <?php if ($httpResponseHeaders !== null): ?>
                        <div class="row"><strong>Response Headers:</strong></div>
                        <pre><?= htmlspecialchars($httpResponseHeaders) ?></pre>
                    <?php endif; ?>
                    <div class="row"><strong>Response Body:</strong></div>
                    <pre><?= htmlspecialchars($httpErrorBody) ?></pre>
                    <script>
                    (function(){
                        var status = <?= json_encode($httpStatusCode) ?>;
                        var body = <?= json_encode($httpErrorBody) ?>;
                        var headers = <?= json_encode($httpResponseHeaders) ?>;
                        var req = <?= json_encode($requestMeta) ?>;
                        if (typeof console !== 'undefined') {
                            console.group('GraphQL HTTP error');
                            if (req) {
                                console.log('Request URL:', req.endpoint);
                                console.log('Request Headers (masked):', req.request_headers_ui);
                                console.log('Request Body:', req.request_body_json);
                            }
                            console.log('Status:', status);
                            console.log('Headers:', headers);
                            console.log('Body:', body);
                            console.groupEnd();
                        }
                    })();
                    </script>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="post">
            <div class="row">
                <label for="transactionDate">Transaction Date (yyyy-MM-dd)</label>
                <input id="transactionDate" name="transactionDate" type="text" value="<?= htmlspecialchars($_POST['transactionDate'] ?? date('Y-m-d')) ?>" />
            </div>
            <div class="row">
                <label for="customerId">Customer Id</label>
                <input id="customerId" name="customerId" type="text" value="<?= htmlspecialchars($_POST['customerId'] ?? '') ?>" />
            </div>
            <div class="row">
                <label for="shipFromZip">Ship From ZIP</label>
                <input id="shipFromZip" name="shipFromZip" type="text" value="<?= htmlspecialchars($_POST['shipFromZip'] ?? '94043') ?>" />
            </div>
            <div class="row">
                <label for="shipToZip">Ship To ZIP</label>
                <input id="shipToZip" name="shipToZip" type="text" value="<?= htmlspecialchars($_POST['shipToZip'] ?? '94043') ?>" />
            </div>
            <div class="row">
                <label for="numberOfUnits">Number of Units</label>
                <input id="numberOfUnits" name="numberOfUnits" type="number" value="<?= htmlspecialchars($_POST['numberOfUnits'] ?? '1') ?>" />
            </div>
            <div class="row">
                <label for="unitValue">Unit Value</label>
                <input id="unitValue" name="unitValue" type="number" step="0.01" value="<?= htmlspecialchars($_POST['unitValue'] ?? '10.00') ?>" />
            </div>
            <div class="row">
                <label for="productVariantId">Product Variant Id</label>
                <input id="productVariantId" name="productVariantId" type="text" value="<?= htmlspecialchars($_POST['productVariantId'] ?? '7') ?>" />
            </div>
            <div class="row">
                <button class="btn" type="submit">Calculate Tax</button>
                <a class="btn" href="../OAuth_2/index.php" style="background:#6c757d">Back</a>
            </div>
        </form>

        <?php if ($result !== null): ?>
            <h3>Result</h3>
            <?php if ($taxAmount !== null): ?>
                <div class="info success"><strong>Sales Tax (Excl. Shipping):</strong> <?= htmlspecialchars((string)$taxAmount) ?></div>
            <?php endif; ?>
            <?php if (is_array($requestMeta)): ?>
                <div class="info">
                    <div class="row"><strong>Request URL:</strong> <?= htmlspecialchars((string)($requestMeta['endpoint'] ?? '')) ?></div>
                    <?php if (!empty($requestMeta['request_headers_ui'])): ?>
                        <div class="row"><strong>Request Headers:</strong></div>
                        <pre><?= htmlspecialchars(json_encode($requestMeta['request_headers_ui'], JSON_PRETTY_PRINT)) ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($requestMeta['request_body_json'])): ?>
                        <div class="row"><strong>Request Body:</strong></div>
                        <pre><?= htmlspecialchars($requestMeta['request_body_json']) ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($requestMeta['response_headers'])): ?>
                        <div class="row"><strong>Response Headers:</strong></div>
                        <pre><?= htmlspecialchars($requestMeta['response_headers']) ?></pre>
                    <?php endif; ?>
                    <?php if (!empty($requestMeta['response_raw'])): ?>
                        <div class="row"><strong>Response Body:</strong></div>
                        <pre><?= htmlspecialchars($requestMeta['response_raw']) ?></pre>
                    <?php endif; ?>
                </div>
                <script>
                (function(){
                    var req = <?= json_encode($requestMeta) ?>;
                    if (typeof console !== 'undefined' && req) {
                        console.group('GraphQL Request');
                        console.log('Request URL:', req.endpoint);
                        console.log('Request Headers (masked):', req.request_headers_ui);
                        console.log('Request Body:', req.request_body_json);
                        console.groupEnd();
                    }
                })();
                </script>
            <?php endif; ?>
            <pre><?= htmlspecialchars(json_encode($result, JSON_PRETTY_PRINT)) ?></pre>
        <?php endif; ?>
    </div>
</body>
</html>


