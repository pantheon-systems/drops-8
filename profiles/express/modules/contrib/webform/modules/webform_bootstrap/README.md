Webform Bootstrap
-----------------

### About this Module

The Webform Bootstrap module help integrate Webform with the Bootstrap base theme.

### Code Snippets

Below are Bootstrap specific classes that can be added to Webform settings. (/admin/structure/webform/config)

**Container classes**

Use for 'Form CSS classes', 'Wrapper CSS classes', 'Element CSS classes', and 'Confirmation CSS classes'.

```
container-inline clearfix
form--inline clearfix
well
well well-sm
well well-lg
alert alert-warning
alert alert-danger
alert alert-success
alert alert-info
alert-dismissible';
```

**Button classes**

Use for 'Button CSS classes' and 'Confirmation back link CSS classes'.

```
btn
btn btn-default
btn btn-primary
btn btn-success
btn btn-info
btn btn-warning
btn btn-danger
btn btn-link
btn-xs
btn-sm
btn-lg
```

### Known Issues

- ```\#description_display: tooltip``` uses Bootstrap tooltip instead of jQuery UI tooltip.

- Color element is not laying out correctly.

- Drupal button--* classes need to be converted Bootstrap btn-* classes.

- Bootstrap converts a.btn to button.btn and loses ajax callbacks.

- Dropdown buttons with AJAX are not working.

- [Issue #2850830: Cannot pass dialog options to modals when using bootstrap theme.](https://www.drupal.org/node/2850830)
