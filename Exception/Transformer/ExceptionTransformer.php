<?php

declare(strict_types=1);

namespace Nyx\Kernel\Exception\Transformer;

use Nyx\Contract\Http\HttpStatus;
use Nyx\Contract\Kernel\Exception\Transformer\ExceptionTransformerInterface;
use Nyx\Http\Exception\HttpException;

class ExceptionTransformer implements ExceptionTransformerInterface
{
    public function __construct(private bool $debug = false)
    {
    }

    public function setDebug(bool $state): static
    {
        $this->debug = $state;

        return $this;
    }

    public function toArray(\Throwable $exception): array
    {
        if ($exception instanceof HttpException) {
            return $this->renderHttpException($exception);
        }

        return $this->renderException($exception);
    }

    private function renderHttpException(HttpException $httpException): array
    {
        $error = [
            'code' => $httpException->getCode(),
            'message' => $httpException->getMessage(),
        ];

        if (!empty($httpException->errors)) {
            $error['errors'] = $httpException->errors;
        }

        return $error;
    }

    private function renderException(\Throwable $content): array
    {
        if ($this->debug) {
            return [
                'code' => $content->getCode(),
                'message' => $content->getMessage(),
                'file' => $content->getFile(),
                'line' => $content->getLine(),
                'trace' => $content->getTraceAsString(),
                'previous' => $content->getPrevious() === null ? null : $this->renderException($content->getPrevious()),
            ];
        }

        return $this->renderHttpException(
            new HttpException(
                HttpStatus::InternalServerError,
                'Internal Server Error',
            )
        );
    }
}
