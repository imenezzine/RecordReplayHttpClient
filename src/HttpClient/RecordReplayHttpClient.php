<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\HttpClientRecorderBundle\Enum\RecordReplayMode;
use Symfony\HttpClientRecorderBundle\Har\HarFile;

class RecordReplayHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    private static string $harFilename = 'default';

    public function __construct(
        private readonly HttpClientInterface $client,
        private readonly string $cassettePath,
        private readonly RecordReplayMode $mode = RecordReplayMode::REPLAY_OR_RECORD
    ) {
    }

    public static function setHarFilename(string $filename): void
    {
        static::$harFilename = $filename;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws \JsonException
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $harPath = $this->cassettePath . '/' . static::$harFilename;

        if (file_exists($harPath) && file_get_contents($harPath)) {
            $harFile = HarFile::createFromFile($harPath);
        } else {
            $harFile = HarFile::create();
        }

        if ($this->mode === RecordReplayMode::RECORD) {
            $response = $this->client->request($method, $url, $options);
            $harFile = $harFile->withEntry($response, $method, $url, $options);
            (new Filesystem())->dumpFile($harPath, json_encode($harFile->toArray(), \JSON_PRETTY_PRINT));
        }

        // Lecture HAR + fallback
        try {
            $response = (new MockHttpClient($harFile->find($method, $url, $options)))->request($method, $url, $options);
        } catch (TransportException $e) {
            if ($this->mode !== RecordReplayMode::RECORD_IF_MISSING) {
                throw $e;
            }

            // Mode RECORD_IF_MISSING
            $response = $this->client->request($method, $url, $options);
            $harFile = $harFile->withEntry($response, $method, $url, $options);
            (new Filesystem())->dumpFile($harPath, json_encode($harFile->toArray(), \JSON_PRETTY_PRINT));
        }

        return $response;
    }

    public function stream(ResponseInterface|iterable $responses, ?float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof ResponseInterface) {
            $responses = [$responses];
        }

        return new ResponseStream(MockResponse::stream($responses, $timeout));
    }
}
