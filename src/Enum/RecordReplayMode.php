<?php

namespace Symfony\HttpClientRecorderBundle\Enum;

enum RecordReplayMode: string
{
    case REPLAY = 'replay';
    case RECORD_IF_MISSING = 'record_if_missing';
    case RECORD = 'record';
    case REPLAY_OR_RECORD = 'replay_or_record';

}
