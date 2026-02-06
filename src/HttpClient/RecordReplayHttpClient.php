<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Component\HttpClient\Response\ResponseStream;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;
use Symfony\HttpClientRecorderBundle\Enum\RecordReplayMode;
use Symfony\HttpClientRecorderBundle\Har\HarFileFactory;

final class RecordReplayHttpClient implements HttpClientInterface
{
    use HttpClientTrait;

    private static RecordReplayMode $mode = RecordReplayMode::PASS_THROUGH;
    private static string $record = 'default.har';

    public static function setMode(RecordReplayMode $mode): void
    {
        self::$mode = $mode;
    }

    public static function setRecord(string $record): void
    {
        self::$record = $record;
    }

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly string $recordsDir,
        private readonly HarFileFactory $harFactory,
    ) {
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (self::$mode === RecordReplayMode::PASS_THROUGH) {
            return $this->inner->request($method, $url, $options);
        }

        $path = $this->recordsDir.'/'.self::$record;
        $har = $this->harFactory->load($path);
        $fs = new Filesystem();

        if (self::$mode !== RecordReplayMode::RECORD) {
            try {
                return (new MockHttpClient(
                    $har->findEntry($method, $url, $options)
                ))->request($method, $url, $options);
            } catch (\Throwable $e) {
                if (self::$mode === RecordReplayMode::PLAYBACK) {
                    throw $e;
                }
            }
        }

        $response = $this->inner->request($method, $url, $options);
        $har->withEntry($response, $method, $url, $options);

        $fs->dumpFile($path, json_encode($har->toArray(), \JSON_PRETTY_PRINT));

        return $response;
    }

    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        if ($responses instanceof ResponseInterface) {
            $responses = [$responses];
        }

        return new ResponseStream(MockResponse::stream($responses, $timeout));
    }
}
