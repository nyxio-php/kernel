<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Server\Event;

use Nyxio\Contract\Config\ConfigInterface;

class StartEventHandler
{
    public function __construct(private readonly ConfigInterface $config,)
    {
    }

    public function handle(): void
    {
        $this->startMessage();
    }

    private function startMessage(): void
    {
        echo sprintf(
            \PHP_EOL . 'Server is started at http://%s:%s' . \PHP_EOL,
            $this->config->get('server.host', '127.0.0.1'),
            $this->config->get('server.port', 9501)
        );

        foreach ($this->config->get('server.options', []) as $key => $option) {
            echo \sprintf(" %s \e[1m\033[92m%s\033[0m" . \PHP_EOL, $key, $option);
        }

        echo "------------------------------\e[7mApplication settings\e[0m-------------------------------------------" . \PHP_EOL;
        echo sprintf(
            "* Debug mode: \e[1m%s\033[0m" . \PHP_EOL,
            $this->config->get('app.debug', false) ? "\033[91mYes" : "\033[92mNo"
        );
        echo sprintf("* Environments: \e[1m\033[92m%s\033[0m" . \PHP_EOL, $this->config->get('app.env', 'local'));
        echo sprintf("* Language: \e[1m\033[92m%s\033[0m" . \PHP_EOL, $this->config->get('app.lng', 'en'));
        echo sprintf("* Timezone: \e[1m\033[92m%s\033[0m" . \PHP_EOL, $this->config->get('app.timezone', 'UTC'));
        echo sprintf(
            "* Loaded providers: \e[1m\033[92m%d\033[0m" . \PHP_EOL,
            count($this->config->get('app.providers', []))
        );
        foreach ($this->config->get('app.providers', []) as $provider) {
            echo \sprintf(" - \e[1m\033[92m%s\033[0m" . \PHP_EOL, $provider);
        }
        echo sprintf(
            "* Loaded http actions: \e[1m\033[92m%d\033[0m" . \PHP_EOL,
            count($this->config->get('http.actions', []))
        );
        echo "---------------------------------------------------------------------------------------------" . \PHP_EOL;
    }
}
