# Crop module [![Build Status](https://travis-ci.org/drupal-media/crop.svg?branch=8.x-1.x)]
(https://travis-ci.org/drupal-media/crop)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/drupal-media/crop/badges/quality-score.png?b=8.x-1.x)]
(https://scrutinizer-ci.com/g/drupal-media/crop/?branch=8.x-1.x)

Provides basic API for image cropping. This module won't do much by itself. 
Users should pick one of UI modules that utilize this API.

## Requirements

* Latest dev release of Drupal 8.x.

## Configuration

There is one configuration which can be used to prevent crop from flushing image
derivatives upon save. See [Decoupled file storage](#decoupled-file-storage) for
details.

## Technical details

Initial discussion can be found on [manual crop issue queue].

[manual crop issue queue]: https://www.drupal.org/node/2368945

### Decoupled file storage  {#decoupled-file-storage}

By default whenever a crop entity is saved the image_styles associated with the
file entity are flushed so that Drupal will regenerate them using the updated
crop information the next time they are requested. If you are manually
generating your image derivatives instead of waiting for them to be generated on
the fly, because you are using a cloud storage service (like S3) you can use the
following code to tell not flush image_style automatically.

By default the `flush_derivative_images` configuration is set to `true`. To
prevent image derivatives from being flushed you can use the following code:
```PHP
$config['crop.settings']['flush_derivative_images'] = false;
```
