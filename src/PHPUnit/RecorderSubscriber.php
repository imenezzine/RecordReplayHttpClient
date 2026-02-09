<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Symfony\HttpClientRecorderBundle\Attribute\UseRecord;
use Symfony\HttpClientRecorderBundle\Enum\RecorderMode;
use Symfony\HttpClientRecorderBundle\HttpClient\RecorderHttpClient;

final class RecorderSubscriber implements PreparationStartedSubscriber
{
    public function notify(PreparationStarted $event): void
    {
        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        $className = $test->className();
        $methodName = $test->methodName();

        $mode = null;
        $record = null;

        if ($attributes = new \ReflectionMethod($className, $methodName)->getAttributes(UseRecord::class)) {
            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $mode = $inst->mode;
            $record = $inst->record;
        }

        if ($attributes = new \ReflectionClass($className)->getAttributes(UseRecord::class)) {
            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $mode ??= $inst->mode;
            $record ??= $inst->record;
        }

        if (!$record) {
            return;
        }

        RecorderHttpClient::setMode($mode ?? RecorderMode::PLAYBACK);
        RecorderHttpClient::setRecord($record);
    }
}
