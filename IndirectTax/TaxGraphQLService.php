<?php
declare(strict_types=1);

namespace IndirectTax;

require_once __DIR__ . '/HttpException.php';
require_once __DIR__ . '/GraphQLClient.php';

class TaxGraphQLService
{
    /** @var array|null */
    private $lastRequestMeta = null;
    /**
     * Prepare GraphQL variables by loading the template and mapping caller inputs.
     *
     * @param string $transactionDate yyyy-MM-dd
     * @param string $customerId
     * @param string $shipFromZip
     * @param string $shipToZip
     * @param int $numberOfUnits
     * @param float $unitValue
     * @param string $productVariantId Pass "None" if not available
     * @return array
     * @throws \RuntimeException when variables template file cannot be read or parsed
     */
    public function prepareVariables(
        string $transactionDate,
        string $customerId,
        string $shipFromZip,
        string $shipToZip,
        int $numberOfUnits,
        float $unitValue,
        string $productVariantId
    ): array {
        $template = $this->loadVariablesTemplate();

        // Ensure structure exists
        if (!isset($template['input']) || !is_array($template['input'])) {
            $template['input'] = array();
        }

        // Map inputs
        $template['input']['transactionDate'] = $transactionDate;

        if (!isset($template['input']['subject']) || !is_array($template['input']['subject'])) {
            $template['input']['subject'] = array();
        }
        $template['input']['subject']['qbCustomerId'] = $customerId;

        if (!isset($template['input']['shipping']) || !is_array($template['input']['shipping'])) {
            $template['input']['shipping'] = array();
        }
        if (!isset($template['input']['shipping']['shipFromAddress']) || !is_array($template['input']['shipping']['shipFromAddress'])) {
            $template['input']['shipping']['shipFromAddress'] = array();
        }
        if (!isset($template['input']['shipping']['shipToAddress']) || !is_array($template['input']['shipping']['shipToAddress'])) {
            $template['input']['shipping']['shipToAddress'] = array();
        }
        $template['input']['shipping']['shipFromAddress']['freeFormAddressLine'] = $shipFromZip;
        $template['input']['shipping']['shipToAddress']['freeFormAddressLine'] = $shipToZip;

        if (!isset($template['input']['lineItems']) || !is_array($template['input']['lineItems'])) {
            $template['input']['lineItems'] = array();
        }
        if (!isset($template['input']['lineItems'][0]) || !is_array($template['input']['lineItems'][0])) {
            $template['input']['lineItems'][0] = array();
        }
        $template['input']['lineItems'][0]['numberOfUnits'] = $numberOfUnits;

        if (!isset($template['input']['lineItems'][0]['pricePerUnitExcludingTaxes']) || !is_array($template['input']['lineItems'][0]['pricePerUnitExcludingTaxes'])) {
            $template['input']['lineItems'][0]['pricePerUnitExcludingTaxes'] = array();
        }
        $template['input']['lineItems'][0]['pricePerUnitExcludingTaxes']['value'] = $unitValue;

        if (!isset($template['input']['lineItems'][0]['productVariantTaxability']) || !is_array($template['input']['lineItems'][0]['productVariantTaxability'])) {
            $template['input']['lineItems'][0]['productVariantTaxability'] = array();
        }
        $template['input']['lineItems'][0]['productVariantTaxability']['productVariantId'] = (trim($productVariantId) === '' ? 'None' : $productVariantId);

        return $template;
    }

