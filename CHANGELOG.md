# CHANGELOG

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/) and [Keep a CHANGELOG](http://keepachangelog.com).

## [0.2.0] - 2019-06-10

### Added

* New connection collection `Cluster` to represent a cluster of fastCGI servers
* All connection collections implement the `Countable` interface
* Ability to send ONE request to a cluster of fastCGI servers and get their responses through new class `ClusterProxy` - [#4]
* Ability to get the status of a cluster of php-fpm servers through `ClusterProxy#getStatus()` - [#4]

### Changed

* Raised minimum version requirement of [hollodotme/fast-cgi-client](https://github.com/hollodotme/fast-cgi-client) to `v2.7.2`.

#### Backwards incompatible changes (BC breaks)

* Connection collections (`Random`, `RoundRobin`) are immutable, cannot be empty and must be 
  initialized using the named constructor `*::fromConnections()`. 
  
* Interface name for the connection collections `Random` and `RoundRobin` used in class `Proxy`:
  **Old:** `ProvidesClients`, **New:** `ProvidesNextClient`
  
### Improved

* Inline documentation
* Development setup (switched from vagrant to docker-compose)
* Test coverage
* CI builds on all supported PHP versions (7.1, 7.2, 7.3)

## [0.1.1] - 2018-05-10

### Fixed

* Counting an empty client collection throws a warning (in php>=7.2)

### Improved

* Import of root level functions and classes
* PHPDoc blocks
* CI builds now also running on php7.2

## [0.1.0] - 2017-11-26

Initial release.

[0.2.0]: https://github.com/hollodotme/fast-cgi-proxy/compare/v0.1.1...v0.2.0
[0.1.1]: https://github.com/hollodotme/fast-cgi-proxy/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/hollodotme/fast-cgi-proxy/tree/v0.1.0

[#4]: https://github.com/hollodotme/fast-cgi-proxy/issues/4