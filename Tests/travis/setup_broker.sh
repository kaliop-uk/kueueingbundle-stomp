#!/bin/sh

# Sets up STOMP brokers
# - listening on port 61613
# - with a user account: admin/password
#
# Tailored for Ubuntu 16

case "$1" in

activemq)
    sudo apt-get install -y activemq
    sudo cp ./Tests/travis/activemq.xml /etc/activemq/instances-available/main/activemq.xml
    sudo ln -s /etc/activemq/instances-available/main /etc/activemq/instances-enabled/
    sudo service activemq restart
    ;;

apollo)
    # NB: Apollo is 'dead' since march 2019...
    wget http://archive.apache.org/dist/activemq/activemq-apollo/1.7.1/apache-apollo-1.7.1-unix-distro.tar.gz
    tar -zxvf apache-apollo-1.7.1-unix-distro.tar.gz
    cd apache-apollo-1.7.1
    ./bin/apollo create testbroker
    echo -e "\nguest=guest" >> testbroker/etc/users.properties
    echo -e "\nusers=guest" >> testbroker/etc/groups.properties
    ./testbroker/bin/apollo-broker run &
    ;;

artemis)
    wget https://archive.apache.org/dist/activemq/activemq-artemis/2.10.1/apache-artemis-2.10.1-bin.tar.gz
    tar -zxvf apache-artemis-2.10.1-bin.tar.gz
    cd apache-artemis-2.10.1
    ./bin/artemis create test-broker --user=guest --password=guest --require-login
    ./test-broker/bin/artemis run &
    ;;

rabbitmq)
    sudo apt-get install -y rabbitmq-server
    sudo cp ./Tests/travis/rabbitmq.conf /etc/rabbitmq/rabbitmq.conf
    sudo service rabbitmq-server restart
    sudo rabbitmq-plugins enable rabbitmq_stomp
    ;;

*)
    echo unknown broker: $1
    ;;
esac
