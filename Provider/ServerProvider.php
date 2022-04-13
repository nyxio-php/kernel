<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Provider;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Container\ContainerInterface;
use Nyxio\Contract\Provider\ProviderInterface;
use Nyxio\Contract\Server\ServerProtocol;
use Swoole\Server;

class ServerProvider implements ProviderInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ConfigInterface $config,
    ) {
    }

    public function process(): void
    {
        $this->container->singletonFn(Server::class, function () {
            $protocol = $this->config->get('server.protocol', ServerProtocol::HTTP);

            if (!$protocol instanceof \BackedEnum) {
                throw new \RuntimeException(
                    \sprintf('Invalid protocol %s', $protocol)
                );
            }

            $host = $this->config->get('server.host', '127.0.0.1');
            $port = $this->config->get('server.port', 9501);

            $server = match ($protocol) {
                ServerProtocol::HTTP => new \Swoole\Http\Server($host, $port),
                ServerProtocol::WebSocket => new \Swoole\WebSocket\Server($host, $port),
            };

            $server->on('start', function () {
                $this->serverStartMessage();
            });

            $server->set($this->config->get('server.options'));

            return $server;
        });
    }

    private function serverStartMessage(): void
    {
        echo sprintf(
            \PHP_EOL . 'Server is started at %s://%s:%s' . \PHP_EOL,
            $this->config->get('server.protocol', ServerProtocol::HTTP)->value,
            $this->config->get('server.host', '127.0.0.1'),
            $this->config->get('server.port', 9501)
        );

        foreach ($this->config->get('server.options', []) as $key => $option) {
            echo \sprintf(" %s \e[1m\033[32m%s\033[0m" . \PHP_EOL, $key, $option);
        }

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
