<?php

namespace Symfony\HttpClientRecorderBundle\Har;

use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

final readonly class HttpRecord
{
    public function __construct(
        private ResponseInterface $response,
        private string $method,
        private string $url,
        private array $options = [],
    ) {
    }

    public static function fromArray(array $entry): self
    {
        $mock = new MockResponse(
            $entry['response']['content']['text'] ?? '',
            ['http_code' => $entry['response']['status'] ?? 200]
        );

        return new self(
            $mock,
            $entry['request']['method'] ?? 'GET',
            $entry['request']['url'] ?? '',
            $entry['request']['options'] ?? []
        );
    }

    public function toArray(): array
    {
        return [
            'request' => [
                'method' => $this->method,
                'url' => $this->url,
                'options' => $this->options,
            ],
            'response' => [
                'status' => $this->response->getStatusCode(),
                'content' => [
                    'text' => $this->response->getContent(false),
                ],
            ],
        ];
    }

    public function toResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
