<?php
declare(strict_types=1);

namespace IndirectTax;

class HttpException extends \RuntimeException
{
    /** @var int */
    private $statusCode;

    /** @var string */
    private $responseBody;

    /** @var string */
    private $responseHeaders;

    public function __construct(string $message, int $statusCode, string $responseBody, string $responseHeaders = '')
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
        $this->responseBody = $responseBody;
        $this->responseHeaders = $responseHeaders;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getResponseBody(): string
    {
        return $this->responseBody;
    }

    public function getResponseHeaders(): string
    {
        return $this->responseHeaders;
    }
}


