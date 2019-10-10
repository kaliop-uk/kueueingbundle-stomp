#!/bin/sh

# Sets up STOMP brokers
# - listening on port 61613
# - with a user account: admin/password
#
# Tailored for Ubuntu 16

echo "Setting up broker: $1..."

case "$1" in

activemq)
    # Ubuntu package
    sudo apt-get install -y activemq
    sudo cp ./Tests/travis/activemq.xml /etc/activemq/instances-available/main/activemq.xml
    sudo ln -s /etc/activemq/instances-available/main /etc/activemq/instances-enabled/
    sudo service activemq restart
    # Alternative: latest version as tarball
    #wget https://archive.apache.org/dist/activemq/5.15.9/apache-activemq-5.15.9-bin.tar.gz
    #tar -zxvf apache-activemq-5.15.9-bin.tar.gz
    #mv apache-activemq-5.15.9 apache-activemq
    #cd apache-activemq
    #bin/activemq start >/dev/null  2>&1 &
    ;;

apollo)
    # NB: Apollo is 'dead' since march 2019... the lat available build is 1.7.1.
    # It does not work on JRE 11, so we install version 8
    sudo apt-get install -y openjdk-8-jdk-headless
    sudo  update-java-alternatives -v --jre-headless --set java-1.8.0-openjdk-amd64
    wget http://archive.apache.org/dist/activemq/activemq-apollo/1.7.1/apache-apollo-1.7.1-unix-distro.tar.gz
    tar -zxvf apache-apollo-1.7.1-unix-distro.tar.gz
    mv apache-apollo-1.7.1 apache-apollo
    cd apache-apollo
    ./bin/apollo create testbroker
    echo -e "\nguest=guest" >> testbroker/etc/users.properties
    echo -e "\nusers=guest" >> testbroker/etc/groups.properties
    ./testbroker/bin/apollo-broker-service start
    ;;

artemis)
    wget https://archive.apache.org/dist/activemq/activemq-artemis/2.10.1/apache-artemis-2.10.1-bin.tar.gz
    tar -zxvf apache-artemis-2.10.1-bin.tar.gz
    mv apache-artemis-2.10.1 apache-artemis
    cd apache-artemis
    ./bin/artemis create test-broker --user=guest --password=guest --require-login
    ./test-broker/bin/artemis-service start
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

# give some time to the brokers for warming up...
sleep 5

echo "Setup: done"
