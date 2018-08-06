(function ($, Drupal, drupalSettings) {
  // At this point 'drupalSettings' is already available.
  try
  {
    //$('body').fitVids({});
    $(drupalSettings.fitvids.selectors).fitVids({
      customSelector: drupalSettings.fitvids.custom_vendors,
      ignore: drupalSettings.fitvids.ignore_selectors
    });
  }
  catch (e) {
    // catch any fitvids errors
    window.console && console.warn('Fitvids stopped with the following exception');
    window.console && console.error(e);
  }

})(jQuery, Drupal, drupalSettings);
