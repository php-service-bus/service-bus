# Changelog

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