/**
 * @file
 * Linkit plugin.
 *
 * @ignore
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  // Alter the dialog settings to make a bigger dialog.
  // $(window).on('dialog:beforecreate', function (event, dialog, $element, settings) {
  //  settings.dialogClass = settings.dialogClass.replace('ui-dialog--narrow', '');
  //  settings.width = 700;
  // });

  CKEDITOR.plugins.add('linkit', {
    init: function (editor) {
      // Add the commands for link and unlink.
      editor.addCommand('linkit', {
        allowedContent: new CKEDITOR.style({
          element: 'a',
          attributes: {
            '!href': '',
            // @TODO: Read these dynamically from the profile.
            'accesskey': '',
            'id': '',
            'rel': '',
            'target': '',
            'title': ''
          }
        }),
        requiredContent: new CKEDITOR.style({
          element: 'a',
          attributes: {
            href: ''
          }
        }),
        modes: {wysiwyg: 1},
        canUndo: true,
        exec: function (editor) {
          var linkElement = getSelectedLink(editor);
          var linkDOMElement = null;

          // Set existing values based on selected element.
          var existingValues = {};
          if (linkElement && linkElement.$) {
            linkDOMElement = linkElement.$;

            // Populate an array with the link's current attributes.
            var attribute = null;
            var attributeName;
            for (var attrIndex = 0; attrIndex < linkDOMElement.attributes.length; attrIndex++) {
              attribute = linkDOMElement.attributes.item(attrIndex);
              attributeName = attribute.nodeName.toLowerCase();
              // Don't consider data-cke-saved- attributes; they're just there
              // to work around browser quirks.
              if (attributeName.substring(0, 15) === 'data-cke-saved-') {
                continue;
              }
              // Store the value for this attribute, unless there's a
              // data-cke-saved- alternative for it, which will contain the
              // quirk-free, original value.
              existingValues[attributeName] = linkElement.data('cke-saved-' + attributeName) || attribute.nodeValue;
            }
          }

          // Prepare a save callback to be used upon saving the dialog.
          var saveCallback = function (returnValues) {
            editor.fire('saveSnapshot');

            // Create a new link element if needed.
            if (!linkElement && returnValues.attributes.href) {
              var selection = editor.getSelection();
              var range = selection.getRanges(1)[0];

              // Use link URL as text with a collapsed cursor.
              if (range.collapsed) {
                // Shorten mailto URLs to just the email address.
                var text = new CKEDITOR.dom.text(returnValues.attributes.href.replace(/^mailto:/, ''), editor.document);
                range.insertNode(text);
                range.selectNodeContents(text);
              }

              // Create the new link by applying a style to the new text.
              var style = new CKEDITOR.style({element: 'a', attributes: returnValues.attributes});
              style.type = CKEDITOR.STYLE_INLINE;
              style.applyToRange(range);
              range.select();

              // Set the link so individual properties may be set below.
              linkElement = getSelectedLink(editor);
            }
            // Update the link properties.
            else if (linkElement) {
              for (var attrName in returnValues.attributes) {
                if (returnValues.attributes.hasOwnProperty(attrName)) {
                  // Update the property if a value is specified.
                  if (returnValues.attributes[attrName].length > 0) {
                    var value = returnValues.attributes[attrName];
                    linkElement.data('cke-saved-' + attrName, value);
                    linkElement.setAttribute(attrName, value);
                  }
                  // Delete the property if set to an empty string.
                  else {
                    linkElement.removeAttribute(attrName);
                  }
                }
              }
            }

            // Save snapshot for undo support.
            editor.fire('saveSnapshot');
          };
          // Drupal.t() will not work inside CKEditor plugins because CKEditor
          // loads the JavaScript file instead of Drupal. Pull translated
          // strings from the plugin settings that are translated server-side.
          var dialogSettings = {
            title: linkElement ? editor.config.linkit_dialogTitleAdd : editor.config.linkit_dialogTitleEdit,
            dialogClass: 'editor-linkit-dialog'
          };

          // Open the dialog for the edit form.
          Drupal.ckeditor.openDialog(editor, Drupal.url('linkit/dialog/linkit/' + editor.config.drupal.format), existingValues, saveCallback, dialogSettings);
        }
      });

      // CTRL + L.
      editor.setKeystroke(CKEDITOR.CTRL + 76, 'linkit');

      // Add buttons.
      if (editor.ui.addButton) {
        editor.ui.addButton('Linkit', {
          label: Drupal.t('Link'),
          command: 'linkit',
          icon: this.path + '/linkit.png'
        });
      }

      editor.on('doubleclick', function (evt) {
        var element = getSelectedLink(editor) || evt.data.element;

        if (!element.isReadOnly()) {
          if (element.is('a')) {
            editor.getSelection().selectElement(element);
            editor.getCommand('linkit').exec();
          }
        }
      });

      // If the "menu" plugin is loaded, register the menu items.
      if (editor.addMenuItems) {
        editor.addMenuItems({
          linkit: {
            label: Drupal.t('Edit Link'),
            command: 'linkit',
            group: 'link',
            order: 1
          }
        });
      }

      // If the "contextmenu" plugin is loaded, register the listeners.
      if (editor.contextMenu) {
        editor.contextMenu.addListener(function (element, selection) {
          if (!element || element.isReadOnly()) {
            return null;
          }
          var anchor = getSelectedLink(editor);
          if (!anchor) {
            return null;
          }

          var menu = {};
          if (anchor.getAttribute('href') && anchor.getChildCount()) {
            menu = {
              linkit: CKEDITOR.TRISTATE_OFF
            };
          }
          return menu;
        });
      }
    }
  });

  /**
   * Get the surrounding link element of current selection.
   *
   * The following selection will all return the link element.
   *
   * @example
   *  <a href="#">li^nk</a>
   *  <a href="#">[link]</a>
   *  text[<a href="#">link]</a>
   *  <a href="#">li[nk</a>]
   *  [<b><a href="#">li]nk</a></b>]
   *  [<a href="#"><b>li]nk</b></a>
   *
   * @param {CKEDITOR.editor} editor
   *   The CKEditor editor object
   *
   * @return {?HTMLElement}
   *   The selected link element, or null.
   *
   */
  function getSelectedLink(editor) {
    var selection = editor.getSelection();
    var selectedElement = selection.getSelectedElement();
    if (selectedElement && selectedElement.is('a')) {
      return selectedElement;
    }

    var range = selection.getRanges(true)[0];

    if (range) {
      range.shrink(CKEDITOR.SHRINK_TEXT);
      return editor.elementPath(range.getCommonAncestor()).contains('a', 1);
    }
    return null;
  }

})(jQuery, Drupal, drupalSettings, CKEDITOR);
