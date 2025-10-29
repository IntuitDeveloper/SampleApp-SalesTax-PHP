<?php
// Active environment can be switched via APP_ENV or by editing the line below
$activeEnvironment = getenv('APP_ENV') ?: 'production'; // 'sandbox' or 'production'

// Common (environment-agnostic) values
$authorizationRequestUrl = 'https://appcenter.intuit.com/connect/oauth2';
$tokenEndPointUrl = 'https://oauth.platform.intuit.com/oauth2/v1/tokens/bearer';
$oauthScope = 'com.intuit.quickbooks.accounting indirect-tax.tax-calculation.quickbooks';
$openIDScope = 'openid profile email';

// Define per-environment settings
$environments = array(
  'production' => array(
    'client_id' => 'client_id',
    'client_secret' => 'client_secret',
    'oauth_redirect_uri' => 'https://3de80448658c.ngrok-free.app/OAuth_2/OAuth2PHPExample.php',
    'openID_redirect_uri' => 'https://3de80448658c.ngrok-free.app/OAuth_2/OAuthOpenIDExample.php',
    'mainPage' => 'https://3de80448658c.ngrok-free.app/OAuth_2/index.php',
    'refreshTokenPage' => 'https://3de80448658c.ngrok-free.app/OAuth_2/RefreshToken.php',
    'base_url' => 'https://quickbooks.api.intuit.com'
  ),
  'sandbox' => array(
    // TODO: Replace with your sandbox app credentials and URIs
    'client_id' => 'client_id',
    'client_secret' => 'client_secret',
    'oauth_redirect_uri' => 'https://7dd335cb56c1.ngrok-free.app/OAuth_2/OAuth2PHPExample.php',
    'openID_redirect_uri' => 'https://7dd335cb56c1.ngrok-free.app/OAuth_2/OAuthOpenIDExample.php',
    'mainPage' => 'https://7dd335cb56c1.ngrok-free.app/OAuth_2/index.php',
    'refreshTokenPage' => 'https://7dd335cb56c1.ngrok-free.app/OAuth_2/RefreshToken.php',
    'base_url' => 'https://sandbox-quickbooks.api.intuit.com'
  )
);

$active = isset($environments[$activeEnvironment]) ? $environments[$activeEnvironment] : $environments['sandbox'];

return array(
  // Environment: 'sandbox' or 'production'
  'environment' => $activeEnvironment,

  'authorizationRequestUrl' => $authorizationRequestUrl,
  'tokenEndPointUrl' => $tokenEndPointUrl,

  // Selected environment credentials and redirect URIs
  'client_id' => $active['client_id'],
  'client_secret' => $active['client_secret'],
  'oauth_scope' => $oauthScope,
  'openID_scope' => $openIDScope,
  'oauth_redirect_uri' => $active['oauth_redirect_uri'],
  'openID_redirect_uri' => $active['openID_redirect_uri'],
  'mainPage' => $active['mainPage'],
  'refreshTokenPage' => $active['refreshTokenPage'],
  'base_url' => $active['base_url'],
);
?>
