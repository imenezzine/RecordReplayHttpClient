<?php

namespace Symfony\HttpClientRecorderBundle\Recorder;

use Symfony\HttpClientRecorderBundle\Har\HarFile;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HarRecorder
{
    public function record(
        ?HarFile $har,
        ResponseInterface $response,
        string $method,
        string $url,
        array $options,
    ): HarFile {
        return ($har ?? HarFile::create())->withEntry($response, $method, $url, $options);
    }
}
