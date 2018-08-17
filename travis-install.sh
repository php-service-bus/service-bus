#!/usr/bin/env bash

sudo apt-get install librabbitmq-dev -y;
sudo pecl install amqp
sudo echo "extension=amqp.so" >> "$(php -r 'echo php_ini_loaded_file();')";
