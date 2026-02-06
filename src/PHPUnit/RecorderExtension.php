<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit;

use PHPUnit\Event\Test\BeforeTestMethodCalled;
use PHPUnit\Event\Test\BeforeTestMethodCalledSubscriber;
use Symfony\HttpClientRecorderBundle\Attribute\UseRecord;
use Symfony\HttpClientRecorderBundle\HttpClient\RecordReplayHttpClient;

final class RecorderExtension implements BeforeTestMethodCalledSubscriber
{
    public function notify(BeforeTestMethodCalled $event): void
    {
        $method = new \ReflectionMethod(
            $event->testClassName(),
            $event->testMethodName()
        );

        foreach ($method->getAttributes(UseRecord::class) as $attribute) {
            $config = $attribute->newInstance();

            RecordReplayHttpClient::setMode($config->mode);
            RecordReplayHttpClient::setRecord($config->record);
        }
    }
}
