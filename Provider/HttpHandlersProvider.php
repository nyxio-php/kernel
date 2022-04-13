<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Kernel\Request\RequestHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Server;

class HttpHandlersProvider implements ProviderInterface
{
    public function __construct(
        private readonly Server $server,
        private readonly RequestHandlerInterface $requestHandler,
        private readonly ServerRequestFactoryInterface $requestFactory,
    ) {
    }

    public function process(): void
    {
        $this->server->on('request', function (Request $httpRequest, Response $httpResponse) {
            $request = $this->getRequest($httpRequest);
            $response = $this->requestHandler->handle($request);

            foreach ($response->getHeaders() as $key => $headers) {
                $httpResponse->setHeader($key, $response->getHeaderLine($key));
            }

            $httpResponse->setStatusCode($response->getStatusCode());
            $httpResponse->end((string)$response->getBody());
        });
    }

    private function getRequest(Request $swooleRequest): ServerRequestInterface
    {
        $request = $this->requestFactory->createServerRequest(
            $swooleRequest->getMethod(),
            $swooleRequest->server['request_uri'],
            $swooleRequest->server,
        );

        $request->withParsedBody($swooleRequest->post);

        foreach ($swooleRequest->header as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
