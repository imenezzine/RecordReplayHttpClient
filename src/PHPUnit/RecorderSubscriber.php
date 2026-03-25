<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit;

use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;
use Symfony\HttpClientRecorderBundle\HttpClient\RecorderHttpClient;
use Symfony\HttpClientRecorderBundle\PHPUnit\Attribute\UseRecord;
use Symfony\HttpClientRecorderBundle\RecorderMode;

final class RecorderSubscriber implements PreparationStartedSubscriber
{
    public function __construct(
        private string $defaultDirectory,
    ) {
        $this->defaultDirectory = \rtrim($this->defaultDirectory, '/').'/';
    }

    public function notify(PreparationStarted $event): void
    {
        RecorderHttpClient::setRecord('default.har');
        RecorderHttpClient::setMode(RecorderMode::PASSTHROUGH);

        $test = $event->test();

        if (!$test instanceof TestMethod) {
            return;
        }

        $attributeData = $this->loadUseRecordAttribute($test);

        if (false === $attributeData) {
            return;
        }

        $currentTestDir = \dirname($test->file());

        $record = $attributeData[0] ?: $currentTestDir.'/'.$test->className().'/'.$test->methodName().'.har';
        $mode = $attributeData[1] ?: RecorderMode::RECORD_IF_MISSING_AND_REPLAY;

        if (\str_starts_with($record, '@')) {
            $record = \substr($record, 1);
            $record = "{$this->defaultDirectory}{$record}";
        } elseif (false === \str_starts_with($record, '/')) {
            $record = "{$currentTestDir}/{$record}";
        }

        RecorderHttpClient::setRecord($record); // TODO: When creating test for this method: make sure it is always absolute
        RecorderHttpClient::setMode($mode);
    }

    /**
     * @psalm-return false|array{0: string, 1: RecorderMode::*|string}
     */
    private function loadUseRecordAttribute(TestMethod $test): false|array
    {
        $className = $test->className();
        $methodName = $test->methodName();

        $attributeFound = false;
        $mode = null;
        $record = null;

        if ($attributes = (new \ReflectionClass($className))->getAttributes(UseRecord::class)) {
            // TODO: using mode record could lead to unwanted side effects : it would override each other.

            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $record = $inst->record ?? "./{$className}.har"; // TODO: or "@{$className}.har" ? (defaultDirectory)
            $mode = $inst->mode;
            $attributeFound = true;
        }

        if ($attributes = (new \ReflectionMethod($className, $methodName))->getAttributes(UseRecord::class)) {
            if ($attributeFound) {
                throw new \LogicException('Cannot use #[UseRecord] attribute on both class and method.');
            }

            /** @var UseRecord $inst */
            $inst = $attributes[0]->newInstance();
            $record = $inst->record;
            $mode = $inst->mode;
            $attributeFound = true;
        }

        if (false === $attributeFound) {
            return false;
        }

        return [$record, $mode];
    }
}
