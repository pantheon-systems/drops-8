# Entity Browser Module

[![Build Status](https://travis-ci.org/drupal-media/entity_browser.svg?branch=8.x-1.x)](https://travis-ci.org/drupal-media/entity_browser) [![Scrutinizer](https://img.shields.io/scrutinizer/g/drupal-media/entity_browser.svg)](https://scrutinizer-ci.com/g/drupal-media/entity_browser)

Provides standardized interface to list, create and select entities.

## Requirements

* Latest dev release of Drupal 8.x.
* [Chaos tool set](https://drupal.org/project/ctools) (soft dependency for configuration UI)

## Configuration

Module ships with a simple configuration UI which allows you to create, edit
and delete entity browsers. It depends on
[Chaos tool set](https://drupal.org/project/ctools). Enable it and navigate to
/admin/config/content/entity_browser.

In order to use this configuration for testing or to help you contribute just 
enable "Entity Browser example" module (entity_browser_example).

## Technical details

Architecture details can be found on [architecture meta-issue.](https://www.drupal.org/node/2289821).

## Maintainers
 - Janez Urevc (@slashrsm) drupal.org/user/744628
 - Primož Hmeljak (@primsi) drupal.org/user/282629
