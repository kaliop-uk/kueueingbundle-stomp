<?php

namespace Kaliop\Queueing\Plugins\StompBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that loads and manages your bundle configuration
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 *
 * Logic heavily inspired by the way that Oldsound/RabbitMqBundle does things
 */
class KaliopQueueingPluginsStompExtension extends Extension
{
    protected $config = array();
    protected $container;
    protected $queueManagerService = 'kaliop_queueing.stomp.queue_manager';

    /**
     * {@inheritDoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $this->container = $container;

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $configuration = new Configuration();
        $this->config = $this->processConfiguration($configuration, $configs);

        $this->loadConnections();
        $this->loadQueues();
    }

    protected function loadConnections()
    {
        // this is not so much a loading as a 'store definition for later access', really
        $definition = $this->container->findDefinition('kaliop_queueing.driver.stomp');
        foreach ($this->config['connections'] as $key => $def) {
            $definition->addMethodCall('registerConnection', array($key, $def));
        }
    }

    protected function loadQueues()
    {
        $qmDefinition = null;
        if ($this->container->hasDefinition($this->queueManagerService)) {
            $qmDefinition = $this->container->findDefinition($this->queueManagerService);
        }

        foreach ($this->config['queues'] as $key => $consumer) {
            if (!isset($this->config['connections'][$consumer['connection']])) {
                throw new \RuntimeException("Stomp queue '$key' can not use connection '{$consumer['connection']}' because it is not defined in the connections section");
            }

            $connectionDefinition = $this->config['connections'][$consumer['connection']];

            $pDefinition = new Definition('%kaliop_queueing.stomp.producer.class%', array($connectionDefinition['connect_string']));
            $pDefinition
                ->addMethodCall('setCredentials', array($connectionDefinition['credentials']['user'], $connectionDefinition['credentials']['password']))
                ->addMethodCall('setQueueName', array($consumer['queue_options']['name']))
            ;
            $name = sprintf('kaliop_queueing.stomp.%s_producer', $key);
            $this->container->setDefinition($name, $pDefinition);

            $cDefinition = new Definition('%kaliop_queueing.stomp.consumer.class%', array($this->config['connections'][$consumer['connection']]['connect_string']));
            $cDefinition
                ->addMethodCall('setCredentials', array($connectionDefinition['credentials']['user'], $connectionDefinition['credentials']['password']))
                ->addMethodCall('setQueueName', array($consumer['queue_options']['name']))
                ->addMethodCall('setCallback', array(new Reference($consumer['callback'])));
            ;
            if (count($consumer['queue_options']['routing_keys'])) {
                $cDefinition->addMethodCall('setRoutingKey', array(reset($consumer['queue_options']['routing_keys'])));
            }
            $name = sprintf('kaliop_queueing.stomp.%s_consumer', $key);
            $this->container->setDefinition($name, $cDefinition);

            //if (!$consumer['auto_setup_fabric']) {
            //    $definition->addMethodCall('disableAutoSetupFabric');
            //}

            if ($qmDefinition) {
                $qmDefinition->addMethodCall('registerQueue', array($key));
            }
        }
    }
}
