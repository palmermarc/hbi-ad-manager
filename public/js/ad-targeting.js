;(function ($) {

  $(document).on('hover', '#wp-admin-bar-dfp_ad_targeting', function(){
    console.log('they like me!');
    var bin = $(this).find('.ab-sub-wrapper');
    bin.css('min-width', '320px');
    bin.html('<ul></ul>');
    bin.find('ul').css('padding', '1em');
    for( var key in dfp_ad_targets )  {
      bin.find('ul').append('<li><strong>'+key+'</strong>: '+dfp_ad_targets[key]+'</li>');

      console.log(key);
    }
  });

})(jQuery);