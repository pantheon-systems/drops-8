Description:
============

The ExternalAuth module provides a generic service for logging in and
registering users that are authenticated against an external site or service and
storing the authentication details.
It is the Drupal 8 equivalent of user_external_login_register() and related
functions, as well as the authmap table in Drupal 6 & 7 core.

Usage:
======

Install this module if it's required as dependency for an external
authentication Drupal module. Module authors that provide external
authentication methods can use this helper service to provide a consistent API
for storing and retrieving external authentication data.

Installation:
=============

Installation of this module is just like any other Drupal module.

1) Download the module
2) Uncompress it
3) Move it to the appropriate modules directory (usually, /modules)
4) Go to the Drupal module administration page for your site
5) Enable the module

Upgrading:
==========

The Drupal 8 version of this module provides Migrate functionality to upgrade
your Drupal 6 or Drupal 7 authmap entries to your Drupal 8 installation.

In order to upgrade the authmap table from your Drupal 6 or Drupal 7
installation, follow these instructions:
  - Install and enable the ExternalAuth module as described above.
  - Activate the Migrate and Migrate Drupal core modules and perform your
  upgrade migration.
  See https://www.drupal.org/upgrade/migrate for more information about this
  process.
