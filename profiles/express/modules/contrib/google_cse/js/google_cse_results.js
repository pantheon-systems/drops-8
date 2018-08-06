/**
 * @file
 * Google CSE JavaScript setup and invocation code.
 */

// Callback to grab search terms from the URL and feed them to Google.
window.__gcse = {
  callback: function () {
    'use strict';
    // Setting search query term as query string.
    var query = document.location.search.split('=')[1];
    if (query) {
      var gcse = google.search.cse.element.getElement('google_cse');
      if (gcse) {
        gcse.execute(decodeURIComponent(query));
      }
    }
  }
};

// The Google CSE standard code, just changed to pick up the SE if
// ("cx") from Drupal.settings.
(function () {
  'use strict';
  var cx = drupalSettings.googleCSE.cx;
  var gcse = document.createElement('script');
  gcse.type = 'text/javascript';
  gcse.async = true;
  gcse.src = (document.location.protocol === 'https:' ? 'https:' : 'http:') +
    '//www.google.com/cse/cse.js?cx=' + cx;
  var s = document.getElementsByTagName('script')[0];
  s.parentNode.insertBefore(gcse, s);
}
)();
