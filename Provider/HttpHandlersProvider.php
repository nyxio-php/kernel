<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Http\ContentType;
use Nyxio\Contract\Http\HttpStatus;
use Nyxio\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface;
use Nyxio\Contract\Kernel\Request\RequestHandlerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Psr\Http\Message\ResponseFactoryInterface;
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
        private readonly ExceptionTransformerInterface $exceptionTransformer,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(): void
    {
        $this->server->on('request', function (Request $httpRequest, Response $httpResponse) {
            try {
                $request = $this->getRequest($httpRequest);
                $response = $this->requestHandler->handle($request);
            } catch (\Throwable $exception) {
                $response = $this->responseFactory->createResponse(HttpStatus::InternalServerError->value);
                $response = $response->withHeader('Content-Type', ContentType::Json->value);
                $response->getBody()->write(
                    \json_encode($this->exceptionTransformer->toArray($exception), JSON_THROW_ON_ERROR)
                );
            }

            foreach ($response->getHeaders() as $key => $headers) {
                $httpResponse->setHeader($key, $response->getHeaderLine($key));
            }

            $httpResponse->setStatusCode($response->getStatusCode());
            $httpResponse->end((string)$response->getBody());
        });
    }

    /**
     * @throws \JsonException
     */
    private function getRequest(Request $swooleRequest): ServerRequestInterface
    {
        $uri = $swooleRequest->server['request_uri'];

        if (!empty($swooleRequest->server['query_string'])) {
            $uri .= '?' . $swooleRequest->server['query_string'];
        }

        $request = $this->requestFactory->createServerRequest(
            $swooleRequest->getMethod(),
            $uri,
            $swooleRequest->server,
        );

        if (!empty($swooleRequest->getContent())) {
            $request = $request->withParsedBody(
                \json_decode($swooleRequest->getContent(), true, 512, JSON_THROW_ON_ERROR)
            );
        }

        $request->withParsedBody($swooleRequest->post);

        foreach ($swooleRequest->header as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