    /**
     * Execute the GraphQL request using cURL.
     *
     * @param string $accessTokenBearer Authorization header value (e.g., "Bearer <token>")
     * @param array $variables Prepared variables array (must contain key 'input')
     * @return array Decoded response body
     * @throws \RuntimeException on HTTP errors or transport failures
     */
    public function calculateTax(string $accessTokenBearer, array $variables): array
    {
        $graphqlUrl = $this->resolveGraphqlUrl();
        $mutation = $this->loadMutation();
        // Use shared GraphQL client helper
        $tokenOnly = trim((string)preg_replace('/^Bearer\s+/i', '', $accessTokenBearer));
        $host = parse_url($graphqlUrl, PHP_URL_HOST) ?: 'qb.api.intuit.com';
        $exec = GraphQLClient::execute($mutation, $variables, $tokenOnly, array(
            'endpoint' => $graphqlUrl,
            'user_agent' => 'PHPSampleIndirectTaxAPI/1.0',
            'host' => $host
        ));

        $this->lastRequestMeta = array(
            'endpoint' => $exec['endpoint'] ?? $graphqlUrl,
            'request_body_json' => $exec['request_body_json'] ?? null,
            'request_headers_ui' => $exec['request_headers_ui'] ?? null,
            'response_headers' => $exec['response_headers'] ?? null,
            'response_raw' => $exec['response_raw'] ?? null,
        );

        $decoded = $exec['data'];
        if ($decoded === null) {
            throw new \RuntimeException('Failed to parse GraphQL response JSON.');
        }

        // Friendly notice for environment limitations
        if (isset($decoded['errors']) && is_array($decoded['errors'])) {
            foreach ($decoded['errors'] as $err) {
                $msg = isset($err['message']) ? (string)$err['message'] : '';
                if (strpos($msg, '-37109') !== false) {
                    $decoded['notice'] = 'Sales tax calculation is not available in this environment. This may be due to sandbox limitations or account configuration. Please contact QuickBooks Developer Support.';
                    break;
                }
            }
        }

        return $decoded;
    }

    /**
     * Expose the last built request metadata for UI/diagnostics.
     */
    public function getLastRequestMeta(): ?array
    {
        return $this->lastRequestMeta;
    }

    /**
     * Extract the total tax amount excluding shipping from the GraphQL response.
     * Returns null if not available.
     */
    public function extractTaxAmount(array $response): ?float
    {
        $path = array(
            'data',
            'indirectTaxCalculateSaleTransactionTax',
            'taxCalculation',
            'taxTotals',
            'totalTaxAmountExcludingShipping',
            'value'
        );

        $cursor = $response;
        foreach ($path as $key) {
            if (!is_array($cursor) || !array_key_exists($key, $cursor)) {
                return null;
            }
            $cursor = $cursor[$key];
        }

        if (is_numeric($cursor)) {
            return (float)$cursor;
        }
        return null;
    }

    /**
     * Resolve GraphQL endpoint URL, with QB_GRAPHQL_URL override support.
     */
    private function resolveGraphqlUrl(): string
    {
        $override = getenv('QB_GRAPHQL_URL');
        if ($override && trim($override) !== '') {
            return $override;
        }

        $env = getenv('QB_ENVIRONMENT') ?: 'production';
        if (strtolower($env) === 'production') {
            return 'https://qb.api.intuit.com/graphql';
        }
        return 'https://qb-sandbox.api.intuit.com/graphql';
    }

    /**
     * Load the mutation file contents from IndirectTax/graphql/sales_tax.graphql
     */
    private function loadMutation(): string
    {
        $path = $this->resolvePath('graphql/sales_tax.graphql');
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Unable to load GraphQL mutation from ' . $path);
        }
        return $content;
    }

    /**
     * Load the variables template JSON from IndirectTax/graphql/graphql_variables.json
     * and return as an array.
     */
    private function loadVariablesTemplate(): array
    {
        $path = $this->resolvePath('graphql/graphql_variables.json');
        $content = @file_get_contents($path);
        if ($content === false) {
            throw new \RuntimeException('Unable to load GraphQL variables template from ' . $path);
        }
        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Invalid variables template JSON in ' . $path);
        }
        return $decoded;
    }

    /**
     * Resolve a path relative to the IndirectTax module directory.
     */
    private function resolvePath(string $relative): string
    {
        // This file sits at {projectRoot}/IndirectTax/TaxGraphQLService.php
        $baseDir = __DIR__;
        return $baseDir . DIRECTORY_SEPARATOR . $relative;
    }
}


