# Changelog

### 2.1.0 - 2019/Sep/10

* Added environment variables in aliases (#47)

### 2.0.4 - 2019/Aug/12

* Bugfix: Better error reporting when json output fails to parse. (#46)

### 2.0.3 - 2019/Jun/4

* Bugfix: Use posix_isatty when available. (#43)

### 2.0.2 - 2019/Apr/5

* When the transport is Docker, allow setting any docker-compose flags in the alias file Alexandru Szasz (#39)
* Added vagrant transport. Alexandru Szasz (#40)
* Added Util class to help detect TTY properly. Dane Powell (#41)

### 2.0.1 - 2019/Apr/2

* Do not format output in RealTimeOutput

### 2.0.0 - 2019/Mar/12

* Add a separaate 'addTransports' method for clients that wish to subclass the process manager (#32)
* Rename AliasRecord to SiteAlias;  Use SiteAliasWithConfig::create (#31)
* Use SiteAliasWithConfig (#30)
* Use ConfigAwareInterface/Trait (#26)
* Allow configuration to be injected into ProcessManager. (#22)
* setWorkingDirectory() controls remote execution dir (#25)

### 1.1.0 - 1.1.2 - 2019/Feb/13

* ms-slasher13 improve escaping on Windows (#24)

### 1.0.0 - 2019/Jan/17

* Initial release
