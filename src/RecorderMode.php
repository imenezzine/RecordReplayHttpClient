<?php

namespace Symfony\HttpClientRecorderBundle;

enum RecorderMode: string
{
    /**
     * Records all HTTP requests into the HAR file.
     */
    public const RECORD = 'record';

    /**
     * Replays HTTP requests from the HAR file.
     */
    public const REPLAY = 'replay';

    /**
     * Tries to find an existing record, create one if none then replays it.
     */
    public const RECORD_IF_MISSING_AND_REPLAY = 'record_is_missing_and_replay';

    /**
     * Completely ignores the recording system and executes requests normally.
     */
    public const PASSTHROUGH = 'passthrough';
}
