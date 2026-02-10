<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;

final class HarFile
{
    public function __construct(private array $har)
    {
    }

    public static function create(): self
    {
        return new self([
            'log' => [
                'version' => '1.2',
                'creator' => ['name' => 'HttpRecorder'],
                'entries' => [],
            ],
        ]);
    }

    public function findEntry(MatcherInterface $matcher, string $method, string $url, array $options = []): ResponseInterface
    {
        foreach ($this->har['log']['entries'] as $entry) {
            if (!$matcher->matches($entry, $method, $url, $options)) {
                continue;
            }

            return new MockResponse(
                $this->decodeContent($entry['response']['content']),
                ['http_code' => $entry['response']['status']]
            );
        }

        throw new TransportException(sprintf('No HAR entry for "%s %s".', $method, $url));
    }

    public function addEntry(MatcherInterface $matcher, HttpRecord $record): self
    {
        $entry = $record->toArray();

        foreach ($this->har['log']['entries'] as $index => $existingEntry) {
            if ($matcher->matches($existingEntry, $record->getMethod(), $record->getUrl(), $record->getOptions())) {
                $this->har['log']['entries'][$index] = $entry;

                return $this;
            }
        }

        $this->har['log']['entries'][] = $entry;

        return $this;
    }

    public function toArray(): array
    {
        return $this->har;
    }

    private function decodeContent(array $content): string
    {
        return ($content['encoding'] ?? null) === 'base64'
            ? base64_decode($content['text'])
            : $content['text'];
    }
}
