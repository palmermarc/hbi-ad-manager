<?php
$space_desktop	= ( $settings->post_grid_count['desktop'] - 1 ) * $settings->post_spacing;
$space_tablet 	= ( $settings->post_grid_count['tablet'] - 1 ) * $settings->post_spacing;
$space_mobile 	= ( $settings->post_grid_count['mobile'] - 1 ) * $settings->post_spacing;
$speed          = !empty( $settings->transition_speed ) ? $settings->transition_speed * 100 : '200';
$page_arg	 	= is_front_page() ? 'page' : 'paged';
$paged 			= get_query_var( $page_arg, 1 );
?>
var ppcg_<?php echo $id; ?> = '';

;(function($) {

	var DFPContentGridOptions = {
		id: '<?php echo $id ?>',
		layout: '<?php echo $settings->layout; ?>',
		ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
		perPage: '<?php echo $settings->posts_per_page; ?>',
		fields: <?php echo json_encode($settings); ?>,
		pagination: '<?php echo $settings->pagination; ?>',
		current_page: '<?php echo home_url($_SERVER['REQUEST_URI']); ?>',
		page: '<?php echo $paged; ?>',
		postSpacing: '<?php echo $settings->post_spacing; ?>',
		postColumns: {
			desktop: <?php echo $settings->post_grid_count['desktop']; ?>,
			tablet: <?php echo $settings->post_grid_count['tablet']; ?>,
			mobile: <?php echo $settings->post_grid_count['mobile']; ?>,
		},
		matchHeight: '<?php echo $settings->match_height; ?>',
		<?php echo (isset($settings->post_grid_filters_display) && 'yes' == $settings->post_grid_filters_display) ? 'filters: true' : 'filters: false'; ?>,
		<?php if ( 'none' != $settings->post_grid_filters ) { ?>
        	filterTax: '<?php echo $settings->post_grid_filters; ?>',
        	filterType: '<?php echo $settings->post_grid_filters_type; ?>',
		<?php } ?>
		<?php if ('grid' == $settings->layout && 'no' == $settings->match_height ) { ?>
		masonry: 'yes',
		<?php } ?>
		<?php if ( 'carousel' == $settings->layout ) { ?>
			carousel: {
				items: <?php echo $settings->post_grid_count['desktop']; ?>,
				itemsDesktop : [1199,<?php echo $settings->post_grid_count['desktop']; ?>],
				itemsDesktopSmall : [980,<?php echo $settings->post_grid_count['desktop']; ?>],
				itemsTablet: [768,<?php echo $settings->post_grid_count['tablet']; ?>],
  				itemsMobile : [479,<?php echo $settings->post_grid_count['mobile']; ?>],
	        <?php if( isset( $settings->slider_pagination ) && $settings->slider_pagination == 'no' ): ?>
		        pagination: false,
		    <?php endif; ?>
	        <?php if( isset( $settings->auto_play ) ): ?>
		        <?php echo 'yes' == $settings->auto_play ? 'autoPlay: true' : 'autoPlay: false'; ?>,
		    <?php endif; ?>
		        slideSpeed: <?php echo $speed ?>,
		    	<?php echo 'yes' == $settings->slider_navigation ? 'navigation: true' : 'navigation: false'; ?>,
		    	<?php echo ($settings->stop_on_hover == 'yes') ? 'stopOnHover: true' : 'stopOnHover: false'; ?>,
				<?php echo ($settings->lazy_load == 'yes') ? 'lazyLoad: true' : 'lazyLoad: false'; ?>,
				navigationText : ['<i class="fa fa-angle-left"></i>','<i class="fa fa-angle-right"></i>'],
				responsive: true,
			    responsiveRefreshRate : 200,
			    responsiveBaseWidth: window,
				rewindNav : true,
			}
		<?php } ?>
	};

	<?php if ( isset( $_GET['orderby'] ) && ! empty( $_GET['orderby'] ) ) { ?>
    DFPContentGridOptions.orderby = '<?php echo (string)$_GET['orderby']; ?>';
    <?php } ?>

	ppcg_<?php echo $id; ?> = new DFPContentGrid( DFPContentGridOptions );

})(jQuery);