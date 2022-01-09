<?php

declare(strict_types=1);

namespace Nyx\Kernel\Request;

use Nyx\Contract\Container\ContainerInterface;
use Nyx\Contract\Http\ContentType;
use Nyx\Contract\Http\HttpStatus;
use Nyx\Contract\Http\MiddlewareInterface;
use Nyx\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface;
use Nyx\Contract\Kernel\Request\ActionCollectionInterface;
use Nyx\Contract\Kernel\Request\RequestHandlerInterface;
use Nyx\Contract\Routing\UriMatcherInterface;
use Nyx\Http\Exception\HttpException;
use Nyx\Http\Request;
use Nyx\Http\Response;
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
                    severRequest: $request,
                    actionCache:  $actionCache,
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
     * @param ServerRequestInterface $severRequest
     * @param ActionCache $actionCache
     * @return ResponseInterface|null
     * @throws \ReflectionException
     */
    private function getResponse(
        ServerRequestInterface $severRequest,
        ActionCache $actionCache,
    ): ?ResponseInterface {
        if (
            $actionCache->route->method->value !== $severRequest->getMethod()
            || !$this->matcher->compare($severRequest, $actionCache->route)
        ) {
            return null;
        }

        if (!empty($queryParams = $this->matcher->getQueryParams())) {
            $severRequest = $severRequest->withQueryParams(
                !empty($severRequest->getQueryParams())
                    ? $queryParams
                    : array_merge($severRequest->getQueryParams(), $queryParams)
            );
        }

        /** @var Response $response */
        $response = $this->container->get($actionCache->handleMethodParams['response'] ?? Response::class, [
            'response' => $this->responseFactory->createResponse(),
        ]);

        /** @var Request $request */
        $request = $this->container->get($actionCache->handleMethodParams['request'] ?? Request::class, [
            'request' => $severRequest,
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
