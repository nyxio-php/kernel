<?php

declare(strict_types=1);

namespace Nyxio\Kernel\Text;

use Nyxio\Contract\Config\ConfigInterface;
use Nyxio\Contract\Kernel\Text\MessageInterface;

use function Nyxio\Helper\Text\getFormattedText;

class Message implements MessageInterface
{
    private const DEFAULT_LANG = 'en';

    public function __construct(private readonly ConfigInterface $config)
    {
    }

    public function text(string $message, array $params = []): string
    {
        $message = $this->config->get($this->getLangConfig($message), $message);

        return empty($params) ? $message : getFormattedText($message, $params);
    }

    private function getLangConfig(string $message): string
    {
        return \sprintf(
            'lang.%s.%s',
            $this->config->get('app.lang', static::DEFAULT_LANG),
            $message
        );
    }
}
