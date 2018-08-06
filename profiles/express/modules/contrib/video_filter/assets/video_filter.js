/**
 * @file
 * Video Filter CKEditor plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  CKEDITOR.plugins.add( 'video_filter', {
    requires: 'widget',
    init: function ( editor ) {

      editor.addCommand( 'video_filter', {
        modes: { wysiwyg: 1 },
        canUndo: true,
        exec: function ( editor ) {

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function ( returnValues ) {

            // Save snapshot for undo support.
            editor.fire( 'saveSnapshot' );

            var embed = editor.document.createElement( 'p' );

            if ( returnValues.attributes.code !== undefined && returnValues.attributes.code !== '' ) {
              embed.setHtml( returnValues.attributes.code );
              editor.insertElement( embed );
            }

            // Save snapshot for undo support.
            editor.fire( 'saveSnapshot' );
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: editor.config.video_filter_dialog_title,
            dialogClass: 'video-filter-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog( editor, Drupal.url( 'video-filter/dialog/' + editor.config.drupal.format), {}, saveCallback, dialogSettings);
        }
      });

      // CTRL + M.
      editor.setKeystroke( CKEDITOR.CTRL + 77, 'video_filter' );

      // Add Video Filter button
      if (editor.ui.addButton) {
        editor.ui.addButton( 'video_filter', {
          label: Drupal.t('Video Filter'),
          command: 'video_filter',
          icon: this.path + 'icon.png'
        });
      }

    }
  });

})(jQuery, Drupal, drupalSettings, CKEDITOR);
