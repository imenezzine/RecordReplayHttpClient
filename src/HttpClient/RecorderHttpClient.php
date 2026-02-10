<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\Har\HarFileFactory;
use Symfony\HttpClientRecorderBundle\Matcher\DefaultMatcher;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;

final class RecorderHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    private static RecorderMode $mode = RecorderMode::PASS_THROUGH;
    private static string $record = 'default.har';

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly HarFileFactory $harFactory,
        private readonly string $recordsDir,
        private readonly MatcherInterface $matcher = new DefaultMatcher(),
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
        if (RecorderMode::PASS_THROUGH === self::$mode) {
            return $this->inner->request($method, $url, $options);
        }

        $har = $this->harFactory->load($this->getRecordPath());

        if (RecorderMode::PLAYBACK === self::$mode) {
            return $this->playback($har, $method, $url, $options);
        }

        if (RecorderMode::RECORD === self::$mode) {
            return $this->record($har, $method, $url, $options);
        }

        if (RecorderMode::NEW_EPISODES === self::$mode) {
            try {
                return $this->playback($har, $method, $url, $options);
            } catch (TransportException) {
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

        $har = $har->addEntry($response, $method, $url, $options);

        (new Filesystem())->dumpFile($this->getRecordPath(), json_encode($har->toArray(), flags: \JSON_PRETTY_PRINT));

        return (new MockHttpClient($response))->request($method, $url, $options);
    }
}
