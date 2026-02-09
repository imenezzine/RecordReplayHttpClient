<?php

namespace Symfony\HttpClientRecorderBundle\Enum;

enum RecorderMode: string
{
    case RECORD = 'record';
    case PLAYBACK = 'playback';
    case NEW_EPISODES = 'new_episodes';
    case PASS_THROUGH = 'passthrough';
}
