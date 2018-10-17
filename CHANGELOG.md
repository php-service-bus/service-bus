# Changelog

## 2.0.0
- *[Transport]* Fully redesigned transport level
- *[Storage]* Desperado\ServiceBus\Storage\ResultSet::getCurrent() now aow always returns an array or null
- *[Structure]* Everything related to working with the database is transferred to Desperado\ServiceBus\Infrastructure
- *[Message delivery]* Added endpoint support
- *[Initialization]* Initial application initialization changed
- *[Structure]* AnnotationsReader moved to Desperado\ServiceBus\Infrastructure

## 1.2.3 - 2018-10-08
- *[Optimization]* Added forced collection of links and freeing memory (1 time per hour)
- *[Improvement]* Added processing "2" signal (Now the subscription will be canceled, and the daemon will stop working after 10 seconds (by default))
- *[Structure]* ./docker directory moved to ./tests/docker 
- *[Optimization]* Optimized RAM usage


## 1.2.2 - 2018-10-05
- [Transport] The bug with the heartbeat timer has been fixed

## 1.2.1 - 2018-10-03
- *[Transport]* AMQP Qos options support added to Amqp client configuration (The default number of preload messages is 100)
- *[Transport]* Limited number of tasks running simultaneously. Now, no more than 50 (previously there was no limit)
- *[Optimization]* Fixed a memory leak in the transport level; Optimized RAM usage