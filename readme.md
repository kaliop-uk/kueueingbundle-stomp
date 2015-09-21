# Kaliop Queueing Bundle - STOMP plugin

Adds support for the STOMP protocol to the Kaliop Queueing Bundle

STOMP is a protocol used by multiple messaging brokers, such as ActiveMQ, Apache Apollo and RabbitMQ (but we suggest you
use AMQP to communicate with RabbitMQ, which is supported natively by the Queueing Bundle) 

See: https://stomp.github.io and https://github.com/kaliop-uk/kueueingbundle respectively for details.


## Installation

1. Install the bundle via Composer.

2. Enable the KaliopQueueingPluginsStompBundle bundle in your kernel class registerBundles().

3. Clear all caches if not on a dev environment


## Usage

4. Start the messaging broker of your choice 

5. Create a queue, using the appropriate management console:
    
    * Apollo
    
    * ActiveMQ
    
    * Stomp

6. Set up configuration according to your AWS account

    - copy queueingbundle_stomp_sample.yml in this bundle to your app/config folder, make sure you require it, and edit it

7. check that you can list the queue:

        php app/console kaliop_queueing:managequeue list -istomp

        php app/console kaliop_queueing:managequeue info -istomp <queue>

8. push a message to the queue

        php app/console kaliop_queueing:queuemessage -istomp <queue> <jsonpayload>

9. receive messages from the queue

        php app/console kaliop_queueing:consumer -istomp <queue>


## Notes

* Stomp does *not* natively support routing-keys the way that RabbitMQ does.
    This bundle *does* add back support for routing-keys, but the way those are handled differs with each broker.


[![License](https://poser.pugx.org/kaliop/queueingbundle-stomp/license)](https://packagist.org/packages/kaliop/queueingbundle-stomp)
[![Latest Stable Version](https://poser.pugx.org/kaliop/queueingbundle-stomp/v/stable)](https://packagist.org/packages/kaliop/queueingbundle-stomp)
[![Total Downloads](https://poser.pugx.org/kaliop/queueingbundle-stomp/downloads)](https://packagist.org/packages/kaliop/queueingbundle-stomp)

[![Build Status](https://travis-ci.org/kaliop-uk/queueingbundle-stomp.svg?branch=master)](https://travis-ci.org/kaliop-uk/queueingbundle-stomp)
