<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit\Attribute;

use Symfony\HttpClientRecorderBundle\RecorderMode;

/**
 * @example UseRecord('my_record.har', RecorderMode::Record)
 * @example UseRecord('./my_record.har', RecorderMode::Record)
 * @example UseRecord('../my_record.har', RecorderMode::Record)
 * @example UseRecord('/my_record.har', RecorderMode::Record)
 * @example UseRecord('@my_record.har', RecorderMode::Record)
 */
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
final readonly class UseRecord
{
    /**
     * @psalm-param RecorderMode::*|null $mode
     */
    public function __construct(
        public ?string $record = null,
        public ?string $mode = null,
    ) {
    }
}
