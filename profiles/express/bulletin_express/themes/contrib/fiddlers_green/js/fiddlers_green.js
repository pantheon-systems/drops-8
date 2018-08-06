// Change 'node' to 'form' before sending to UCB-bulletin

(function ($) {
  if ((location.pathname.indexOf('form') > -1) && !($(".user-logged-in")[0])) {
    jQuery('.tabs--primary').hide();
  }
  $('.path-frontpage #edit-field-bulletin-category-target-id').attr('size', '18');
}(jQuery));