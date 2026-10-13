<?php

namespace Replier;

/**
 * Thrown when the API answers with "success": false, or when the HTTP call itself fails.
 */
class ReplierException extends \RuntimeException
{
    /** @var string|null API error code, e.g. "0054" */
    private $errorCode;

    /** @var array|null Decoded response body, when there was one */
    private $response;

    public function __construct(string $message, ?string $errorCode = null, ?array $response = null, int $httpStatus = 0)
    {
        parent::__construct($message, $httpStatus);
        $this->errorCode = $errorCode;
        $this->response = $response;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }
}
