<?php

namespace Symfony\HttpClientRecorderBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\HttpClientRecorderBundle\HttpClient\RecorderHttpClient;
use Symfony\HttpClientRecorderBundle\Store\FilesystemStore;

final class HttpClientRecorderBundle extends AbstractBundle implements CompilerPassInterface
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end() // TODO: default to framework.test
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$config['enabled']) {
            return;
        }
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass($this);
    }

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasParameter('http_client.recorder.records_path')) {
            return;
        }

        $recordsPath = $container->getParameter('http_client.recorder.records_path');

        if (!$container->hasDefinition('http_client.recorder.store')) {
            $container->register('http_client.recorder.store', FilesystemStore::class)
                ->setArguments([$recordsPath, new Reference('filesystem')]);
        }

        foreach ($container->findTaggedServiceIds('http_client.client') as $serviceId => $attributes) {
            $container
                ->register("{$serviceId}.recorder", RecorderHttpClient::class)
                ->setDecoratedService($serviceId)
                ->setArguments([
                    new Reference("{$serviceId}.recorder.inner"),
                    new Reference('http_client.recorder.store'),
                ])
                ->addTag('http_client.client');
        }
    }
}
