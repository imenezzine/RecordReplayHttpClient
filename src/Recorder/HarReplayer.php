<?php

namespace Symfony\HttpClientRecorderBundle\Recorder;

use Symfony\HttpClientRecorderBundle\Har\HarFile;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HarReplayer
{
    public function replay(
        HarFile $har,
        string $method,
        string $url,
        array $options,
    ): ResponseInterface {
        return $har->find($method, $url, $options);
    }
}
