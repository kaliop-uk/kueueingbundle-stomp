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
        $this->loadProducers();
        $this->loadConsumers();
    }

    protected function loadConnections()
    {
        // this is not so much a loading as a 'store definition for later access', really
        $definition = $this->container->findDefinition('kaliop_queueing.driver.stomp');
        foreach ($this->config['connections'] as $key => $def) {
            $definition->addMethodCall('registerConnection', array($key, $def));
        }
    }

    protected function loadProducers()
    {
        $qmDefinition = null;
        if ($this->container->hasDefinition($this->queueManagerService)) {
            $qmDefinition = $this->container->findDefinition($this->queueManagerService);
        }

        foreach ($this->config['producers'] as $key => $producer) {
            if (!isset($this->config['connections'][$producer['connection']])) {
                throw new \RuntimeException("Stomp producer '$key' can not use connection '{$producer['connection']}' because it is not defined in the connections section");
            }

            $connectionDefinition = $this->config['connections'][$producer['connection']];

            $pDefinition = new Definition('%kaliop_queueing.stomp.producer.class%', array($connectionDefinition));
            $pDefinition
                ->addMethodCall('setStompQueueName', array($producer['queue_options']['name']))
            ;
            $name = sprintf('kaliop_queueing.stomp.%s_producer', $key);
            $this->container->setDefinition($name, $pDefinition);


            //if (!$producer['auto_setup_fabric']) {
            //    $definition->addMethodCall('disableAutoSetupFabric');
            //}

            if ($qmDefinition) {
                $qmDefinition->addMethodCall('registerProducer', array($key));
            }
        }
    }

    protected function loadConsumers()
    {
        $qmDefinition = null;
        if ($this->container->hasDefinition($this->queueManagerService)) {
            $qmDefinition = $this->container->findDefinition($this->queueManagerService);
        }

        foreach ($this->config['consumers'] as $key => $consumer) {
            if (!isset($this->config['connections'][$consumer['connection']])) {
                throw new \RuntimeException("Stomp consumer '$key' can not use connection '{$consumer['connection']}' because it is not defined in the connections section");
            }

            $connectionDefinition = $this->config['connections'][$consumer['connection']];

            $cDefinition = new Definition('%kaliop_queueing.stomp.consumer.class%', array($connectionDefinition));
            $cDefinition
                ->addMethodCall('setSubscriptionName', array($consumer['subscription_options']['name']))
                ->addMethodCall('setStompQueueName', array($consumer['queue_options']['name']))
                ->addMethodCall('setCallback', array(new Reference($consumer['callback'])));
            ;
            if (count($consumer['subscription_options']['routing_keys'])) {
                $cDefinition->addMethodCall('setRoutingKey', array(reset($consumer['subscription_options']['routing_keys'])));
            }
            $name = sprintf('kaliop_queueing.stomp.%s_consumer', $key);
            $this->container->setDefinition($name, $cDefinition);

            //if (!$consumer['auto_setup_fabric']) {
            //    $definition->addMethodCall('disableAutoSetupFabric');
            //}

            if ($qmDefinition) {
                $qmDefinition->addMethodCall('registerConsumer', array($key));
            }
        }
    }
}
