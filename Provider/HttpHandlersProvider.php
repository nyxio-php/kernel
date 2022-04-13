<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Config\ConfigInterface;
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
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(): void
    {
        $this->server->on('start', function () {
            $this->serverStartMessage();
        });

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

    private function serverStartMessage(): void
    {
        echo sprintf(
            \PHP_EOL . 'Server is started at http://%s:%s' . \PHP_EOL,
            $this->config->get('server.host', '127.0.0.1'),
            $this->config->get('server.port', 9501)
        );

        echo "------------------------------\e[7mApplication settings\e[0m-------------------------------------------" . \PHP_EOL;
        echo sprintf(
            "* Debug mode: \e[1m%s\033[0m" . \PHP_EOL,
            $this->config->get('app.debug', false) ? "\033[31mYes" : "\033[32mNo"
        );
        echo sprintf("* Environments: \e[1m\033[32m%s\033[0m" . \PHP_EOL, $this->config->get('app.env', 'local'));
        echo sprintf("* Timezone: \e[1m\033[32m%s\033[0m" . \PHP_EOL, $this->config->get('app.timezone', 'UTC'));
        echo sprintf(
            "* Loaded providers: \e[1m\033[32m%d\033[0m" . \PHP_EOL,
            count($this->config->get('app.providers', []))
        );
        foreach ($this->config->get('app.providers', []) as $provider) {
            echo \sprintf(" - \e[1m\033[32m%s\033[0m" . \PHP_EOL, $provider);
        }
        echo sprintf(
            "* Loaded http actions: \e[1m\033[32m%d\033[0m" . \PHP_EOL,
            count($this->config->get('http.actions', []))
        );
        echo "---------------------------------------------------------------------------------------------" . \PHP_EOL;
    }
}
