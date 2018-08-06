## About Media entity

Media entity provides a 'base' entity for a media element. This is a very basic
entity which can reference to all kinds of media-objects (local files, YouTube
videos, tweets, CDN-files, ...). This entity only provides a relation between
Drupal (because it is an entity) and the resource. You can reference to this
entity within any other Drupal entity.

## About Media entity image

This module provides local image integration for Media entity (i.e. media type
provider plugin). The user can map fields from image's Exif data to media bundle
fields.

### Storing field values

If you want to store field values you will have to map them to actual bundle
fields. At the momemnt there is no GUI for that, so the only method of doing
that for now is via CMI.

This whould be an example of that (the field_map section):

```
langcode: en
status: true
dependencies:
  module:
    - media_entity_image
id: photo
label: Photo
description: 'Photo to be used with content.'
type: image
type_configuration:
  source_field: field_image
field_map:
  mime: field_mime
  make: field_make
```

Project page: http://drupal.org/project/media_entity_image (if you can't find it, it will be created shortly).

Maintainers:
 - Janez Urevc (@slashrsm) drupal.org/user/744628
 - Primož Hmeljak (@primsi) drupal.org/user/282629

IRC channel: #drupal-media
