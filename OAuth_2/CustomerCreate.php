<?php
// Suppress PHP warnings from QuickBooks SDK for PHP 8.4+ compatibility
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

session_start();
require_once "../vendor/autoload.php";

use QuickBooksOnline\API\DataService\DataService;
use QuickBooksOnline\API\Core\Http\Serialization\XmlObjectSerializer;
use QuickBooksOnline\API\Facades\Customer;

// Get configuration
$configs = include('./config.php');

// Local customer storage removed

// Check if user is authenticated
if (!isset($_SESSION['access_token']) || empty($_SESSION['access_token'])) {
    echo "<h2>Error: Not Authenticated</h2>";
    echo "<p>You need to complete the OAuth2 flow first.</p>";
    echo "<a href='index.php'>Go back to main page</a>";
    exit();
}

// Get tokens from session
$access_token = $_SESSION['access_token'];
$refresh_token = $_SESSION['refresh_token'];
$realm_id = $_SESSION['realm_id'];

try {
    // Configure DataService with session tokens
    $dataService = DataService::Configure(array(
        'auth_mode' => 'oauth2',
        'ClientID' => $configs['client_id'],
        'ClientSecret' => $configs['client_secret'],
        'accessTokenKey' => $access_token,
        'refreshTokenKey' => $refresh_token,
        'QBORealmID' => $realm_id,
        'baseUrl' => $configs['base_url']
    ));
    
    $dataService->throwExceptionOnError(true);
    
    // Customer data
    $customerInfo = [
        "BillAddr" => [
            "Line1" => "123 Main Street",
            "City" => "Mountain View",
            "Country" => "USA",
            "CountrySubDivisionCode" => "CA",
            "PostalCode" => "94042"
        ],
        "Notes" => "Created via OAuth2 API - " . date('Y-m-d H:i:s'),
        "Title" => "Mr",
        "GivenName" => "John",
        "MiddleName" => "A",
        "FamilyName" => "Doe",
        "Suffix" => "Jr",
        "FullyQualifiedName" => "John Doe Company",
        "CompanyName" => "John Doe Company",
        "DisplayName" => "John Doe - " . time(), // Make unique
        "PrimaryPhone" => [
            "FreeFormNumber" => "(555) 123-4567"
        ],
        "PrimaryEmailAddr" => [
            "Address" => "john.doe@example.com"
        ]
    ];
    
    // Create customer
    $theResourceObj = Customer::create($customerInfo);
    $resultingObj = $dataService->Add($theResourceObj);
    $error = $dataService->getLastError();
    
    ?>
    
    <html>
    <head>
        <title>Customer Creation Result</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .success { color: green; background: #f0fff0; padding: 10px; border: 1px solid green; }
            .error { color: red; background: #fff0f0; padding: 10px; border: 1px solid red; }
            .info { background: #f0f8ff; padding: 10px; border: 1px solid #ccc; margin: 10px 0; }
            pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        </style>
    </head>
    <body>
        <h1>Customer Creation Result</h1>
        
        <div class="info">
            <h3>Authentication Info Used:</h3>
            <p><strong>Environment:</strong> <?= htmlspecialchars($environment) ?> (<?= htmlspecialchars($baseUrl) ?>)</p>
            <p><strong>Realm ID:</strong> <?= htmlspecialchars($realm_id) ?></p>
            <p><strong>Access Token (first 20 chars):</strong> <?= htmlspecialchars(substr($access_token, 0, 20)) ?>...</p>
        </div>
        
        <?php if ($error): ?>
            <div class="error">
                <h2>❌ Error Creating Customer</h2>
                <p><strong>HTTP Status:</strong> <?= $error->getHttpStatusCode() ?></p>
                <p><strong>Helper Message:</strong> <?= htmlspecialchars($error->getOAuthHelperError()) ?></p>
                <p><strong>Response:</strong></p>
                <pre><?= htmlspecialchars($error->getResponseBody()) ?></pre>
            </div>
        <?php else: ?>
            <div class="success">
                <h2>✅ Customer Created Successfully!</h2>
                <p><strong>Customer ID:</strong> <?= $resultingObj->Id ?></p>
                <p><strong>Name:</strong> <?= $resultingObj->Name ?></p>
                <p><strong>Display Name:</strong> <?= $resultingObj->DisplayName ?></p>
            </div>
            
            <div class="info">
                <h3>Full Customer Object:</h3>
                <pre><?= htmlspecialchars(json_encode($resultingObj, JSON_PRETTY_PRINT)) ?></pre>
            </div>
            
            
            <?php 
            $urlResource = null;
            $xmlBody = XmlObjectSerializer::getPostXmlFromArbitraryEntity($resultingObj, $urlResource);
            ?>
            <div class="info">
                <h3>XML Representation:</h3>
                <pre><?= htmlspecialchars($xmlBody) ?></pre>
            </div>
        <?php endif; ?>
        
        <div style="margin-top: 30px;">
            <a href="index.php">← Back to Main Page</a> | 
            <a href="connected.php">View Connection Info</a> |
            <a href="CustomerCreate.php">Create Another Customer</a> |
        </div>
    </body>
    </html>
    
    <?php
    
} catch (Exception $e) {
    ?>
    <html>
    <head>
        <title>Customer Creation Error</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .error { color: red; background: #fff0f0; padding: 10px; border: 1px solid red; }
        </style>
    </head>
    <body>
        <h1>Customer Creation Error</h1>
        <div class="error">
            <h2>❌ Exception Occurred</h2>
            <p><strong>Message:</strong> <?= htmlspecialchars($e->getMessage()) ?></p>
            <p><strong>File:</strong> <?= $e->getFile() ?></p>
            <p><strong>Line:</strong> <?= $e->getLine() ?></p>
        </div>
        <a href="index.php">← Back to Main Page</a>
    </body>
    </html>
    <?php
}
