# Changelog

## 1.3.0

### Added
- ```Desperado\ServiceBus\Infrastructure\MessageSerialization\*``` (New unified interface for serializing messages)

### Removed
- TransactionAdapter::rollback() No longer throws exceptions
- ```Desperado\ServiceBus\Marshal\*``` and ```Desperado\ServiceBus\Transport\Marshal\*``` (Instead, now used Desperado\ServiceBus\Infrastructure\MessageSerialization\*)


### Moved
- ```Desperado\ServiceBus\Logger``` to ```Desperado\ServiceBus\Infrastructure\Logger```


## 1.2.3 - 2018-10-08

### Added
- Added forced collection of links and freeing memory (1 time per hour)
- Added processing "2" signal (Now the subscription will be canceled, and the daemon will stop working after 10 seconds (by default))

### Moved
- ./docker directory moved to ./tests/docker 

### Fixed
- Optimized RAM usage


## 1.2.2 - 2018-10-05

### Fixed
- The bug with the heartbeat timer has been fixed


## 1.2.1 - 2018-10-03

### Added
- AMQP Qos options support added to Amqp client configuration (The default number of preload messages is 100)
- Limited number of tasks running simultaneously. Now, no more than 50 (previously there was no limit)

### Deprecated
- Nothing

### Fixed
- Fixed a memory leak in the transport level
- Optimized RAM usage

### Removed
- Nothing

### Security
- Nothing