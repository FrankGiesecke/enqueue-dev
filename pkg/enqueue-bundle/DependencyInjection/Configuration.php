<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Symfony\MissingComponentFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('enqueue');

        $rootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->arrayPrototype()
                ->children()
                    ->append(TransportFactory::getConfiguration())
                    ->append(TransportFactory::getQueueConsumerConfiguration())
                    ->append(ClientFactory::getConfiguration($this->debug))
                    ->append($this->getMonitoringConfiguration())
                    ->append($this->getAsyncCommandsConfiguration())
                    ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                        ->booleanNode('doctrine_ping_connection_extension')->defaultFalse()->end()
                        ->booleanNode('doctrine_clear_identity_map_extension')->defaultFalse()->end()
                        ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
                        ->booleanNode('reply_extension')->defaultTrue()->end()
                    ->end()->end()
                ->end()
            ->end()
        ;

//        $rootNode->children()
//            ->booleanNode('job')->defaultFalse()->end()
//            ->arrayNode('async_events')
//                ->addDefaultsIfNotSet()
//                ->canBeEnabled()
//            ->end()
//        ;

        return $tb;
    }

    private function getMonitoringConfiguration(): ArrayNodeDefinition
    {
        if (false === class_exists(MonitoringFactory::class)) {
            return MissingComponentFactory::getConfiguration('monitoring', ['enqueue/monitoring']);
        }

        return MonitoringFactory::getConfiguration();
    }

    private function getAsyncCommandsConfiguration(): ArrayNodeDefinition
    {
        if (false === class_exists(RunCommandProcessor::class)) {
            return MissingComponentFactory::getConfiguration('async_commands', ['enqueue/async-command']);
        }

        return (new ArrayNodeDefinition('async_commands'))
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
        ;
    }
}
