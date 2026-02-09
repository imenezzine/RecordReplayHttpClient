<?php

namespace Symfony\HttpClientRecorderBundle\Matcher;

interface MatcherInterface
{
    /**
     * @param array<string, mixed> $harEntry
     * @param array<string, mixed> $options
     */
    public function matches(
        array $harEntry,
        string $method,
        string $url,
        array $options,
    ): bool;
}
