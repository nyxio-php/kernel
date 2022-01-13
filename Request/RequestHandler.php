<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Request;

use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Http\ContentType;
use Nyxio\Contract\Http\HttpStatus;
use Nyxio\Contract\Http\MiddlewareInterface;
use Nyxio\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface;
use Nyxio\Contract\Kernel\Request\ActionCollectionInterface;
use Nyxio\Contract\Kernel\Request\RequestHandlerInterface;
use Nyxio\Contract\Routing\UriMatcherInterface;
use Nyxio\Http\Exception\HttpException;
use Nyxio\Http\Request;
use Nyxio\Http\Response;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private readonly UriMatcherInterface $matcher,
        private readonly ContainerInterface $container,
        private readonly ExceptionTransformerInterface $exceptionTransformer,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ActionCollectionInterface $actionCollection
    ) {
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            foreach ($this->actionCollection->all() as $actionCache) {
                $response = $this->getResponse(
                    serverRequest: $request,
                    actionCache:   $actionCache,
                );

                if ($response === null) {
                    continue;
                }

                return $response;
            }

            throw new HttpException(HttpStatus::PageNotFound, 'Page Not Found');
        } catch (\Throwable $exception) {
            $httpException = $exception instanceof HttpException;

            $response = $this->responseFactory->createResponse(
                $httpException ? $exception->getCode() : HttpStatus::InternalServerError->value
            );

            $response = $response->withHeader('Content-Type', ContentType::Json->value);

            try {
                $response->getBody()->write(
                    \json_encode($this->exceptionTransformer->toArray($exception), JSON_THROW_ON_ERROR)
                );

                return $response;
            } catch (\JsonException) {
                $response = $this->responseFactory->createResponse(HttpStatus::InternalServerError->value);
                $response = $response->withHeader('Content-Type', ContentType::Json->value);
                $response->getBody()->write('Internal Server Error');

                return $response;
            }
        }
    }

    /**
     * @param ServerRequestInterface $serverRequest
     * @param ActionCache $actionCache
     * @return ResponseInterface|null
     * @throws \ReflectionException
     */
    private function getResponse(
        ServerRequestInterface $serverRequest,
        ActionCache $actionCache,
    ): ?ResponseInterface {
        if (
            $actionCache->route->method->value !== $serverRequest->getMethod()
            || !$this->matcher->compare($serverRequest, $actionCache->route)
        ) {
            return null;
        }

        if (!empty($queryParams = $this->matcher->getQueryParams())) {
            $serverRequest = $serverRequest->withQueryParams(
                empty($serverRequest->getQueryParams())
                    ? $queryParams
                    : array_merge($serverRequest->getQueryParams(), $queryParams)
            );
        }

        /** @var Response $response */
        $response = $this->container->get($actionCache->handleMethodParams['response'] ?? Response::class, [
            'response' => $this->responseFactory->createResponse(),
        ]);

        /** @var Request $request */
        $request = $this->container->get($actionCache->handleMethodParams['request'] ?? Request::class, [
            'request' => $serverRequest,
        ]);

        return $this->performMiddlewares(
            request:     $request,
            response:    $response,
            middlewares: \array_merge($actionCache->validations, $actionCache->middlewares),
            action: static function (Request $request, Response $response) use ($actionCache): ResponseInterface {
                return $actionCache->instance->handle($request, $response);
            }
        );
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param MiddlewareInterface[] $middlewares
     * @param \Closure $action
     * @return ResponseInterface
     */
    private function performMiddlewares(
        Request $request,
        Response $response,
        array $middlewares,
        \Closure $action
    ): ResponseInterface {
        if (empty($middlewares)) {
            return $action($request, $response);
        }

        $middleware = \array_shift($middlewares);

        if (!$middleware instanceof MiddlewareInterface) {
            return $this->performMiddlewares($request, $response, $middlewares, $action);
        }

        return $middleware->handle($request, $response, function (
            ?Request $nextRequest = null,
            ?Response $nextResponse = null
        ) use ($middlewares, $action, $request, $response) {
            return $this->performMiddlewares(
                request:     $nextRequest ?? $request,
                response:    $nextResponse ?? $response,
                middlewares: $middlewares,
                action:      $action
            );
        });
    }
}
