#!/usr/bin/env bash

git clone https://github.com/m6w6/ext-pq;
pushd ext-pq;
phpize;
./configure;
make;
make install;
popd;
echo "extension=pq.so" >> "$(php -r 'echo php_ini_loaded_file();')";
