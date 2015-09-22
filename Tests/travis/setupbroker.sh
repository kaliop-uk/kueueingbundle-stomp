#!/bin/sh

case "$1" in
rabbitmq)
    # rabbitmq is already installed and started
    ;;
apollo)
    echo NOT YET SUPPORTED
    ;;
activemq)
    echo NOT YET SUPPORTED
    ;;
*)
    echo unknown broker: $1
    ;;
esac
