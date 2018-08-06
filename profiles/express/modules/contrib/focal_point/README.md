##ABOUT

Focal Point allows you to specify the portion of an image that is most
important. This information can be used when the image is cropped or cropped and
scaled so that you don't, for example, end up with an image that cuts off the
subject's head.

This module borrows heavily from the ImageField Focus module but it works in a
fundamentally different way. In this module the focus is defined as a single
point on the image. Among other things this helps to solve the problem of
guaranteeing the size of a cropped image as described here:
https://drupal.org/node/1889542.

Currently, Focal Point integrates with the standard image fields.

##DEPENDENCIES

- image
- crop

##USAGE

### Setting up image fields

Install the module as usual. On the "Manage Form Display" page (e.g.
admin/structure/types/manage/article/form-display) choose the "Image (Focal
Point)" widget for your image field.

### Setting the focal point for an image

To set the focal point on an image, go to the content edit form (ex. the node
edit form) and upload an image. You will notice a crosshair in the middle of the
newly uploaded image. Drag this crosshair to the most important part of your
image. Done.

Pro tip: you can double-click the crosshair to see the exact coordinates (in
percentages) of the focal point.

### Cropping your image
The focal point module comes with two image effects:

1. focal point crop
2. focal point crop and scale

Both effects will make sure that the defined focal point is as close to the
center of your image as possible. It guarantees the focal point will be not be
cropped out of your image and that the image size will be the specified size.
