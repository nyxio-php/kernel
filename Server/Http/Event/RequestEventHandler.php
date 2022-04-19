<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Http\Event;

use Nyxio\Contract\Event\EventDispatcherInterface;
use Nyxio\Contract\Http\ContentType;
use Nyxio\Contract\Http\HttpStatus;
use Nyxio\Contract\Http\Method;
use Nyxio\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface;
use Nyxio\Contract\Kernel\Request\RequestHandlerInterface;
use Nyxio\Http\Exception\HttpException;
use Nyxio\Kernel\Event\ResponseEvent;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Swoole\Http\Response;

/**
 * @codeCoverageIgnore
 */
class RequestEventHandler
{
    public function __construct(
        private readonly RequestHandlerInterface $requestHandler,
        private readonly ServerRequestFactoryInterface $requestFactory,
        private readonly ExceptionTransformerInterface $exceptionTransformer,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @param \Swoole\Http\Request $httpRequest
     * @param Response $httpResponse
     * @return void
     * @throws \JsonException
     */
    public function handle(\Swoole\Http\Request $httpRequest, Response $httpResponse): void
    {
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

        $this->eventDispatcher->dispatch(ResponseEvent::NAME, new ResponseEvent($response, $request ?? null));

        $httpResponse->setStatusCode($response->getStatusCode());
        $httpResponse->end((string)$response->getBody());
    }

    /**
     * @throws \JsonException
     * @throws HttpException
     */
    private function getRequest(\Swoole\Http\Request $swooleRequest): ServerRequestInterface
    {
        if (!empty($swooleRequest->files)) {
            throw new HttpException(HttpStatus::BadRequest, 'File upload not supported');
        }

        $uri = $swooleRequest->server['request_uri'];

        if (!empty($swooleRequest->server['query_string'])) {
            $uri .= '?' . $swooleRequest->server['query_string'];
        }

        $request = $this->requestFactory->createServerRequest(
            $swooleRequest->getMethod(),
            $uri,
            $swooleRequest->server,
        );

        if (!empty($swooleRequest->cookie)) {
            $request = $request->withCookieParams($swooleRequest->cookie);
        }

        if ($request->getMethod() !== Method::GET->value && !empty($swooleRequest->getContent())) {
            $request = $request->withParsedBody(
                \json_decode($swooleRequest->getContent(), true, 512, JSON_THROW_ON_ERROR)
            );
        }

        foreach ($swooleRequest->header as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }
}
