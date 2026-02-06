<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\Har\HarFileFactory;

final class RecorderHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    private static RecorderMode $mode = RecorderMode::PASS_THROUGH;
    private static string $record = 'default.har';

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly HarFileFactory $harFactory,
        private readonly string $recordsDir,
    ) {
    }

    public static function setMode(RecorderMode $mode): void
    {
        self::$mode = $mode;
    }

    public static function setRecord(string $record): void
    {
        self::$record = $record;
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (self::$mode === RecorderMode::PASS_THROUGH) {
            return $this->inner->request($method, $url, $options);
        }

        $har = $this->harFactory->load($this->getRecordPath());

        if (self::$mode === RecorderMode::PLAYBACK) {
            return $this->playback($har, $method, $url, $options);
        }

        if (self::$mode === RecorderMode::RECORD) {
            return $this->record($har, $method, $url, $options);
        }

        if (self::$mode === RecorderMode::NEW_EPISODES) {
            try {
                return $this->playback($har, $method, $url, $options);
            } catch(TransportException) {
                return $this->record($har, $method, $url, $options);
            }
        }
    }

    private function getRecordPath(): string
    {
        return $this->recordsDir.'/'.self::$record;
    }

    private function playback($har, $method, $url, $options): ResponseInterface
    {
        $response = $har->findEntry($method, $url, $options);
        
        return (new MockHttpClient($response))->request($method, $url, $options);
    }

    private function record($har, $method, $url, $options): ResponseInterface
    {
        $response = $this->inner->request($method, $url, $options);

        $har = $har->withEntry($response, $method, $url, $options);
        
        (new Filesystem())->dumpFile($this->getRecordPath(), json_encode($har->toArray(), flags:\JSON_PRETTY_PRINT));
        
        return (new MockHttpClient($response))->request($method, $url, $options);
    }
}
