<?php
$taxonomy = 'category';

if( $settings->post_type === 'photo-gallery' ) {
  $taxonomy = 'gallery-collection';
}

$categories = get_the_terms( get_the_ID(), $taxonomy );

?>
<div class="pp-content-grid-image pp-post-image">
    <?php if ( has_post_thumbnail() ) { ?>
        <?php //echo $module->pp_render_img( get_the_id(), $settings->image_thumb_crop ); ?>
        <div class="pp-post-featured-img">
		      <?php FLBuilder::render_module_html( 'photo', BB_PowerPack_Post_Helper::post_image_get_settings( get_the_ID(), $settings->image_thumb_crop, $settings ) ); ?>
		    </div>
        <div class="dfp-post-meta">
          <span class="dfp-post-meta-category"><?php echo ( 24 == $categories[0]->term_id ) ? 'Top Rock Countdown' : $categories[0]->name; ?></span>
          <span class="dfp-post-meta-date"><?php echo get_the_date(); ?></span>
        </div>
    <?php } else {
		$img_src = '';

		if ( isset( $settings->fallback_image ) && 'default' == $settings->fallback_image ) {
			$first_img = BB_PowerPack_Post_Helper::post_catch_image( get_the_content() );
        	$img_src = ( '' != $first_img ) ? $first_img : apply_filters( 'pp_cg_placeholder_img', $module_url .'/images/placeholder.jpg' );
		}
		if ( isset( $settings->fallback_image ) && 'custom' == $settings->fallback_image ) {
			$img_src = $settings->fallback_image_custom_src;
		}
        ?>
		<?php if ( ! empty( $img_src ) ) { ?>
			<div class="pp-post-featured-img">
				<img src="<?php echo $img_src; ?>" />
			</div>
		<?php } ?>
    <?php } ?>

    <?php if(($settings->show_categories == 'yes' && taxonomy_exists($settings->post_taxonomies) && !empty($terms_list)) && ('style-3' == $settings->post_grid_style_select) ) : ?>
        <?php include $module_dir . 'includes/templates/post-meta.php'; ?>
    <?php endif; ?>

    <?php if( 'style-4' == $settings->post_grid_style_select ) { ?>
        <<?php echo $settings->title_tag; ?> class="pp-content-grid-title pp-post-title" itemprop="headline">
            <?php if( $settings->more_link_type == 'button' || $settings->more_link_type == 'title' || $settings->more_link_type == 'title_thumb' ) { ?>
                <a href="<?php the_permalink(); ?>">
            <?php } ?>
                    <?php the_title(); ?>
            <?php if( $settings->more_link_type == 'button' || $settings->more_link_type == 'title' || $settings->more_link_type == 'title_thumb' ) { ?>
                </a>
            <?php } ?>
        </<?php echo $settings->title_tag; ?>>
    <?php } ?>

    <?php if('style-6' == $settings->post_grid_style_select && 'yes' == $settings->show_date) { ?>
    <div class="pp-content-post-date pp-post-meta">
        <span class="pp-post-month"><?php echo get_the_date('M'); ?></span>
        <span class="pp-post-day"><?php echo get_the_date('d'); ?></span>
    </div>
    <?php } ?>
</div>
