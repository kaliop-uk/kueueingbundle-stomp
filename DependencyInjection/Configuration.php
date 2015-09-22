<?php

namespace Kaliop\Queueing\Plugins\StompBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $tree = new TreeBuilder();

        $rootNode = $tree->root('kaliop_queueing_plugins_stomp');

        $this->addConnections($rootNode);
        $this->addProducers($rootNode);
        $this->addConsumers($rootNode);

        return $tree;
    }

    protected function addConnections(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('connection')
            ->children()
                ->arrayNode('connections')
                    ->useAttributeAsKey('key')
                    ->canBeUnset()
                    ->prototype('array')
                        ->children()
                            ->scalarNode('connect_string')->isRequired()->end()
                            ->variableNode('credentials')
                                /// @todo: validate presence of user, password
                                //->children()
                                //->whatever...
                                //->end()
                            ->end()
                            ->booleanNode('debug')->defaultFalse()->end()
                            //->variableNode('http')
                                //->children()
                                    //->whatever...
                                //->end()
                            //->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addProducers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('producer')
            ->children()
                ->arrayNode('producers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getQueueConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            //->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function addConsumers(ArrayNodeDefinition $node)
    {
        $node
            ->fixXmlConfig('consumer')
            ->children()
                ->arrayNode('consumers')
                    ->canBeUnset()
                    ->useAttributeAsKey('key')
                    ->prototype('array')
                        ->append($this->getQueueConfiguration())
                        ->append($this->getSubscriptionConfiguration())
                        ->children()
                            ->scalarNode('connection')->defaultValue('default')->end()
                            ->scalarNode('callback')->isRequired()->end() // Q: could it be made optional?
                            //->scalarNode('auto_setup_fabric')->defaultTrue()->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;
    }

    protected function getQueueConfiguration()
    {
        $node = new ArrayNodeDefinition('queue_options');

        return $node
            ->children()
                ->scalarNode('name')->isRequired()->end()
            ->end()
        ;
    }

    protected function getSubscriptionConfiguration()
    {
        $node = new ArrayNodeDefinition('subscription_options');

        $this->addSubscriptionNodeConfiguration($node);

        return $node;
    }

    /**
     * @todo we use an array for routing keys, as RabbitMQ config does, but we probably only support one
     * @param ArrayNodeDefinition $node
     */
    protected function addSubscriptionNodeConfiguration(ArrayNodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('name')->isRequired()->end()
                ->arrayNode('routing_keys')
                    ->prototype('scalar')->end()
                    ->defaultValue(array())
                ->end()
            ->end()
        ;
    }
}
