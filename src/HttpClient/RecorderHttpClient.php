<?php

namespace Symfony\HttpClientRecorderBundle\HttpClient;

use Symfony\Component\HttpClient\AsyncDecoratorTrait;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\Har\HarFileFactory;
use Symfony\HttpClientRecorderBundle\Har\HttpRecord;
use Symfony\HttpClientRecorderBundle\Matcher\DefaultMatcher;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;
use Symfony\HttpClientRecorderBundle\Store\StoreInterface;

final class RecorderHttpClient implements HttpClientInterface
{
    use AsyncDecoratorTrait;

    private static RecorderMode $mode = RecorderMode::PASS_THROUGH;
    private static string $record = 'default.har';

    public function __construct(
        private readonly HttpClientInterface $inner,
        private readonly HarFileFactory $harFactory,
        private readonly StoreInterface $store,
        private MatcherInterface $matcher = new DefaultMatcher(),
        private readonly string $recordsDir = '',
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

        $har = $this->harFactory->load(self::$record);

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

        throw new \RuntimeException('Unknown recorder mode');
    }

    private function playback($har, string $method, string $url, array $options): ResponseInterface
    {
        $response = $har->findEntry($this->matcher, $method, $url, $options);

        return (new MockHttpClient($response))->request($method, $url, $options);
    }

    private function record($har, string $method, string $url, array $options): ResponseInterface
    {
        $response = $this->inner->request($method, $url, $options);

        $record = new HttpRecord($response, $method, $url, $options);
        $har->addEntry($this->matcher, $record);

        $this->store->save(self::$record, $har->getRecords());

        return (new MockHttpClient($response))->request($method, $url, $options);
    }
}
