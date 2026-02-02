<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @see http://www.softwareishard.com/blog/har-12-spec/
 */
final readonly class HarFile
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
                'creator' => [
                    'name' => 'Symfony HttpClient',
                ],
                'entries' => [],
            ],
        ]);
    }

    public static function createFromFile(string $file): self
    {
        if (!is_file($file)) {
            throw new \InvalidArgumentException(sprintf('Invalid HAR file "%s".', $file));
        }

        return new self(json_decode(file_get_contents($file), true, flags: \JSON_THROW_ON_ERROR));
    }

    public function find(string $method, string $url, array $options = []): ResponseInterface
    {
        foreach ($this->har['log']['entries'] as $entry) {
            $request = $entry['request'];
            $response = $entry['response'];

            if ($request['method'] !== $method || $request['url'] !== $url) {
                continue;
            }

            if (isset($options['body'])) {
                $body = $request['postData']['text'] ?? null;
                if ($options['body'] !== $body) {
                    continue;
                }
            }

            $headers = [];
            foreach ($response['headers'] as $header) {
                $headers[$header['name']][] = $header['value'];
            }

            $content = $response['content']['text'] ?? '';
            if (($response['content']['encoding'] ?? null) === 'base64') {
                $content = base64_decode($content);
            }

            return new MockResponse($content, [
                'http_code' => $response['status'],
                'response_headers' => $headers,
                'url' => $request['url'],
            ]);
        }

        throw new TransportException(sprintf('No HAR entry for "%s %s".', $method, $url));
    }

    public function withEntry(ResponseInterface $response, string $method, string $url, array $options = []): self
    {
        $har = $this->har;

        $responseHeaders = [];
        foreach ($response->getHeaders(false) as $name => $values) {
            foreach ($values as $value) {
                $responseHeaders[] = ['name' => $name, 'value' => $value];
            }
        }

        $body = $response->getContent(false);
        $encoding = null;

        if (preg_match('/[^\x20-\x7E\t\r\n]/', $body)) {
            $body = base64_encode($body);
            $encoding = 'base64';
        }

        $har['log']['entries'][] = [
            'startedDateTime' => gmdate('c'),
            'request' => [
                'method' => $method,
                'url' => $url,
                'postData' => isset($options['body']) ? ['text' => (string) $options['body']] : [],
            ],
            'response' => [
                'status' => $response->getStatusCode(),
                'headers' => $responseHeaders,
                'content' => array_filter([
                    'text' => $body,
                    'encoding' => $encoding,
                ]),
            ],
        ];

        return new self($har);
    }

    public function toArray(): array
    {
        return $this->har;
    }
}
