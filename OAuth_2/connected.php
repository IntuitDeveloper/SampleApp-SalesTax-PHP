<?php
session_start();

// Get authentication data
$access_token = $_SESSION['access_token'] ?? null;
$refresh_token = $_SESSION['refresh_token'] ?? null;
$realm_id = $_GET['realmId'] ?? $_SESSION['realm_id'] ?? null;
$state = $_GET['state'] ?? null;

// Store realm_id in session if provided
if ($realm_id) {
    $_SESSION['realm_id'] = $realm_id;
}
?>

<html>
<head>
  <title>Connected to QuickBooks</title>
</head>
<body>
  <h2>Connected Successfully!</h2>
  
  <h3>Authentication Information:</h3>
  <p><strong>Access Token:</strong> <?= htmlspecialchars($access_token ?? 'Not available') ?></p>
  <p><strong>Refresh Token:</strong> <?= htmlspecialchars($refresh_token ?? 'Not available') ?></p>
  <p><strong>Realm ID:</strong> <?= htmlspecialchars($realm_id ?? 'Not available') ?></p>
  <p><strong>State:</strong> <?= htmlspecialchars($state ?? 'Not available') ?></p>
  <p><strong>Session ID:</strong> <?= htmlspecialchars(session_id()) ?></p>
  
  <br>
  <div style="max-width: 1100px; margin: 10px 0; padding: 10px;">
    <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
      <a href="index.php" style="text-decoration:none;background:#6c757d;color:white;padding:10px 15px;border-radius:5px;">← Back to Home</a>
      <a href="CustomerCreate.php" style="text-decoration:none;background:#28a745;color:white;padding:10px 15px;border-radius:5px;">Create Customer</a>
      <a href="RefreshToken.php" style="text-decoration:none;background:#6c757d;color:white;padding:10px 15px;border-radius:5px;">Refresh Token</a>
      <a href="../IndirectTax/Calculate.php" style="text-decoration:none;background:#28a745;color:white;padding:10px 15px;border-radius:5px;">Indirect Tax (GraphQL)</a>
      <a href="../IndirectTax/SalesTaxStatus.php" style="text-decoration:none;background:#17a2b8;color:white;padding:10px 15px;border-radius:5px;">Sales Tax Status</a>
    </div>
  </div>
</body>
</html>
