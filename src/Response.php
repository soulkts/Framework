<?php
namespace Wandu\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Wandu\Http\Traits\ResponseTrait;

class Response extends Message implements ResponseInterface
{
    use ResponseTrait;

    /**
     * @param int $statusCode
     * @param string $reasonPhrase
     * @param array $headers
     * @param \Psr\Http\Message\StreamInterface|null $body
     */
    public function __construct(
        $statusCode = 200,
        $reasonPhrase = '',
        array $headers = [],
        StreamInterface $body = null
    ) {
        $this->validStatusCode($statusCode);

        $this->statusCode = $statusCode;
        $this->reasonPhrase = $this->filterReasonPhrase($statusCode, $reasonPhrase);

        parent::__construct('1.1', $headers, $body);
    }
}
