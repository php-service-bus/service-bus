#!/usr/bin/env bash

echo "extension=amqp.so" >> "$(php -r 'echo php_ini_loaded_file();')";
