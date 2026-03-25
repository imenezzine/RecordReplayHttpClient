<?php

namespace Symfony\HttpClientRecorderBundle\PHPUnit;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

final class RecorderExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $defaultDirectory = null;

        if ($parameters->has('defaultDirectory')) {
            $defaultDirectory = \realpath($parameters->get('defaultDirectory'));
        }

        $defaultDirectory ??= \dirname($configuration->configurationFile()).'/tests/fixtures/records/';

        $facade->registerSubscriber(new RecorderSubscriber($defaultDirectory));
    }
}
