jQuery(window).scroll(function() {
  if (jQuery(this).scrollTop() > 300) {
      jQuery('#express_back_to_top').fadeIn('slow');
  } else {
      jQuery('#express_back_to_top').fadeOut('slow');
  }
});
jQuery('#express_back_to_top a').click(function(){
  jQuery("html, body").animate({ scrollTop: 0 }, 600);
  jQuery("#page").focus();
  return false;
});
