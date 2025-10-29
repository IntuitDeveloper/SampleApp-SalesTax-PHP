## QuickBooks Sales Tax (Indirect Tax) API - PHP Sample

A PHP sample app that demonstrates calculating QuickBooks Indirect Sales Tax via GraphQL and checking Sales Tax enablement (Preferences) via REST. Includes OAuth 2.0 flow and simple UI pages for testing.

## Features

- **Indirect Tax via GraphQL**
  - Calculate sales tax for a simple sale transaction
  - Shows request URL, headers (masked), body, and full response
  - Handles errors and surfaces friendly notice for sandbox limitations (e.g., -37109)
- **Sales Tax Status (REST v3)**
  - Fetches Preferences to determine if `TaxPrefs.UsingSalesTax` is enabled
  - Displays raw JSON response and logs to the console
- **OAuth 2.0** with QuickBooks Online (tokens stored in PHP session)

## Pages (UI)

- OAuth Home: `OAuth_2/index.php`
- Indirect Tax (GraphQL): `IndirectTax/Calculate.php`
- Sales Tax Status (Preferences REST): `IndirectTax/SalesTaxStatus.php`

## GraphQL Operation

- Mutation: `indirectTaxCalculateSaleTransactionTax`
  - Definitions in `IndirectTax/graphql/sales_tax.graphql`
  - Variables template in `IndirectTax/graphql/graphql_variables.json`

### Endpoint Resolution

- If `QB_GRAPHQL_URL` is set, it is used
- Else if `QB_ENVIRONMENT=production` → `https://qb.api.intuit.com/graphql`
- Else (default sandbox) → `https://qb-sandbox.api.intuit.com/graphql`

## Configuration

Edit `OAuth_2/config.php` and set:

- `client_id`, `client_secret`
- Environment selection is automatic via `APP_ENV` (defaults to `sandbox`). Both `production` and `sandbox` blocks are defined with per-env `base_url` and redirect URLs.
- URLs (use your ngrok domain when testing):
  - `oauth_redirect_uri` → `https://<ngrok-domain>/OAuth_2/OAuth2PHPExample.php`
  - `openID_redirect_uri` → `https://<ngrok-domain>/OAuth_2/OAuthOpenIDExample.php`
  - `mainPage` → `https://<ngrok-domain>/OAuth_2/index.php`
  - `refreshTokenPage` → `https://<ngrok-domain>/OAuth_2/RefreshToken.php`

Required scopes typically include `com.intuit.quickbooks.accounting`.

Environment variables used by GraphQL service (optional):

- `QB_ENVIRONMENT=sandbox|production`
- `QB_GRAPHQL_URL` (override endpoint)

## Run locally

```bash
composer install
php -S 127.0.0.1:5001 -t . | cat
# In another terminal
ngrok http 5001
```

Open `https://<ngrok-domain>/OAuth_2/index.php` and complete the OAuth flow.

## Usage notes

- GraphQL requests include: `Authorization: Bearer <token>`, `Content-Type: application/json`, `Accept: application/json;charset=UTF-8`, `User-Agent`, `Host` (and optionally `intuit-realm-id` when needed)
- UI shows request URL, masked headers, request body, and response (headers/body). Errors log details to the browser console.
- Logging: Requests/responses are appended to `logs/graphql_requests.log`.

## Troubleshooting

- 401 AuthenticationFailed: refresh tokens or re-authenticate
- 403/UNAUTHORIZED/DENY: verify entitlements/scopes and correct environment/realm
- `-37109` notice: sales tax calculation not available in this environment (sandbox limitation or account config). Try production or contact QuickBooks Developer Support.
- Validation errors: ensure a valid `qbCustomerId` in the same realm, `transactionDate` format `yyyy-MM-dd`, positive unit values, and address fields as required by schema.

## Ignore local changes to `OAuth_2/config.php` (keep tracked remotely)

If you want to prevent your local edits to `OAuth_2/config.php` from showing up in `git status` while keeping the file tracked on remote, use Git's skip-worktree flag:

Also ensure the OAuth redirect URIs configured above are added to your QuickBooks Developer app settings (Redirect URIs) exactly, including protocol and path.

After authenticating, create a customer using `OAuth_2/CustomerCreate.php` and copy the returned Customer Id to use in the Sales Tax UI.

```bash
# Ignore local changes going forward



# Verify (lines starting with S are skipped)
git ls-files -v | grep '^S'

# Stop ignoring later (to allow updates/pulls)
git update-index --no-skip-worktree OAuth_2/config.php
```

Note: This is a local-only setting; it does not modify the remote repository history.


