#!/bin/sh

# Sets up STOMP brokers
# - listening on port 61613
# - with a user account: guest/guest
#
# Tailored for Ubuntu 16 (Xenial) with a default JDK 11 installed

echo "Setting up broker: $1..."

case "$1" in

activemq)

    # Ubuntu package
    #
    # It does not seem to work on JRE 11 (xenial default, despite what the online docs say...), so we install version 8
    #sudo apt-get install -y openjdk-8-jdk-headless
    #sudo update-java-alternatives -v --jre-headless --set java-1.8.0-openjdk-amd64
    #echo "JAVA_HOME=/usr/lib/jvm/java-8-openjdk-amd64" | sudo tee -a /etc/environment
    #
    #sudo apt-get install -y activemq
    #sudo cp ./Tests/travis/activemq.xml /etc/activemq/instances-available/main/activemq.xml
    #sudo ln -s /etc/activemq/instances-available/main /etc/activemq/instances-enabled/
    #echo 'JAVA_HOME="/usr/lib/jvm/java-8-openjdk-amd64"' | sudo tee -a /usr/share/activemq/activemq-options
    #sudo service activemq restart
    #service activemq status

    # Alternative: latest version from tarball
    wget https://archive.apache.org/dist/activemq/5.15.9/apache-activemq-5.15.9-bin.tar.gz
    tar -zxvf apache-activemq-5.15.9-bin.tar.gz
    mv apache-activemq-5.15.9 apache-activemq
    cd apache-activemq
    # It seems that the shell scripts provided don't work well with Ubuntu, unless we force them
    # to use a separate user by usage of sudo...
    sudo ./bin/activemq create testbroker
    #echo "guest=guest" | sudo tee -a testbroker/conf/users.properties
    #echo "users=guest" | sudo tee -a testbroker/conf/groups.properties
    sudo mkdir -p testbroker/data
    sudo chmod -R o+w testbroker/data
    sudo ./testbroker/bin/testbroker start >testbroker/data/testbroker.log 2>&1 &
    ;;

apollo)
    # NB: Apollo is 'dead' since march 2019... the last available build is 1.7.1.
    # It does not work on JRE 11 (xenial default, despite what the online docs say...), so we install version 8
    sudo apt-get install -y openjdk-8-jdk-headless
    sudo update-java-alternatives -v --jre-headless --set java-1.8.0-openjdk-amd64
    echo "JAVA_HOME=/usr/lib/jvm/java-8-openjdk-amd64" | sudo tee -a /etc/environment

    wget http://archive.apache.org/dist/activemq/activemq-apollo/1.7.1/apache-apollo-1.7.1-unix-distro.tar.gz
    tar -zxvf apache-apollo-1.7.1-unix-distro.tar.gz
    mv apache-apollo-1.7.1 apache-apollo
    cd apache-apollo
    ./bin/apollo create testbroker
    echo -e "\nguest=guest" >> testbroker/etc/users.properties
    echo -e "\nusers=guest" >> testbroker/etc/groups.properties
    JAVA_HOME=/usr/lib/jvm/java-8-openjdk-amd64 ./testbroker/bin/apollo-broker-service start
    ;;

artemis)
    wget https://archive.apache.org/dist/activemq/activemq-artemis/2.10.1/apache-artemis-2.10.1-bin.tar.gz
    tar -zxvf apache-artemis-2.10.1-bin.tar.gz
    mv apache-artemis-2.10.1 apache-artemis
    cd apache-artemis
    ./bin/artemis create testbroker --user=guest --password=guest --require-login
    ./testbroker/bin/artemis-service start
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

# Give some time to the brokers for warming up...
sleep 5

echo "Setup: done"
