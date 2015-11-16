# Kaliop Queueing Bundle - STOMP plugin

Adds support for the STOMP protocol to the Kaliop Queueing Bundle

STOMP is a protocol used by multiple messaging brokers, such as ActiveMQ, Apache Apollo and RabbitMQ (but we suggest you
use AMQP to communicate with RabbitMQ, which is supported natively by the Queueing Bundle)

Currently the bundle is tested using Apache Apollo and Apache ActiveMQ.

See: https://stomp.github.io and https://github.com/kaliop-uk/kueueingbundle respectively for more details.


## Installation

1. Install the bundle via Composer.

2. Enable the KaliopQueueingPluginsStompBundle bundle in your kernel class registerBundles().

3. Clear all caches if not on a dev environment


## Usage

4. Start the messaging broker of your choice

5. Create a queue, using the appropriate management console:

    * Apollo: : no need, queues and topics are created based on need

    * ActiveMQ: no need, queues and topics are created based on need

    * RabbitMQ:

6. Set up configuration according to your broker

    - copy queueingbundle_stomp_sample.yml in this bundle to your app/config folder, make sure you require it, and edit it

7. check that you can list the queue:

        php app/console kaliop_queueing:managequeue list-configured -istomp

   *not yet supported:* ask the broker for queue info

        php app/console kaliop_queueing:managequeue info -istomp <queue>

8. push a message to the queue

        php app/console kaliop_queueing:queuemessage -istomp <queue> <jsonpayload>

9. receive messages from the queue

        php app/console kaliop_queueing:consumer -istomp <queue>


## Notes

* Stomp does *not* natively support routing-keys the way that RabbitMQ does.
    Also, the implementation of Topic and Queue messaging patterns is left to the single brokers.

    This bundle *does* add back support for routing-keys. It also strives to replicate the same messaging pattern
    regardless of the broker in use. The way to achieve that differs with each broker.

    In particular:

    - for Apollo, topics with persistent subscriptions are used
    - for ActiveMQ, 'Virtual Topics' are used ( http://activemq.apache.org/virtual-destinations.html )
    - NB: ActiveMQ 5.5 seems to have a bug with wildcard support when using '#' as key. If this is a problem for you, please
      upgrade to a later version

    In the bundle configuration, the same wildcard characters are to be used regardless of teh broker in use: # and *

[![License](https://poser.pugx.org/kaliop/queueingbundle-stomp/license)](https://packagist.org/packages/kaliop/queueingbundle-stomp)
[![Latest Stable Version](https://poser.pugx.org/kaliop/queueingbundle-stomp/v/stable)](https://packagist.org/packages/kaliop/queueingbundle-stomp)
[![Total Downloads](https://poser.pugx.org/kaliop/queueingbundle-stomp/downloads)](https://packagist.org/packages/kaliop/queueingbundle-stomp)

[![Build Status](https://travis-ci.org/kaliop-uk/queueingbundle-stomp.svg?branch=master)](https://travis-ci.org/kaliop-uk/queueingbundle-stomp)
