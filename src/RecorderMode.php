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
    public const REPLAY_AND_RECORD_IF_MISSING = 'replay_and_record_if_missing';

    /**
     * Completely ignores the recording system and executes requests normally.
     */
    public const PASSTHROUGH = 'passthrough';
}
