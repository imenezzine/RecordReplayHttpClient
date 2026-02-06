<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\Clock\ClockInterface;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class HarFile
{
    public function __construct(
        private array $har,
        private ClockInterface $clock,
    ) {
    }

    public static function create(ClockInterface $clock): self
    {
        return new self([
            'log' => [
                'version' => '1.2',
                'creator' => ['name' => 'HttpRecorder'],
                'entries' => [],
            ],
        ], $clock);
    }

    public static function createFromFile(string $path, ClockInterface $clock): self
    {
        if (!is_file($path)) {
            return self::create($clock);
        }

        return new self(
            json_decode(file_get_contents($path), true, \JSON_THROW_ON_ERROR),
            $clock
        );
    }

    public function findEntry(string $method, string $url, array $options = []): ResponseInterface
    {
        foreach ($this->har['log']['entries'] as $entry) {
            if ($entry['request']['method'] !== $method || $entry['request']['url'] !== $url) {
                continue;
            }

            return new MockResponse(
                $this->decodeContent($entry['response']['content']),
                ['http_code' => $entry['response']['status']]
            );
        }

        throw new TransportException(sprintf('No HAR entry for "%s %s".', $method, $url));
    }

    public function withEntry(ResponseInterface $response, string $method, string $url, array $options = []): self
    {
        $startedDateTime = $this->clock
            ->now()
            ->setTimezone(new \DateTimeZone('UTC'))
            ->format('Y-m-d\TH:i:s.v\Z');

        $this->har['log']['entries'][] = [
            'startedDateTime' => $startedDateTime,
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
