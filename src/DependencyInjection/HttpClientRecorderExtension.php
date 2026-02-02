<?php

namespace Symfony\HttpClientRecorderBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\HttpClientRecorderBundle\HttpClient\RecordReplayHttpClient;
use Symfony\HttpClientRecorderBundle\Enum\RecordReplayMode;

final class HttpClientRecorderExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        if (!$config['enabled']) {
            return;
        }

        $container
            ->register('http_client.recorder', RecordReplayHttpClient::class)
            ->setDecoratedService('http_client')
            ->addArgument(new Reference('http_client.recorder.inner'))
            ->addArgument($config['cassette_path'])
            ->addArgument(RecordReplayMode::from($config['mode']));
    }
}
