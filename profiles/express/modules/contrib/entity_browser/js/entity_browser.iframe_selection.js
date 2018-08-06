/**
 * @file entity_browser.iframe_selection.js
 *
 * Propagates selected entities from iFrame display.
 */

(function (drupalSettings) {

  'use strict';

  // We need to access parent window, get it's jquery and find correct iFrame
  // element to trigger event on.
  parent.jQuery(parent.document)
    .find('iframe[data-uuid*=' + drupalSettings.entity_browser.iframe.uuid + ']').hide().prev().hide()
    .parent().find('a[data-uuid*=' + drupalSettings.entity_browser.iframe.uuid + ']')
    .trigger('entities-selected', [drupalSettings.entity_browser.iframe.uuid, drupalSettings.entity_browser.iframe.entities])
    .unbind('entities-selected').show();

}(drupalSettings));
