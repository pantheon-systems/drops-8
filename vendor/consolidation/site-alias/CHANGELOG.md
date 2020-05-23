# Changelog

### 3.0.0 2019-03-12

* Make self.site.yml more discoverable. (#33)
* Add interfaces for the Site Alias Manager (#34)
* Rename AliasRecord to SiteAlias for consistency (#32)
* Add SiteAliasWithConfig class (#31)

### 2.0.0 2018-11-01

* Throw when root() is called with no root

### 1.1.7 - 1.1.9 - 2018/Oct/30

* Fixes #11: Prevent calls to 'localRoot' from failing when there is no root set (#15)
* Set short description in composer.json

### 1.1.6 - 2018/Oct/27

* Add an 'os' method to AliasRecord
* Only run root through realpath if it is present (throw otherwise) (#11)
* Add a site:value command for ad-hoc testing

### 1.1.3 - 1.1.5 - 2018/Sept/21

* Experimental wildcard environments
* Find 'aliases.drushrc.php' files when converting aliases.
* Fix get multiple (#6)

### 1.1.2 - 2018/Aug/21

* Allow SiteAliasFileLoader::loadMultiple to be filtered by location. (#3)

### 1.1.0 + 1.1.1 - 2018/Aug/14

* Add wildcard site alias environments. (#2)
* Remove legacy AliasRecord definition; causes more problems than it solves.

### 1.0.1 - 2018/Aug/7

* Allow addSearchLocation to take an array

### 1.0.0 - 2018/July/5

* Initial release

