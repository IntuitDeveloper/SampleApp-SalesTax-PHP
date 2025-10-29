<?php
$session_id = session_id();
if (empty($session_id))
{
    session_start();
}
$configs = include('./config.php');
$redirect_uri = $configs['oauth_redirect_uri'];
$openID_redirect_uri = $configs['openID_redirect_uri'];
$refreshTokenPage = $configs['refreshTokenPage'];
 ?>
<html>


<title>My Connect Page</title>


<?php

  if(isset($_SESSION['access_token']) && !empty($_SESSION['access_token'])){
    echo "<h2 style='text-align:center; margin:50px 0'>Retrieve OAuth 2 Tokens from Sessions:</h2>";    
    $tokens = array(
       'access_token' => $_SESSION['access_token'],
       'refresh_token' => $_SESSION['refresh_token']
    );

    echo "<p><strong>Access Token:</strong> {$_SESSION['access_token']}</p>";
    echo "<p><strong>Refresh Token:</strong> {$_SESSION['refresh_token']}</p>";

    echo "<br /> <a href='" .$refreshTokenPage . "'>
          Refresh Token
    </a> <br />";
    echo "<br /> <a href='" .$refreshTokenPage . "?deleteSession=true'>
          Clean Session
    </a> <br />";
  } else{
    echo "<h3>Please Complete the \"Connect to QuickBooks\" OAuth 2 flow:</h3>";
    echo '<div> Add the OAuth 2 Consumer Key and OAuth 2 Consumer Secret of your application to config.php file to enable OAuth2 flow.</div> </br>
          <div> Add the oauth_redirect_uri to config.php file. This URL is used by Intuit to redirect the user to your page when user authorized your app. </div> </br>
          <div> Click on the button below to start "Connect to QuickBooks"</div>';
    echo "<br /> <a class='imgLink' href='/OAuth_2/OAuth2PHPExample.php'><img style='height: 40px' src='C2QB_white_btn.png'/></a> <br />";
    echo "<h3>Please Complete the \"Sign In With Intuit\" flow:</h3>";
    echo '<div> Add the OAuth 2 Consumer Key and OAuth 2 Consumer Secret of your application to config.php file to enable OpenID flow.</div> </br>
          <div> Add the openID_redirect_uri to config.php file. This URL is used by Intuit to redirect the user to your page when the user agreed for your app retrieving their personal information. </div> </br>
          <div> Click on the button below to start "Sign in with Intuit"</div>';
    echo "<a class='imgLink' href='/OAuth_2/OAuthOpenIDExample.php'><img style='height: 40px' src='IntuitSignIn-lg.jpg'/></a>";
  }
 ?>



</html>

<!-- Navigation to all pages -->
<div style="max-width: 1100px; margin: 20px auto; padding: 10px;">
  <h3 style="margin: 10px 0;">Navigation</h3>
  <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
    <a href="/OAuth_2/CustomerCreate.php" style="text-decoration:none;background:#0077C5;color:white;padding:10px 15px;border-radius:5px;">Create Customer</a>
    
    
    <a href="/OAuth_2/connected.php" style="text-decoration:none;background:#6c757d;color:white;padding:10px 15px;border-radius:5px;">Connected</a>
    <a href="/OAuth_2/RefreshToken.php" style="text-decoration:none;background:#6c757d;color:white;padding:10px 15px;border-radius:5px;">Refresh Token</a>
    <a href="/IndirectTax/Calculate.php" style="text-decoration:none;background:#28a745;color:white;padding:10px 15px;border-radius:5px;">Indirect Tax (GraphQL)</a>
    <a href="/IndirectTax/SalesTaxStatus.php" style="text-decoration:none;background:#17a2b8;color:white;padding:10px 15px;border-radius:5px;">Sales Tax Status</a>
  </div>
</div>

</html>