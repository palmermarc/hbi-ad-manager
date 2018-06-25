;(function($){

	FLBuilder.registerModuleHelper('dfp-content-grid', {

		/**
         * The 'init' method is called by the builder when
         * the settings form is opened.
         *
         * @method init
         */
        init: function()
        {
			//$('.fl-builder-pp-content-grid-settings select').trigger('change');

			if( $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'product' || $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'download' ) {
                $('#fl-builder-settings-section-product-settings').show();
                $('#fl-field-more_link_text').hide();
                if ( $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'download' ) {
                    $('#fl-field-product_rating, #fl-field-product_rating_color').hide();
                }
		   	}

			$('#fl-builder-settings-section-general select[name="post_type"]').on('change', function() {
				if( $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'product' || $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'download' ) {
                    $('#fl-builder-settings-section-product-settings').show();
                    $('#fl-field-more_link_text').hide();
                    if ( $('#fl-builder-settings-section-general select[name="post_type"]').val() == 'download' ) {
                        $('#fl-field-product_rating, #fl-field-product_rating_color').hide();
                    }
			   	} else {
				   $('#fl-builder-settings-section-product-settings').hide();
                   $('#fl-field-more_link_text').show();
			   	}
			});

			$('#fl-builder-settings-section-general select[name="post_type"]').trigger('change');

        },

	});

})(jQuery);
