## About Media entity

[![Travis](https://img.shields.io/travis/drupal-media/media_entity.svg)]() [![Scrutinizer](https://img.shields.io/scrutinizer/g/drupal-media/media_entity.svg)]()

Media entity provides a 'base' entity for media. This is a very basic entity
which can reference to all kinds of media-objects (local files, YouTube
videos, Tweets, Instagram photos, ...). Media entity provides a relation between
Drupal and the media resource. You can reference to/use this entity within any
other Drupal entity.

This module attempts to provide the base storage component for the Drupal 8
media ecosystem.

Project page: https://drupal.org/project/media_entity

## Official documentation

You will find all the documentation about this module on the [official handbook](https://drupal-media.gitbooks.io/drupal8-guide/content/modules/media_entity/intro.html).

## Contribute

Our current development focus can be seen in [the roadmap issue](https://www.drupal.org/node/2577453).

Development is generally done via [GitHub pull requests](https://github.com/drupal-media/media_entity/pulls).
Every pull request should be linked to an [issue in drupal.org issue queue](http://drupal.org/project/issues/media_entity)
and vice-versa. 

If you prefer usual patch-based workflow feel free to submit a patch. We started
using GitHub mostly for easier review process. However, there are not strong opinions
about that. Any contribution in any shape or form will be treated equally.

## Media provider modules

There are already several media provider modules that extend functionality of
Media entity:

- [Image](https://drupal.org/project/media_entity_image)
- [Audio](https://drupal.org/project/media_entity_audio)
- [Slideshow](https://drupal.org/project/media_entity_slideshow)
- [Video embed field](https://drupal.org/project/video_embed_field)
- [Twitter](https://drupal.org/project/media_entity_twitter)
- [Instagram](https://drupal.org/project/media_entity_instagram)
- [Document](https://drupal.org/project/media_entity_document)
- [Slideshare](https://drupal.org/project/media_entity_slideshare)
- [Video (local)](https://drupal.org/project/media_entity_video)
- [Tumblr](https://drupal.org/project/media_entity_tumblr)
- [Facebook](https://drupal.org/project/media_entity_facebook)
- [Audio embed field (sandbox)](https://drupal.org/sandbox/vilepickle/2784301)

## Other modules that integrate with media entity

- [Entity browser](https://drupal.org/project/entity_browser): Provides entity browser
  widget that supports uploading [Media entity images](https://drupal.org/project/media_entity_image).
- [DropzoneJS](https://drupal.org/project/dropzonejs): Extends entity browser [image
  upload widget](https://drupal.org/project/media_entity_image) with [DropzoneJS
  upload library](http://www.dropzonejs.com).
- [Slick media](https://drupal.org/project/slick_media): Provides integration
between [Slick carousel](https://drupal.org/project/slick) and Media entity. Slick media allows richer slideshows/carousel
  with a mix of text, image and video.
- [Brightcove](https://github.com/dawehner/media_entity_brightcove)

## Maintainers
- Janez Urevc ([@slashrsm](https://github.com/slashrsm)) https://drupal.org/user/744628
- Primož Hmeljak ([@primsi](https://github.com/primsi)) https://drupal.org/user/282629
- Nguyễn Hải Nam (@jcisio) https://drupal.org/user/210762
- Boris Gordon (@boztek) https://drupal.org/user/134410

## Get in touch
- http://groups.drupal.org/media
- IRC: #drupal-media @ Freenode
