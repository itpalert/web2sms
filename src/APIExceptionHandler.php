<?php

namespace ITPalert\Web2sms;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

use ITPalert\Web2sms\Exceptions\Request;
use ITPalert\Web2sms\Exceptions\Server;
use ITPalert\Web2sms\Exceptions\Exception;

class APIExceptionHandler
{
    /**
     * Format to use for the rfc7807 formatted errors
     *
     * @var string
     */
    protected $rfc7807Format = "%s: %s. See %s for more information";

    /**
     * @throws Exception\Exception
     *
     * @return Exception\Request|Exception\Server
     */
    public function __invoke(ResponseInterface $response)
    {
        $pos = strpos($response->getBody(), '{"error":{');
        $body = json_decode(substr($response->getBody(), $pos));

        $response->getBody()->rewind();

        // This message isn't very useful, but we shouldn't ever see it
        $errorTitle = 'Unexpected error';

        if (isset($body->error)) {
            $errorTitle = $body->error->message;
        }

        $status = (int)$response->getStatusCode();

        if ($status >= 400 && $status < 500) {
            $e = new Request($errorTitle, $status);
        } elseif ($status >= 500 && $status < 600) {
            $e = new Server($errorTitle, $status);
        } else {
            $e = new Exception('Unexpected HTTP Status Code');
            throw $e;
        }

        return $e;
    }
}