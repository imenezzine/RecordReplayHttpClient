<?php

namespace Symfony\HttpClientRecorderBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\HttpClientRecorderBundle\Har\HarFileFactory;
use Symfony\HttpClientRecorderBundle\HttpClient\RecorderHttpClient;

use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

final class HttpClientRecorderBundle extends AbstractBundle implements CompilerPassInterface
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->scalarNode('records_path')
                    ->defaultValue('%kernel.project_dir%/tests/fixtures/records')
                ->end()
            ->end()
        ;
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        if (!$config['enabled']) {
            return;
        }

        $container->parameters()->set('http_client.recorder.records_path', $config['records_path']);
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

        $container->register('http_client.recorder.factory', HarFileFactory::class);

        foreach ($container->findTaggedServiceIds('http_client.client') as $serviceId => $attributes) {
            $container
                ->register("$serviceId.recorder", RecorderHttpClient::class)
                ->setDecoratedService($serviceId)
                ->setArguments([
                    new Reference('http_client.recorder.inner'),
                    new Reference('http_client.recorder.factory'),
                    new Parameter('http_client.recorder.records_path'),
                ])
                ->addTag('http_client.client')
            ;
        }
    }
}
