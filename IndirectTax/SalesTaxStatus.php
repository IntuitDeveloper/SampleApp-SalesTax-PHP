<?php
declare(strict_types=1);

session_start();

$realmId = isset($_SESSION['realm_id']) ? $_SESSION['realm_id'] : null;
$hasToken = isset($_SESSION['access_token']) && $_SESSION['access_token'] ? true : false;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Sales Tax Status</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        pre { background: #f5f5f5; padding: 10px; overflow: auto; }
        .info { background: #f0f8ff; padding: 10px; border: 1px solid #ccc; margin: 10px 0; border-radius: 4px; }
        .error { background: #fff3f3; border-color: #ffb3b3; }
        .row { margin-bottom: 10px; }
        .btn { background: #0077C5; color: #fff; padding: 8px 12px; border-radius: 4px; text-decoration: none; border: none; cursor: pointer; }
    </style>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
</head>
<body>
    <div class="container">
        <h1>Sales Tax Status</h1>
        <div class="info">
            <div class="row"><strong>Realm ID:</strong> <?= htmlspecialchars((string)$realmId) ?></div>
            <div class="row"><strong>Access Token Present:</strong> <?= $hasToken ? 'Yes' : 'No' ?></div>
            <div class="row"><a class="btn" href="../OAuth_2/index.php" style="background:#6c757d">Back</a></div>
        </div>

        <div id="status" class="info">Loading Preferences...</div>
        <div id="details" class="info" style="display:none;"></div>

        <script>
        (function(){
            function setHTML(id, html){ var el = document.getElementById(id); if (el) el.innerHTML = html; }
            function show(id){ var el = document.getElementById(id); if (el) el.style.display = ''; }
            function esc(str){
                return String(str).replace(/[&<>]/g, function(s){ return ({'&':'&amp;','<':'&lt;','>':'&gt;'}[s]); });
            }
            fetch('api/preferences.php', { credentials: 'same-origin' })
                .then(function(r){ return r.text().then(function(t){ return { status: r.status, body: t, headers: r.headers }; }); })
                .then(function(resp){
                    console.group('Preferences Fetch');
                    console.log('HTTP Status:', resp.status);
                    console.log('Body:', resp.body);
                    console.groupEnd();
                    try { var data = JSON.parse(resp.body); } catch(e) { data = null; }
                    if (!data) {
                        setHTML('status', 'Invalid JSON response.');
                        setHTML('details', '<pre>'+esc(resp.body)+'</pre>');
                        show('details');
                        return;
                    }
                    var using = false;
                    try { using = !!(data.Preferences.TaxPrefs.UsingSalesTax); } catch(e) {}
                    setHTML('status', using ? 'Sales tax is ENABLED' : 'Sales tax is NOT ENABLED');
                    setHTML('details', '<pre>'+esc(JSON.stringify(data, null, 2))+'</pre>');
                    show('details');
                })
                .catch(function(err){
                    setHTML('status', 'Failed to load: '+esc(err && err.message ? err.message : err));
                });
        })();
        </script>
    </div>
</body>
</html>



