/**
 * @file
 * The video_embed_field colorbox integration.
 */

(function($) {
  Drupal.behaviors.video_embed_field_colorbox = {
    attach: function (context, settings) {
      $('.video-embed-field-launch-modal', context).once().click(function(e) {
        // Allow the thumbnail that launches the modal to link to other places
        // such as video URL, so if the modal is sidestepped things degrade
        // gracefully.
        e.preventDefault();
        $.colorbox($.extend(settings.colorbox, {'html': $(this).data('video-embed-field-modal')}));
      });
    }
  };
})(jQuery);
