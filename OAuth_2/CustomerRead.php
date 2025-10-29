<?php
// Suppress PHP warnings for PHP 8.4 compatibility with SDK
error_reporting(E_ALL & ~E_DEPRECATED & ~E_WARNING);

session_start();
require_once "../vendor/autoload.php";

use QuickBooksOnline\API\DataService\DataService;

// Get configuration
$configs = include('./config.php');

// Check authentication
if (!isset($_SESSION['access_token']) || empty($_SESSION['access_token'])) {
    echo "<h2>Error: Not Authenticated</h2>";
    echo "<p>You need to complete the OAuth2 flow first.</p>";
    echo "<a href='index.php'>Go back to main page</a>";
    exit();
}

$access_token = $_SESSION['access_token'];
$refresh_token = $_SESSION['refresh_token'];
$realm_id = $_SESSION['realm_id'];

$customers = [];
$errorMessage = null;

try {

    // Setup DataService
    $dataService = DataService::Configure([
        'auth_mode' => 'oauth2',
        'ClientID' => $configs['client_id'],
        'ClientSecret' => $configs['client_secret'],
        'accessTokenKey' => $access_token,
        'refreshTokenKey' => $refresh_token,
        'QBORealmID' => $realm_id,
        'baseUrl' => $configs['base_url']
    ]);
    $dataService->throwExceptionOnError(true);

    // Optional: basic filtering by name via query string
    $filter = isset($_GET['q']) ? trim($_GET['q']) : '';
    if ($filter !== '') {
        $escaped = str_replace("'", "\\'", $filter);
        $query = "SELECT Id, DisplayName, FullyQualifiedName, Job, ParentRef FROM Customer WHERE DisplayName LIKE '%$escaped%' ORDER BY FullyQualifiedName";
    } else {
        $query = "SELECT Id, DisplayName, FullyQualifiedName, Job, ParentRef FROM Customer ORDER BY FullyQualifiedName";
    }

    $result = $dataService->Query($query);
    $customers = $result ?: [];

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Read Customers - QuickBooks API</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .info { background: #f0f8ff; padding: 12px; border: 1px solid #ccc; margin: 12px 0; border-radius: 5px; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background: #f7f7f7; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 12px; }
        .badge-job { background: #e6f7ff; color: #0366d6; }
        .nav { margin: 16px 0; }
        .nav a { text-decoration: none; background: #0077C5; color: white; padding: 8px 12px; border-radius: 5px; margin-right: 8px; }
        .nav a:hover { background: #005a9c; }
        .search { margin: 10px 0; }
        .search input { padding: 8px; width: 280px; }
        .search button { padding: 8px 12px; }
    </style>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
</head>
<body>
    <div class="container">
        <h1>Customers</h1>

        <div class="info">
            <strong>Environment:</strong> <?= htmlspecialchars($environment ?? 'Unknown') ?>
            | <strong>Realm ID:</strong> <?= htmlspecialchars($realm_id ?? '') ?>
        </div>

        <div class="nav">
            <a href="index.php">← Home</a>
            <a href="CustomerCreate.php">Create Customer</a>
        </div>

        <form class="search" method="get">
            <input type="text" name="q" placeholder="Filter by Display Name" value="<?= htmlspecialchars($filter ?? '') ?>" />
            <button type="submit">Search</button>
            <?php if (!empty($filter)): ?>
                <a href="CustomerRead.php" style="margin-left:8px;">Clear</a>
            <?php endif; ?>
        </form>

        <?php if ($errorMessage): ?>
            <div class="info error">
                <strong>Error:</strong> <?= htmlspecialchars($errorMessage) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($customers)): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Display Name</th>
                        <th>Fully Qualified Name</th>
                        <th>Type</th>
                        <th>Parent Id</th>
                        
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c->Id ?? '') ?></td>
                            <td><?= htmlspecialchars($c->DisplayName ?? '') ?></td>
                            <td><?= htmlspecialchars($c->FullyQualifiedName ?? '') ?></td>
                            <td>
                                <?php if (!empty($c->Job) && $c->Job): ?>
                                    <span class="badge badge-job">Job (Project)</span>
                                <?php else: ?>
                                    Customer
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c->ParentRef->value ?? '') ?></td>
                            
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="info">No customers found.</div>
        <?php endif; ?>

    </div>
</body>
</html>


