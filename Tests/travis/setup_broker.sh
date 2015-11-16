#!/bin/sh

case "$1" in
rabbitmq)
    # rabbitmq is already installed and started
    ;;
apollo)
    wget http://www.eu.apache.org/dist/activemq/activemq-apollo/1.7.1/apache-apollo-1.7.1-unix-distro.tar.gz
    tar -zxvf apache-apollo-1.7.1-unix-distro.tar.gz
    cd apache-apollo-1.7.1
    ./bin/apollo create testbroker
    ./testbroker/bin/apollo-broker run &
    ;;
activemq)
    sudo apt-get install activemq
    sudo sed -i '/<transportConnectors>/<transportConnectors><transportConnector name="stomp" uri="stomp://0.0.0.0:61613"\/>/g' /etc/activemq/instances-available/main/rabbitmq.xml
    sudo ln -s /etc/activemq/instances-available/main /etc/activemq/instances-enabled/
    sudo service activemq restart
    ;;
*)
    echo unknown broker: $1
    ;;
esac
