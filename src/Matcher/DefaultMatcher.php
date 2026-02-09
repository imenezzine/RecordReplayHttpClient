<?php

namespace Symfony\HttpClientRecorderBundle\Matcher;

final class DefaultMatcher implements MatcherInterface
{
    public function matches(
        array $harEntry,
        string $method,
        string $url,
        array $options,
    ): bool {
        if (($harEntry['request']['method'] ?? null) !== $method) {
            return false;
        }

        if (($harEntry['request']['url'] ?? null) !== $url) {
            return false;
        }

        if (!isset($options['body'])) {
            return true;
        }

        $entryBody = $harEntry['request']['postData']['text'] ?? null;

        return $entryBody === $options['body'];
    }
}
