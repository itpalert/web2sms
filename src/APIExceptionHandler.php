<?php

namespace ITPalert\Web2sms;

use Psr\Http\Message\ResponseInterface;
use ITPalert\Web2sms\Exceptions\Exception;

class APIExceptionHandler
{
    public function __invoke(ResponseInterface $response)
    {
        $status = $response->getStatusCode();
        $body = (string) $response->getBody();
        $response->getBody()->rewind();

        $data = json_decode($body, true);
        $errorMessage = $data['error']['message'] ?? $body;
        
        $message = sprintf(
            'Web2SMS API Error [%d]: %s',
            $status,
            $errorMessage
        );

        return new Exception($message, $status);
    }
}