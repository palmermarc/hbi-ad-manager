(function ($) {
    'use strict';

    $(function () {

        var t = document.querySelector('#admap_row');

        $('#admap_size').on('change', function () {
            var new_depth = $(this).val();
            var current_depth = parseInt($('#admap_table tbody tr').length, 10) || 0;

            if (new_depth == current_depth) {
                return;
            }

            if (new_depth > current_depth) {
                for (current_depth; current_depth < new_depth; current_depth++) {
                    var clone = document.importNode(t.content, true);
                    $(clone).contents().find('.browser_width').attr('name', 'term_meta[admap_sizes]['+current_depth+'][browser_width]');
                    $(clone).contents().find('.browser_height').attr('name', 'term_meta[admap_sizes]['+current_depth+'][browser_height]');
                    $(clone).contents().find('.ad_width').attr('name', 'term_meta[admap_sizes]['+current_depth+'][ad_width]');
                    $(clone).contents().find('.ad_height').attr('name', 'term_meta[admap_sizes]['+current_depth+'][ad_height]');
                    $(clone).appendTo('#admap_table tbody');
                }
            } else {
                for (new_depth; new_depth < current_depth; current_depth--) {
                    $('#admap_table tbody tr:last').remove();
                }
            }
        });
        
        $(document).on('click touch tap', '#add_another_conditional',  function() {
        	var conditional_template = $('#ad-conditional-template').html();
        	$('#logical-operator').before(conditional_template);
        	if( $('.single-ad-unit-conditional').length > 0 && $('#logical-operator').hasClass('hide') ) {
        		 $('#logical-operator').removeClass('hide').addClass('show');
        		 
        	}
        });
        
        $(document).on('click touch tap', '.remove_ad_conditional', function() {
        	$(this).parent().remove();
        	if( $('#ads-conditional-bin .single-ad-unit-conditional').length == 0 && $('#logical-operator').hasClass('show') ) {
        		 $('#logical-operator').removeClass('show').addClass('hide');
        	}
        });
    });

})(jQuery);