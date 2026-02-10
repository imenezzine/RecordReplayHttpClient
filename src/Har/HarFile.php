<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;

final class HarFile
{
    public function __construct(
        private array $har,
    ) {
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

    public static function createFromFile(string $path): self
    {
        if (!is_file($path)) {
            return self::create();
        }

        return new self(json_decode(file_get_contents($path), true, \JSON_THROW_ON_ERROR));
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

    public function addEntry(MatcherInterface $matcher, ResponseInterface $response, string $method, string $url, array $options = []): self
    {
        $entry = [
            'startedDateTime' => (new DatePoint('now'))->format('Y-m-d\TH:i:s.v\Z'),
            'request' => [
                'method' => $method,
                'url' => $url,
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'content' => [
                    'text' => $response->getContent(false),
                ],
            ],
        ];

        foreach ($this->har['log']['entries'] as $index => $existingEntry) {
            if ($matcher->matches($existingEntry, $method, $url, $options)) {
                $this->har['log']['entries'][$index] = $entry;

                return $this;
            }
        }

        $this->har['log']['entries'][] = $entry;

        return $this;
    }

    private function matches(array $entry, string $method, string $url, array $options): bool
    {
        if ($entry['request']['method'] !== $method) {
            return false;
        }

        if ($entry['request']['url'] !== $url) {
            return false;
        }

        $expectedBody = $options['body'] ?? null;
        $actualBody = $entry['request']['postData']['text'] ?? null;

        return $expectedBody === $actualBody;
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
