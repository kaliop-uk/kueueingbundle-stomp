#!/bin/sh

case "$1" in
rabbitmq)
    sudo apt-get install -y rabbitmq-server
    ;;
apollo)
    # NB: Apollo is dead since march 2019...
    sudo apt-get install -y maven
    wget https://github.com/apache/activemq-apollo/archive/apollo-project-1.7.1.tar.gz
    tar -zxvf apollo-project-1.7.1.tar.gz
    cd apollo-project-1.7.1
    mvn install -P download
    cd apollo-distro/target
    ./bin/apollo create testbroker
    ./testbroker/bin/apollo-broker run &
    ;;
activemq)
    sudo apt-get install -y activemq
    sudo sed -i 's/<transportConnectors>/<transportConnectors><transportConnector name="stomp" uri="stomp:\/\/0.0.0.0:61613"\/>/g' /etc/activemq/instances-available/main/activemq.xml
    sudo ln -s /etc/activemq/instances-available/main /etc/activemq/instances-enabled/
    sudo service activemq restart
    ;;
artemis)
    wget https://archive.apache.org/dist/activemq/activemq-artemis/2.10.1/apache-artemis-2.10.1-bin.tar.gz
    tar -zxvf apache-artemis-2.10.1-bin.tar.gz
    cd apache-artemis-2.10.1
    ./bin/artemis create test-broker
    ./bin/artemis run &
    ;;
*)
    echo unknown broker: $1
    ;;
esac
