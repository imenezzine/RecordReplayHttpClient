<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\Clock\DatePoint;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\HttpClientRecorderBundle\Matcher\MatcherInterface;

/**
 * @psalm-type HarEntry = array{
 *     startedDateTime: string,
 *     request: array{
 *         method: string,
 *         url: string,
 *         postData: ?array{text: ?string},
 *     },
 *     response: array{
 *         status: int,
 *         headers: array<string, list<string>>,
 *         content: array{
 *             text: string,
 *             encoding?: string,
 *         },
 *     },
 * }
 * @psalm-type HarLog = array{
 *     version: string,
 *     creator: array{name: string},
 *     entries: list<HarEntry>,
 * }
 * @psalm-type HarData = array{log: HarLog}
 */
final class HarFile
{
    /**
     * @psalm-param HarData $har
     */
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
                [
                    'http_code' => $entry['response']['status'],
                    'response_headers' => $entry['response']['headers'] ?? [],
                ]
            );
        }

        throw new TransportException(sprintf('No HAR entry for "%s %s".', $method, $url));
    }

    public function addEntry(MatcherInterface $matcher, ResponseInterface $response, string $method, string $url, array $options = []): self
    {
        /** @psalm-var HarEntry $entry */
        $entry = [
            'startedDateTime' => (new DatePoint('now'))->format('Y-m-d\TH:i:s.v\Z'),
            'request' => [
                'method' => $method,
                'url' => $url,
                'postData' => isset($options['body'])
                    ? ['text' => (string) $options['body']]
                    : null,
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'headers' => $response->getHeaders(false),
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

    /**
     * @psalm-return HarData
     */
    public function toArray(): array
    {
        return $this->har;
    }

    /**
     * @psalm-param HarEntry['response']['content'] $content
     */
    private function decodeContent(array $content): string
    {
        return ($content['encoding'] ?? null) === 'base64'
            ? base64_decode($content['text'])
            : $content['text'];
    }
}
