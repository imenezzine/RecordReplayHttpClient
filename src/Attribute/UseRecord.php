<?php

namespace Symfony\HttpClientRecorderBundle\Attribute;

use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;

#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class UseRecord
{
    public function __construct(
        public ?RecorderMode $mode = null,
        public ?string $record = null,
    ) {
    }
}