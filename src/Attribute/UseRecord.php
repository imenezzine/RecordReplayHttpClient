<?php

namespace Symfony\HttpClientRecorderBundle\Attribute;

use Symfony\HttpClientRecorderBundle\Enum\RecordReplayMode;

#[\Attribute(\Attribute::TARGET_METHOD)]
final readonly class UseRecord
{
    public function __construct(
        public RecordReplayMode   $mode,
        public string $record,
    ) {
    }
}