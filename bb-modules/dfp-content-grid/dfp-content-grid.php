<?php

/**
 * @class DFPContentGridModule
 */
class DFPContentGridModule extends FLBuilderModule {

	/**
	 * @method __construct
	 */
	public function __construct()
	{
		parent::__construct(array(
			'name'          	=> __('DFP Content Grid', 'fl-builder'),
			'description'   	=> __('Display posts and pages in grid or carousel format.', 'fl-builder'),
      'category'			  => 'Advertising Modules',
      //'dir'           	=> BB_POWERPACK_DIR . 'modules/pp-content-grid/',
      //'url'           	=> BB_POWERPACK_URL . 'modules/pp-content-grid/',
			'partial_refresh'	=> true,
			'icon'				=> 'schedule.svg',
		));

		// add_action( 'wp_head', array( $this, 'post_ajax_filters' ) );
		add_action( 'wp_ajax_get_post_tax', array( $this, 'pp_get_post_taxonomies' ) );
		add_action( 'wp_ajax_nopriv_get_post_tax', array( $this, 'pp_get_post_taxonomies' ) );
		add_filter( 'fl_builder_loop_query_args', array( $this, 'exclude_current_post' ), 10, 1 );
	}

	/**
	 * @method enqueue_scripts
	 */
	public function enqueue_scripts()
	{
		$this->add_js( 'jquery-imagesloaded' );

		if(FLBuilderModel::is_builder_active() || $this->settings->layout == 'grid') {
			$this->add_js( 'jquery-isotope' );
		}

		if(FLBuilderModel::is_builder_active() || $this->settings->pagination == 'scroll') {
			$this->add_js( 'jquery-infinitescroll' );
		}
		if(FLBuilderModel::is_builder_active() || $this->settings->layout == 'carousel') {
			$this->add_css( 'font-awesome' );
			$this->add_css( 'owl-carousel' );
			$this->add_css( 'owl-carousel-theme' );
			$this->add_js( 'owl-carousel' );
		}

		// Jetpack sharing has settings to enable sharing on posts, post types and pages.
		// If pages are disabled then jetpack will still show the share button in this module
		// but will *not* enqueue its scripts and fonts.
		// This filter forces jetpack to enqueue the sharing scripts.
		add_filter( 'sharing_enqueue_scripts', '__return_true' );
	}

	/**
	 * @since 1.3.1
	 */
	public function update( $settings ) {
		global $wp_rewrite;
		$wp_rewrite->flush_rules( false );
		return $settings;
	}

	/**
	 * Renders the schema structured data for the current
	 * post in the loop.
	 *
	 * @since 1.7.4
	 * @return void
	 */
	static public function schema_meta()
	{
		// General Schema Meta
		echo '<meta itemscope itemprop="mainEntityOfPage" itemtype="https://schema.org/WebPage" itemid="' . esc_url( get_permalink() ) . '" content="' . the_title_attribute( array(
			'echo' => false,
		) ) . '" />';
		echo '<meta itemprop="datePublished" content="' . get_the_time('Y-m-d') . '" />';
		echo '<meta itemprop="dateModified" content="' . get_the_modified_date('Y-m-d') . '" />';

		// Publisher Schema Meta
		echo '<div itemprop="publisher" itemscope itemtype="https://schema.org/Organization">';
		echo '<meta itemprop="name" content="' . get_bloginfo( 'name' ) . '">';

		if ( class_exists( 'FLTheme' ) && 'image' == FLTheme::get_setting( 'fl-logo-type' ) ) {
			echo '<div itemprop="logo" itemscope itemtype="https://schema.org/ImageObject">';
			echo '<meta itemprop="url" content="' . FLTheme::get_setting( 'fl-logo-image' ) . '">';
			echo '</div>';
		}

		echo '</div>';

		// Author Schema Meta
		echo '<div itemscope itemprop="author" itemtype="http://schema.org/Person">';
		echo '<meta itemprop="url" content="' . get_author_posts_url( get_the_author_meta( 'ID' ) ) . '" />';
		echo '<meta itemprop="name" content="' . get_the_author_meta( 'display_name', get_the_author_meta( 'ID' ) ) . '" />';
		echo '</div>';

		// Image Schema Meta
		if(has_post_thumbnail()) {

			$image = wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'full');

			if ( is_array( $image ) ) {
				echo '<div itemscope itemprop="image" itemtype="http://schema.org/ImageObject">';
				echo '<meta itemprop="url" content="' . $image[0] . '" />';
				echo '<meta itemprop="width" content="' . $image[1] . '" />';
				echo '<meta itemprop="height" content="' . $image[2] . '" />';
				echo '</div>';
			}
		}

		// Comment Schema Meta
		echo '<div itemprop="interactionStatistic" itemscope itemtype="http://schema.org/InteractionCounter">';
		echo '<meta itemprop="interactionType" content="http://schema.org/CommentAction" />';
		echo '<meta itemprop="userInteractionCount" content="' . wp_count_comments(get_the_ID())->approved . '" />';
		echo '</div>';
	}

	/**
	 * Renders the schema itemtype for the current
	 * post in the loop.
	 *
	 * @since 1.7.4
	 * @return void
	 */
	static public function schema_itemtype()
	{
		global $post;

		if ( ! is_object( $post ) || ! isset( $post->post_type ) || 'post' != $post->post_type ) {
			echo 'http://schema.org/CreativeWork';
		}
		else {
			echo 'http://schema.org/BlogPosting';
		}
	}

	public function pp_get_settings() {
		return $this->settings;
	}

	/**
     * Get ajax taxonomies
     */
    public function pp_get_post_taxonomies() {
        $options = array( 'none' => __('None', 'bb-powerpack') );
		$slug = isset( $_POST['post_type_slug'] ) ? $_POST['post_type_slug'] : '';
		$taxonomies = FLBuilderLoop::taxonomies($slug);
		$html = '';
		$html .= '<option value="none">'. __('None', 'bb-powerpack') .'</option>';
		foreach($taxonomies as $tax_slug => $tax) {
			$html .= '<option value="'.$tax_slug.'">'.$tax->label.'</option>';
			$options[$tax_slug] = $tax->label;
		}

        echo $html; die();
	}

	public function exclude_current_post( $args ) {
		if ( ! isset( $args['settings'] ) ) {
			return $args;
		}

		$settings = $args['settings'];

		if ( ! isset( $settings->pp_content_grid ) || ! $settings->pp_content_grid ) {
			return $args;
		}

		if ( ! isset( $settings->exclude_current_post ) ) {
			return $args;
		}

		if ( ! isset( $settings->pp_post_id ) ) {
			return $args;
		}
		
		if ( ! isset( $args['post__not_in'] ) || ! is_array( $args['post__not_in'] ) ) {
			$args['post__not_in'] = array();
		}

		$args['post__not_in'][] = $settings->pp_post_id;

		return $args;
	}

  public static function get_ad_units() {
    $ad_units = HBI_Ad_Manager_Public::get_ad_units();
    $filtered_ad_units = array();

    foreach( $ad_units as $ad_unit ) {
      $ad_unit_id = $ad_unit['post_id'];
      $filtered_ad_units[ $ad_unit_id ] = __( $ad_unit['post_title'], 'fl-builder' );
    }

    return $filtered_ad_units;
  }

}

/**
 * Register the module and its form settings.
 */
FLBuilder::register_module('DFPContentGridModule', array(
	'layout'        => array(
		'title'         => __('Layout', 'bb-powerpack'),
		'sections'      => array(
			'layout_cg'       => array(
				'title'         => '',
				'fields'        => array(
					'layout'        => array(
						'type'          => 'pp-switch',
						'label'         => __('Layout Type', 'bb-powerpack'),
						'default'       => 'grid',
						'options'       => array(
							'grid'          => __('Grid', 'bb-powerpack'),
							'carousel'       => __('Carousel', 'bb-powerpack'),
						),
						'toggle'		=> array(
							'grid'			=> array(
								'fields'		=> array( 'match_height',
															'post_background',
															'post_text_color',
															'pagination',
															'show_author',
															'show_categories',
															'post_grid_style_select',
															'post_content_alignment',
															'post_grid_padding',
															'post_content_padding',
															'post_grid_filters_display'
														),
								'sections'		=> array('grid', 'meta-settings', 'pagination_style', 'post-content-settings'),
								'tabs'			=> array('filters_style', 'pagination')
							),
							'carousel'			=> array(
								'fields'		=> array( 'post_background',
															'post_text_color',
															'slide_width',
															'post_carousel_minimum',
															'post_carousel_maximum',
															'show_author',
															'show_categories',
															'post_grid_style_select',
															'post_content_alignment',
															'post_grid_padding',
															'post_content_padding',
														),
								'sections'		=> array('grid', 'meta-settings', 'post-content-settings'),
								'tabs'			=> array('slider')
							),
						),
						'hide'	=> array(
							'carousel'	=> array(
								'tabs'	=> array('filters_style')
							)
						)
					),
					'post_grid_style_select'    => array(
                        'type'      => 'select',
                        'label'     => __('Select Style', 'bb-powerpack'),
                        'default'   => 'default',
                        'options'   => array(
							'default'  => __('Default', 'bb-powerpack'),
                            'style-1'  => __('Style 1', 'bb-powerpack'),
                            'style-2'  => __('Style 2', 'bb-powerpack'),
							'style-3'  => __('Style 3', 'bb-powerpack'),
							'style-4'  => __('Style 4', 'bb-powerpack'),
							'style-5'  => __('Style 5', 'bb-powerpack'),
							'style-6'  => __('Style 6', 'bb-powerpack'),
							'style-7'  => __('Style 7', 'bb-powerpack'),
							'style-8'  => __('Style 8', 'bb-powerpack'),
                        ),
						'toggle'	=> array(
							'default'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories')
							),
							'style-1'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories')
							),
							'style-2'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories'),
								'sections'	=> array('divider_style')
							),
							'style-3'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories'),
								'sections'	=> array('post_category_style')
							),
							'style-4'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories'),
								'sections'	=> array('post_title_style')
							),
							'style-5'	=> array(
								'fields'	=> array('post_date_day_bg_color', 'show_categories', 'post_date_day_text_color', 'post_date_month_bg_color', 'post_date_month_text_color', 'post_date_border_radius'),
								'sections'	=> array('post_date_style')
							),
							'style-6'	=> array(
								'fields'	=> array('post_date_bg_color', 'post_date_text_color', 'show_categories'),
								'sections'	=> array('post_date_style')
							),
							'style-7'	=> array(
								'fields'	=> array('post_content_alignment', 'show_categories')
							)
						),
                    ),
					'match_height'  => array(
						'type'          => 'pp-switch',
						'label'         => __('Equal Heights', 'bb-powerpack'),
						'default'       => 'no',
						'options'       => array(
							'yes'          => __('Yes', 'bb-powerpack'),
							'no'         => __('No', 'bb-powerpack'),
						),
					),
					'total_post'  => array(
						'type'          => 'pp-switch',
						'label'         => __('Total Posts', 'bb-powerpack'),
						'default'       => 'all',
						'options'       => array(
							'all'          => __('All', 'bb-powerpack'),
							'custom'         => __('Custom', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'custom'	=> array(
								'fields'	=> array('total_posts_count')
							)
						)
					),
					'total_posts_count' => array(
						'type'          => 'text',
						'label'         => __('Posts Count', 'bb-powerpack'),
						'default'       => '20',
						'size'          => '4',
					),
					'posts_per_page' => array(
						'type'          => 'text',
						'label'         => __('Posts Per Page', 'bb-powerpack'),
						'default'       => '10',
						'size'          => '4',
						'help'			=> __('Number of posts to be displayed at once. Should be less than or equal to total post count.', 'bb-powerpack')
					),
					'pagination'     => array(
						'type'          => 'select',
						'label'         => __('Pagination', 'bb-powerpack'),
						'default'       => 'numbers',
						'options'       => array(
							'none'          => _x( 'None', 'Pagination style.', 'bb-powerpack' ),
							'numbers'       => __('Numbers', 'bb-powerpack'),
							'scroll'       	=> __('Scroll', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'numbers'	=> array(
								'sections'	=> array('pagination_style', 'pagination_typography'),
								'tabs'	=> array('pagination')
							)
						)
					),
					'exclude_current_post'	=> array(
						'type'					=> 'select',
						'label'					=> __('Exclude Current Post', 'bb-powerpack'),
						'default'				=> 'no',
						'options'				=> array(
							'yes'					=> __('Yes', 'bb-powerpack'),
							'no'					=> __('No', 'bb-powerpack')
						)
					)
				)
			),
			'grid'          => array(
				'title'         => __('Column Settings', 'bb-powerpack'),
				'fields'        => array(
					'post_grid_count'    => array(
						'type' 			=> 'pp-multitext',
						'label' 		=> __('Number of Columns', 'bb-powerpack'),
						'default'		=> array(
							'desktop'	=> 3,
							'tablet'	=> 2,
							'mobile'	=> 1,
						),
						'options' 		=> array(
							'desktop' => array(
								'icon'		=> 'fa-desktop',
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
							),
							'tablet' => array(
								'icon'		=> 'fa-tablet',
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
							),
							'mobile' => array(
								'icon'		=> 'fa-mobile',
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
							),
						),
					),
					'post_spacing'  => array(
						'type'          => 'text',
						'label'         => __('Column Spacing', 'bb-powerpack'),
						'default'       => '2',
						'maxlength'     => '3',
						'size'          => '4',
						'description'   => '%',
						'preview'   => array(
							'type'	=> 'css',
							'rules'		=> array(
								array(
									'selector'  => '.pp-content-grid-post',
		                            'property'  => 'margin-right',
		                            'unit'      => '%',
								),
								array(
									'selector'  => '.pp-content-grid-post',
		                            'property'  => 'margin-bottom',
		                            'unit'      => '%',
								),
								array(
									'selector'  => '.pp-content-carousel-post',
		                            'property'  => 'margin-right',
		                            'unit'      => '%',
								),
								array(
									'selector'  => '.pp-content-carousel-post',
		                            'property'  => 'margin-left',
		                            'unit'      => '%',
								)
							)
                        )
					),
				)
			),
		),
	),
	'slider'      => array(
		'title'         => __('Carousel', 'bb-powerpack'),
		'sections'      => array(
			'slider_general'       => array(
				'title'         => '',
				'fields'        => array(
					'auto_play'     => array(
						'type'          => 'pp-switch',
						'label'         => __('Auto Play', 'bb-powerpack'),
						'default'       => 'yes',
						'options'       => array(
							'yes'          => __('Yes', 'bb-powerpack'),
							'no'         => __('No', 'bb-powerpack'),
						)
					),
					'stop_on_hover'     => array(
						'type'          => 'pp-switch',
						'label'         => __('Stop On Hover', 'bb-powerpack'),
						'default'       => 'no',
						'options'       => array(
							'yes'          => __('Yes', 'bb-powerpack'),
							'no'         => __('No', 'bb-powerpack'),
						)
					),
					'lazy_load'     => array(
						'type'          => 'pp-switch',
						'label'         => __('Lazy Load', 'bb-powerpack'),
						'default'       => 'no',
						'options'       => array(
							'yes'          => __('Yes', 'bb-powerpack'),
							'no'         => __('No', 'bb-powerpack'),
						)
					),
					'transition_speed' => array(
						'type'          => 'text',
						'label'         => __('Transition Speed', 'bb-powerpack'),
						'default'       => '2',
						'size'          => '5',
						'description'   => _x( 'seconds', 'Value unit for form field of time in seconds. Such as: "5 seconds"', 'bb-powerpack' )
					),
				)
			),
			'controls'       => array(
				'title'         => __('Controls', 'bb-powerpack'),
				'fields'        => array(
					'slider_pagination'     => array(
						'type'          => 'pp-switch',
						'label'         => __('Show Navigation Dots?', 'bb-powerpack'),
						'default'       => 'yes',
						'options'       => array(
							'yes'       	=> __('Yes', 'bb-powerpack'),
							'no'			=> __('No', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'yes'	=> array(
								'sections'	=> array('post_carousel_dot_style')
							)
						)
					),
					'slider_navigation'     => array(
						'type'          => 'pp-switch',
						'label'         => __('Show Navigation Arrows?', 'bb-powerpack'),
						'default'       => 'no',
						'options'       => array(
							'yes'        	=> __('Yes', 'bb-powerpack'),
							'no'            => __('No', 'bb-powerpack'),
						),
						'toggle'		=> array(
							'yes'			=> array(
								'sections'		=> array( 'post_carousel_arrow_style' )
							)
						)
					),
				)
			),
			'post_carousel_arrow_style'   => array( // Section
                'title' => 'Carousel Navigation Arrow', // Section Title
                'fields' => array( // Section Fields
					'post_slider_arrow_font_size'   => array(
						'type'          => 'text',
						'label'         => __('Arrow Size', 'bb-powerpack'),
						'description'   => 'px',
						'size'      => 5,
						'maxlength' => 3,
						'default'       => '30',
						'preview'         => array(
							'type'            => 'css',
							'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
							'property'        => 'font-size',
							'unit'            => 'px'
						)
					),
					'post_slider_arrow_color'       => array(
						'type'      => 'pp-color',
						'label'     => __('Arrow Color', 'bb-powerpack'),
						'show_reset' => true,
						'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
					),
                    'post_slider_arrow_bg_color'       => array(
						'type'      => 'pp-color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> '',
							'secondary'	=> ''
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
					),
                    'post_slider_arrow_border_style'     => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Border Style', 'bb-powerpack'),
                        'default'     => 'none',
                        'options'       => array(
                             'none'          => __('None', 'bb-powerpack'),
                             'solid'          => __('Solid', 'bb-powerpack'),
                             'dashed'          => __('Dashed', 'bb-powerpack'),
                             'dotted'          => __('Dotted', 'bb-powerpack'),
                         ),
                         'toggle'   => array(
                             'solid'    => array(
                                 'fields'   => array('post_slider_arrow_border_width', 'post_slider_arrow_border_color')
                             ),
                             'dashed'    => array(
                                 'fields'   => array('post_slider_arrow_border_width', 'post_slider_arrow_border_color')
                             ),
                             'dotted'    => array(
                                 'fields'   => array('post_slider_arrow_border_width', 'post_slider_arrow_border_color')
                             ),
                             'double'    => array(
                                 'fields'   => array('post_slider_arrow_border_width', 'post_slider_arrow_border_color')
                             )
                         )
                    ),
                    'post_slider_arrow_border_width'   => array(
                        'type'          => 'text',
                        'label'         => __('Border Width', 'bb-powerpack'),
                        'description'   => 'px',
						'size'      => 5,
                        'maxlength' => 3,
                        'default'       => '1',
                        'preview'         => array(
                            'type'            => 'css',
                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
                            'property'        => 'border-width',
                            'unit'            => 'px'
                        )
                    ),
                    'post_slider_arrow_border_color'    => array(
						'type'      => 'pp-color',
                        'label'     => __('Border Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> '',
							'secondary'	=> ''
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
                    ),
					'post_slider_arrow_padding' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Padding', 'bb-powerpack'),
                        'description'   => 'px',
                        'default'       => array(
                            'top' => 10,
                            'right' => 10,
                            'bottom' => 10,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'         => array(
		                            'type'            => 'css',
		                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
		                            'property'        => 'padding-top',
		                            'unit'            => 'px'
		                        )
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'         => array(
		                            'type'            => 'css',
		                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
		                            'property'        => 'padding-bottom',
		                            'unit'            => 'px'
		                        )
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'         => array(
		                            'type'            => 'css',
		                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
		                            'property'        => 'padding-left',
		                            'unit'            => 'px'
		                        )
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'         => array(
		                            'type'            => 'css',
		                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
		                            'property'        => 'padding-right',
		                            'unit'            => 'px'
		                        )
                    		),
                    	)
                    ),
					'post_slider_arrow_border_radius'   => array(
						'type'          => 'text',
						'label'         => __('Round Corners', 'bb-powerpack'),
						'description'   => 'px',
						'size'      	=> 5,
						'maxlength' 	=> 3,
						'default'       => '0',
						'preview'         => array(
							'type'            => 'css',
							'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-buttons div',
							'property'        => 'border-radius',
							'unit'            => 'px'
						)
					),
                )
            ),
            'post_carousel_dot_style'   => array( // Section
                'title' => 'Carousel Navigation Dots', // Section Title
                'fields' => array( // Section Fields
                    'post_slider_dot_bg_color'  => array(
						'type'          => 'color',
						'label'         => __('Background Color', 'bb-powerpack'),
						'default'       => '666666',
						'show_reset'    => true,
						'preview'       => array(
							'type'          => 'css',
                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-page span',
                            'property'        => 'background',
						)
					),
                    'post_slider_dot_bg_hover'      => array(
						'type'          => 'color',
						'label'         => __('Active Color', 'bb-powerpack'),
						'default'       => '000000',
						'show_reset'    => true,
						'preview'       => array(
                            'type'          => 'css',
                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-page.active span',
                            'property'        => 'background',
						)
					),
                    'post_slider_dot_width'   => array(
                        'type'          => 'text',
                        'label'         => __('Size', 'bb-powerpack'),
                        'description'   => 'px',
						'size'      => 5,
                        'maxlength' => 3,
                        'default'       => '10',
                        'preview'         => array(
                            'type'            => 'css',
                            'rules'           => array(
                               array(
                                   'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-page span',
                                   'property'        => 'width',
                                   'unit'            => 'px'
                               ),
                               array(
                                   'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-page span',
                                   'property'        => 'height',
                                   'unit'            => 'px'
                               ),
                           ),
                        )
                    ),
                    'post_slider_dot_border_radius'   => array(
                        'type'          => 'text',
                        'label'         => __('Round Corners', 'bb-powerpack'),
                        'description'   => 'px',
						'size'      => 5,
                        'maxlength' => 3,
                        'default'       => '100',
                        'preview'         => array(
                            'type'            => 'css',
                            'selector'        => '.pp-content-post-carousel .owl-theme .owl-controls .owl-page span',
                            'property'        => 'border-radius',
                            'unit'            => 'px'
                        )
                    ),
                )
            )
		)
	),
	'ads' => array(
	  'title' => __( 'DFP Ads' ),
    'sections' => array(
      'ads' => array(
        'title'   => __( 'DFP Ads' ),
        'fields' => array(
          'first_ad_unit' => array(
            'type'		=> 'select',
            'label'		=> __('First Ad Unit', 'bb-powerpack'),
            'options'	=> DFPContentGridModule::get_ad_units(),
          ),
          'second_ad_unit' => array(
            'type'		=> 'select',
            'label'		=> __('Second Ad Unit', 'bb-powerpack'),
            'options'	=> DFPContentGridModule::get_ad_units(),
          ),
        )
      ),
    ),
  ),
	'content'   => array( // Tab
		'title'         => __('Content', 'bb-powerpack'),
		'file'          => BB_POWERPACK_DIR . 'modules/pp-content-grid/includes/loop-settings.php',
	),
	'style'         => array( // Tab
		'title'         => __('Style', 'bb-powerpack'), // Tab title
		'sections'      => array( // Tab Sections
			'post_grid_general'   => array(
				'title'         => __('Structure', 'bb-powerpack'),
				'fields'        => array(
					'post_background'      => array(
						'type'      => 'pp-color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> 'f5f5f5',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
                    ),
					'post_border'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Border Style', 'bb-powerpack'),
                        'default'   => 'none',
                        'options'   => array(
                            'none'  => __('None', 'bb-powerpack'),
                            'solid'  => __('Solid', 'bb-powerpack'),
                            'dashed'  => __('Dashed', 'bb-powerpack'),
                            'dotted'  => __('Dotted', 'bb-powerpack'),
                        ),
                        'toggle'    => array(
                            'dashed'   => array(
                                'fields'    => array('post_border_width', 'post_border_color', 'post_border_position')
                            ),
                            'dotted'   => array(
                                'fields'    => array('post_border_width', 'post_border_color', 'post_border_position')
                            ),
                            'solid'   => array(
                                'fields'    => array('post_border_width', 'post_border_color', 'post_border_position')
                            ),
                        ),
                    ),
                    'post_border_width'   => array(
                        'type'      => 'text',
                        'label'     => __('Border Width', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 1,
                        'description'   => 'px',
						'preview'              => array(
							'type'				=> 'css',
							'rules'	=> array(
								array(
									'selector'	=> '.pp-content-grid-post',
									'property'	=> 'border-width',
									'unit'		=> 'px'
								),
								array(
									'selector'	=> '.pp-content-carousel-post',
									'property'	=> 'border-width',
									'unit'		=> 'px'
								)
							)
						)
                    ),
                    'post_border_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Border Color', 'bb-powerpack'),
                        'show_reset'   => true,
						'preview'              => array(
							'type'				=> 'css',
							'rules'	=> array(
								array(
									'selector'	=> '.pp-content-grid-post',
									'property'	=> 'border-color',
								),
								array(
									'selector'	=> '.pp-content-carousel-post',
									'property'	=> 'border-color',
								)
							)
						)
					),
					'post_border_position'    => array(
                        'type'      => 'select',
                        'label'     => __('Border Position', 'bb-powerpack'),
                        'default'   => '',
                        'options'   => array(
                            ''  		=> __('All', 'bb-powerpack'),
                            'top'  		=> __('Top', 'bb-powerpack'),
                            'bottom'  	=> __('Bottom', 'bb-powerpack'),
                            'left'  	=> __('Left', 'bb-powerpack'),
                            'right'  	=> __('Right', 'bb-powerpack'),
						),
					),
					'post_content_alignment'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Text Alignment', 'bb-powerpack'),
                        'default'   => 'left',
                        'options'   => array(
                            'left'  => __('Left', 'bb-powerpack'),
                            'center'  => __('Center', 'bb-powerpack'),
                            'Right'  => __('Right', 'bb-powerpack'),
                        ),
                    ),
					'field_separator_0'  => array(
                        'type'                => 'pp-separator',
                        'color'               => 'eeeeee'
                    ),
					'post_shadow_display'   => array(
                        'type'                 => 'pp-switch',
                        'label'                => __('Enable Shadow', 'bb-powerpack'),
                        'default'              => 'no',
                        'options'              => array(
                            'yes'          => __('Yes', 'bb-powerpack'),
                            'no'             => __('No', 'bb-powerpack'),
                        ),
                        'toggle'    =>  array(
                            'yes'   => array(
                                'fields'    => array('post_shadow', 'post_shadow_color', 'post_shadow_opacity')
                            )
                        )
                    ),
                    'post_shadow' 		=> array(
						'type'              => 'pp-multitext',
						'label'             => __('Box Shadow', 'bb-powerpack'),
						'default'           => array(
							'vertical'			=> 2,
							'horizontal'		=> 2,
							'blur'				=> 2,
							'spread'			=> 1
						),
						'options'			=> array(
							'vertical'			=> array(
								'placeholder'		=> __('Vertical', 'bb-powerpack'),
								'tooltip'			=> __('Vertical', 'bb-powerpack'),
								'icon'				=> 'fa-arrows-v'
							),
							'horizontal'		=> array(
								'placeholder'		=> __('Horizontal', 'bb-powerpack'),
								'tooltip'			=> __('Horizontal', 'bb-powerpack'),
								'icon'				=> 'fa-arrows-h'
							),
							'blur'				=> array(
								'placeholder'		=> __('Blur', 'bb-powerpack'),
								'tooltip'			=> __('Blur', 'bb-powerpack'),
								'icon'				=> 'fa-circle-o'
							),
							'spread'			=> array(
								'placeholder'		=> __('Spread', 'bb-powerpack'),
								'tooltip'			=> __('Spread', 'bb-powerpack'),
								'icon'				=> 'fa-paint-brush'
							),
						)
					),
                    'post_shadow_color' => array(
                        'type'              => 'color',
                        'label'             => __('Shadow Color', 'bb-powerpack'),
                        'default'           => '000000',
                    ),
                    'post_shadow_opacity' => array(
                        'type'              => 'text',
                        'label'             => __('Shadow Opacity', 'bb-powerpack'),
                        'description'       => '%',
                        'size'             => 5,
                        'default'           => 50,
                    ),
					'field_separator_1'  => array(
                        'type'                => 'pp-separator',
                        'color'               => 'eeeeee'
                    ),
					'post_grid_padding'   => array(
                        'type'      => 'pp-multitext',
                        'label'     => __('Box Padding', 'bb-powerpack'),
                        'description'   => 'px',
						'default'       => array(
                            'top' => 10,
                            'right' => 10,
                            'bottom' => 10,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'              => array(
									'selector'	=> '.pp-content-post',
									'property'	=> 'padding-top',
									'unit'		=> 'px'
		                        )
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'              => array(
									'selector'	=> '.pp-content-post',
									'property'	=> 'padding-bottom',
									'unit'		=> 'px'
		                        )
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'              => array(
									'selector'	=> '.pp-content-post',
									'property'	=> 'padding-left',
									'unit'		=> 'px'
		                        )
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'              => array(
									'selector'	=> '.pp-content-post',
									'property'	=> 'padding-right',
									'unit'		=> 'px'
		                        )
                    		),
                    	)
                    ),
					'post_content_padding'   => array(
                        'type'      => 'pp-multitext',
                        'label'     => __('Content Padding', 'bb-powerpack'),
                        'description'   => 'px',
						'default'       => array(
                            'top' => 10,
                            'right' => 10,
                            'bottom' => 10,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'              => array(
									'selector'	=> '.pp-content-post .pp-content-body',
									'property'	=> 'padding-top',
									'unit'		=> 'px'
		                        )
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'              => array(
									'selector'	=> '.pp-content-post .pp-content-body',
									'property'	=> 'padding-bottom',
									'unit'		=> 'px'
		                        )
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'              => array(
		                            'selector'	=> '.pp-content-post .pp-content-body',
									'property'	=> 'padding-left',
									'unit'		=> 'px'
		                        )
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'              => array(
									'selector'	=> '.pp-content-post .pp-content-body',
									'property'	=> 'padding-right',
									'unit'		=> 'px'
		                        )
                    		),
                    	)
                    ),
					'post_border_radius'   => array(
                        'type'      => 'text',
                        'label'     => __('Round Corners', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 0,
                        'description'   => 'px',
                        'preview'       => array(
							'type'				=> 'css',
							'rules'	=> array(
								array(
									'selector'	=> '.pp-content-grid-post',
									'property'	=> 'border-radius',
									'unit'		=> 'px'
								),
								array(
									'selector'	=> '.pp-content-carousel-post',
									'property'	=> 'border-radius',
									'unit'		=> 'px'
								)
							)
                        ),
                    ),
				)
			),
			'divider_style'	=> array(
				'title'	=> __('Divider', 'bb-powerpack'),
				'fields'	=> array(
					'post_title_divider_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Color', 'bb-powerpack'),
						'default'		=> '333333',
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post .pp-post-title-divider',
							'property'	=> 'background-color',
                        ),
                    ),
				)
			),
			'post_category_style'	=> array(
				'title'	=> __('Taxonomy', 'bb-powerpack'),
				'fields'	=> array(
					'post_category_bg_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'default'		=> '000000',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post .pp-post-image .pp-content-category-list',
							'property'	=> 'background-color',
                        ),
                    ),
					'post_category_text_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Text Color', 'bb-powerpack'),
						'default'		=> 'ffffff',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post .pp-post-image .pp-content-category-list a, .pp-content-post .pp-post-image .pp-content-category-list',
							'property'	=> 'color',
                        ),
                    ),
					'post_category_position'	=> array(
						'type'          => 'pp-switch',
						'label'         => __('Position', 'bb-powerpack'),
						'default'       => 'left',
						'options'       => array(
							'left'          => __('Left', 'bb-powerpack'),
							'right'         => __('Right', 'bb-powerpack'),
						),
					),
				)
			),
			'post_title_style'	=> array(
				'title'	=> __('Title', 'bb-powerpack'),
				'fields'	=> array(
					'post_title_overlay_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Overlay Color', 'bb-powerpack'),
						'default'		=> '000000',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post .pp-post-image .pp-post-title',
							'property'	=> 'background',
                        ),
                    ),
					'post_title_overlay_opacity'   => array(
                        'type'          => 'text',
                        'label'         => __('Opacity', 'bb-powerpack'),
                        'description'   => '%',
						'size'      => 5,
                        'maxlength' => 3,
                        'default'       => '50',
                        'preview'         => array(
                            'type'            => 'css',
                            'selector'        => '.pp-content-post .pp-post-image .pp-post-title',
                            'property'        => 'opacity',
                        )
                    ),
				)
			),
			'post_date_style'	=> array(
				'title'	=> __('Date', 'bb-powerpack'),
				'fields'	=> array(
					'post_date_day_bg_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Day Background Color', 'bb-powerpack'),
						'default'		=> 'f9f9f9',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-day',
							'property'	=> 'background-color',
                        ),
                    ),
					'post_date_day_text_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Day Text Color', 'bb-powerpack'),
						'default'		=> '888888',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-day',
							'property'	=> 'color',
                        ),
                    ),
					'post_date_month_bg_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Month Background Color', 'bb-powerpack'),
						'default'		=> '000000',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-month',
							'property'	=> 'background-color',
                        ),
                    ),
					'post_date_month_text_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Month Text Color', 'bb-powerpack'),
						'default'		=> 'ffffff',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-month',
							'property'	=> 'color',
                        ),
                    ),
					'post_date_bg_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'default'		=> '000000',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-6 .pp-post-image .pp-content-post-date',
							'property'	=> 'background-color',
                        ),
                    ),
					'post_date_text_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Color', 'bb-powerpack'),
						'default'		=> 'ffffff',
						'show_reset' 	=> true,
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-post.pp-grid-style-6 .pp-post-image .pp-content-post-date',
							'property'	=> 'color',
                        ),
                    ),
					'post_date_border_radius'   => array(
                        'type'      => 'text',
                        'label'     => __('Round Corners', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 2,
                        'description'   => 'px',
                        'preview'       => array(
                            'type'      => 'css',
							'rules' 	=> array(
								array(
									'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-day',
									'property'	=> 'border-top-left-radius',
									'unit'		=> 'px'
								),
								array(
									'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-day',
									'property'	=> 'border-top-right-radius',
									'unit'		=> 'px'
								),
								array(
									'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-month',
									'property'	=> 'border-bottom-left-radius',
									'unit'		=> 'px'
								),
								array(
									'selector'	=>'.pp-content-post.pp-grid-style-5 .pp-content-post-date span.pp-post-month',
									'property'	=> 'border-bottom-right-radius',
									'unit'		=> 'px'
								)
							)
                        ),
                    ),
				)
			),
			'product_info_style'	=> array(
				'title'	=> __('Product Info', 'bb-powerpack'),
				'fields'	=> array(
					'product_rating_color'  => array(
						'type'          => 'color',
						'label'         => __('Rating Color', 'bb-powerpack'),
						'default'       => '000000',
						'show_reset'    => true,
						'preview'       => array(
							'type'          => 'css',
                            'selector'        => '.pp-content-post .star-rating:before, .pp-content-post .star-rating span:before',
                            'property'        => 'color',
						)
					),
					'product_price_color'  => array(
						'type'          => 'color',
						'label'         => __('Price Color', 'bb-powerpack'),
						'default'       => '000000',
						'show_reset'    => true,
						'preview'       => array(
							'type'          => 'css',
                            'selector'        => '.pp-content-post .pp-product-price',
                            'property'        => 'color',
						)
					),
				)
			),
			'button_colors'	=> array(
				'title'	=> __('Read More Link/Button', 'bb-powerpack'),
				'fields'	=> array(
					'button_background'      => array(
						'type'      => 'pp-color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> '666666'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
                    ),
					'button_color'	=> array(
						'type'      => 'pp-color',
                        'label'     => __('Text Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> 'ffffff',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
					),
					'button_border'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Border Style', 'bb-powerpack'),
                        'default'   => 'none',
                        'options'   => array(
                            'none'  => __('None', 'bb-powerpack'),
                            'solid'  => __('Solid', 'bb-powerpack'),
                        ),
                        'toggle'    => array(
                            'solid'   => array(
                                'fields'    => array('button_border_width', 'button_border_color', 'button_border_position')
                            ),
                        ),
						'preview'       => array(
							'type'            => 'css',
							'selector'        => '.pp-content-post .pp-more-link-button',
							'property'        => 'border-style',
                        ),
                    ),
                    'button_border_width'   => array(
						'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Border Width', 'bb-powerpack'),
                        'description'   => __( 'px', 'Value unit for font size. Such as: "14 px"', 'bb-powerpack' ),
                        'default'       => array(
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'border-top-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'border-bottom-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'border-left-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'border-right-width',
									'unit'            => 'px'
		                        ),
                    		),
                    	)
                    ),
                    'button_border_color'   => array(
						'type'      => 'pp-color',
                        'label'     => __('Border Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
                    ),
					'button_width'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Width', 'bb-powerpack'),
                        'default'   => 'default',
                        'options'   => array(
                            'default'  => __('Auto', 'bb-powerpack'),
                            'full'  => __('Full Width', 'bb-powerpack'),
                        ),
                    ),
                    'button_border_radius'   => array(
                        'type'      => 'text',
                        'label'     => __('Round Corners', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 0,
                        'description'   => 'px',
                        'preview'       => array(
							'type'            => 'css',
							'selector'        => '.pp-content-post .pp-more-link-button',
							'property'        => 'border-radius',
							'unit'            => 'px'
                        ),
                    ),
					'button_padding' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Padding', 'bb-powerpack'),
                        'description'   => __( 'px', 'Value unit for font size. Such as: "14 px"', 'bb-powerpack' ),
                        'default'       => array(
                            'top' => 5,
                            'right' => 10,
                            'bottom' => 5,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'padding-top',
									'unit'            => 'px'
		                        ),
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'padding-bottom',
									'unit'            => 'px'
		                        ),
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'padding-left',
									'unit'            => 'px'
		                        ),
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'padding-right',
									'unit'            => 'px'
		                        ),
                    		),
                    	)
                    ),
					'button_margin' 	=> array(
                    	'type' 				=> 'pp-multitext',
                    	'label' 			=> __('Margin', 'bb-powerpack'),
                        'description'   	=> __( 'px', 'Value unit for font size. Such as: "14 px"', 'bb-powerpack' ),
                        'default'       	=> array(
                            'top' 				=> 10,
                            'bottom' 			=> 5,
                        ),
                    	'options' 		=> array(
                    		'top' 			=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'		=> '.pp-content-post .pp-content-grid-more-link',
									'property'      => 'margin-top',
									'unit'          => 'px'
		                        ),
                    		),
                            'bottom' 		=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'      => '.pp-content-post .pp-content-grid-more-link',
									'property'      => 'margin-bottom',
									'unit'          => 'px'
		                        ),
                    		),
                    	)
                    ),
				)
			),
		)
	),
	'pagination'	=> array(
		'title'			=> __('Pagination', 'bb-powerpack'),
		'sections'		=> array(
			'pagination_style'    => array(
				'title'         => __('General', 'bb-powerpack'),
				'fields'        => array(
					'pagination_spacing_v'   => array(
                        'type'      => 'text',
                        'label'     => __('Spacing Top/Bottom', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 15,
                        'description'   => 'px',
                        'preview'       => array(
                            'type'      => 'css',
							'rules'		=> array(
								array(
									'selector'	=>'.pp-content-grid-pagination.fl-builder-pagination',
									'property'	=> 'padding-top',
									'unit'		=> 'px'
								),
								array(
									'selector'	=>'.pp-content-grid-pagination.fl-builder-pagination',
									'property'	=> 'padding-bottom',
									'unit'		=> 'px'
								)
							)
                        ),
                    ),
					'pagination_spacing'   => array(
                        'type'      => 'text',
                        'label'     => __('Spacing Left/Right', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 5,
                        'description'   => 'px',
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-grid-pagination li .page-numbers',
							'property'	=> 'margin-right',
							'unit'		=> 'px'
                        ),
                    ),
					'pagination_padding'   => array(
                        'type'      => 'pp-multitext',
                        'label'     => __('Padding', 'bb-powerpack'),
                        'description'   => 'px',
						'default'       => array(
                            'top' => 5,
                            'right' => 10,
                            'bottom' => 5,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'              => array(
									'selector'	=> '.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'padding-top',
									'unit'		=> 'px'
		                        )
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'              => array(
									'selector'	=> '.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'padding-bottom',
									'unit'		=> 'px'
		                        )
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'              => array(
		                            'selector'	=> '.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'padding-left',
									'unit'		=> 'px'
		                        )
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'      => array(
									'selector'	=> '.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'padding-right',
									'unit'		=> 'px'
		                        )
                    		),
                    	)
                    ),
				)
			),
			'pagination_colors'	=> array(
				'title'				=> __('Colors', 'bb-powerpack'),
				'fields'			=> array(
					'pagination_background_color'    => array(
						'type'      => 'pp-color',
                        'label'     => __('Background Color', 'bb-powerpack'),
                        'default'   => array(
							'primary'	=> 'ffffff',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Active', 'bb-powerpack')
						),
						'preview'       => array(
							'type'		=> 'css',
							'rules'	=> array(
								array(
									'selector'	=> '.pp-content-grid-post .pp-post-date',
									'property'	=> 'border-style',
								),
								array(
									'selector'	=> '.pp-content-carousel-post .pp-post-date',
									'property'	=> 'border-style',
								)
							)
						)
					),
					'pagination_text_color' => array(
						'type'      => 'pp-color',
                        'label'     => __('Text Color', 'bb-powerpack'),
                        'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> '000000'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Active', 'bb-powerpack')
						)
					),
				)
			),
			'pagination_border'	=> array(
				'title'				=> __('Border', 'bb-powerpack'),
				'fields'			=> array(
					'pagination_border'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Border Style', 'bb-powerpack'),
                        'default'   => 'none',
                        'options'   => array(
                            'none'  => __('None', 'bb-powerpack'),
                            'solid'  => __('Solid', 'bb-powerpack'),
                            'dashed'  => __('Dashed', 'bb-powerpack'),
                            'dotted'  => __('Dotted', 'bb-powerpack'),
                        ),
                        'toggle'    => array(
                            'dashed'   => array(
                                'fields'    => array('pagination_border_width', 'pagination_border_color')
                            ),
                            'dotted'   => array(
                                'fields'    => array('pagination_border_width', 'pagination_border_color')
                            ),
                            'solid'   => array(
                                'fields'    => array('pagination_border_width', 'pagination_border_color')
                            ),
                        ),
                    ),
                    'pagination_border_width'   => array(
                        'type'      => 'text',
                        'label'     => __('Border Width', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 1,
                        'description'   => 'px',
                        'preview'       => array(
                            'type'      => 'css',
							'rules' 	=> array(
								array(
									'selector'	=>'.pp-content-grid-pagination li a.page-numbers',
									'property'	=> 'border-width',
									'unit'		=> 'px'
								),
								array(
									'selector'	=>'.pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'border-width',
									'unit'		=> 'px'
								)
							)
                        ),
                    ),
                    'pagination_border_color'   => array(
                        'type'      => 'color',
                        'label'     => __('Border Color', 'bb-powerpack'),
                        'show_reset'   => true,
						'default'		=> 'cccccc',
                        'preview'       => array(
                            'type'      => 'css',
							'rules' 	=> array(
								array(
									'selector'	=>'.pp-content-grid-pagination li a.page-numbers',
									'property'	=> 'border-color',
								),
								array(
									'selector'	=>'.pp-content-grid-pagination li span.page-numbers',
									'property'	=> 'border-color',
								)
							)
                        ),
                    ),
                    'pagination_border_radius'   => array(
                        'type'      => 'text',
                        'label'     => __('Round Corners', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 100,
                        'description'   => 'px',
                        'preview'       => array(
                            'type'      => 'css',
							'selector'	=>'.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
							'property'	=> 'border-radius',
							'unit'		=> 'px'
                        ),
                    ),
				)
			),
		)
	),
	'filters_style'         => array( // Tab
		'title'         => __('Filter', 'bb-powerpack'), // Tab title
		'sections'      => array( // Tab Sections
			'filter_general_setting'	=> array(
				'title'	=> __('General', 'bb-powerpack'),
				'fields'	=> array(
					'filter_alignment'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Alignment', 'bb-powerpack'),
                        'default'   => 'left',
                        'options'   => array(
                            'left'  => __('Left', 'bb-powerpack'),
                            'center'  => __('Center', 'bb-powerpack'),
                            'Right'  => __('Right', 'bb-powerpack'),
                        ),
					),
					'filter_margin' 	=> array(
						'type'      => 'text',
                        'label'     => __('Spacing', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 10,
                        'description'   => 'px',
                        'preview'       => array(
							'type'            => 'css',
							'selector'        => '.pp-post-filters li',
							'property'        => 'margin-right',
							'unit'            => 'px'
                        ),
                    ),
				)
			),
			'filter_colors'	=> array(
				'title'	=> __('Colors', 'bb-powerpack'),
				'fields'	=> array(
                    'filter_background'      => array(
						'type'      => 'pp-color',
                        'label'     => __('Background Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> 'eeeeee',
							'secondary'	=> 'bbbbbb'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Active', 'bb-powerpack')
						)
                    ),
					'filter_color'	=>array(
						'type'      => 'pp-color',
						'label'     => __('Text Color', 'bb-powerpack'),
						'show_reset' => true,
						'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> 'ffffff'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Active', 'bb-powerpack')
						)
					),
				),
			),
			'filter_border_setting'	=> array(
				'title'	=> __('Border', 'bb-powerpack'),
				'fields'	=> array(
					'filter_border'    => array(
                        'type'      => 'pp-switch',
                        'label'     => __('Border Style', 'bb-powerpack'),
                        'default'   => 'none',
                        'options'   => array(
                            'none'  => __('None', 'bb-powerpack'),
                            'solid'  => __('Solid', 'bb-powerpack'),
							'dashed'  => __('Dashed', 'bb-powerpack'),
							'dotted'  => __('Dotted', 'bb-powerpack'),
                        ),
                        'toggle'    => array(
                            'solid'   => array(
                                'fields'    => array('filter_border_width', 'filter_border_color')
                            ),
							'dashed'   => array(
                                'fields'    => array('filter_border_width', 'filter_border_color')
                            ),
							'dotted'   => array(
                                'fields'    => array('filter_border_width', 'filter_border_color')
                            ),
                        ),
						'preview'       => array(
							'type'            => 'css',
							'selector'        => '.pp-post-filters li',
							'property'        => 'border-style',
                        ),
                    ),
                    'filter_border_width'   => array(
						'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Border Width', 'bb-powerpack'),
                        'description'   => __( 'px', 'Value unit for font size. Such as: "14 px"', 'bb-powerpack' ),
                        'default'       => array(
                            'top' => 1,
                            'right' => 1,
                            'bottom' => 1,
                            'left' => 1,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'border-top-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'border-bottom-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'border-left-width',
									'unit'            => 'px'
		                        ),
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'border-right-width',
									'unit'            => 'px'
		                        ),
                    		),
                    	)
                    ),
                    'filter_border_color'   => array(
						'type'      => 'pp-color',
                        'label'     => __('Border Color', 'bb-powerpack'),
						'show_reset' => true,
                        'default'   => array(
							'primary'	=> '000000',
							'secondary'	=> 'eeeeee'
						),
						'options'	=> array(
							'primary'	=> __('Default', 'bb-powerpack'),
							'secondary' => __('Hover', 'bb-powerpack')
						)
                    ),
				)
			),
			'filter_corners_padding'  => array(
				'title'         => __('Corners & Padding', 'bb-powerpack'),
				'fields'        => array(
                    'filter_border_radius'   => array(
                        'type'      => 'text',
                        'label'     => __('Round Corners', 'bb-powerpack'),
                        'size'      => 5,
                        'maxlength' => 3,
                        'default'   => 0,
                        'description'   => 'px',
                        'preview'       => array(
							'type'            => 'css',
							'selector'        => '.pp-post-filters li',
							'property'        => 'border-radius',
							'unit'            => 'px'
                        ),
                    ),
					'filter_padding' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Padding', 'bb-powerpack'),
                        'description'   => __( 'px', 'Value unit for font size. Such as: "14 px"', 'bb-powerpack' ),
                        'default'       => array(
                            'top' => 8,
                            'right' => 10,
                            'bottom' => 8,
                            'left' => 10,
                        ),
                    	'options' 		=> array(
                    		'top' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'padding-top',
									'unit'            => 'px'
		                        ),
                    		),
                            'bottom' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'padding-bottom',
									'unit'            => 'px'
		                        ),
                    		),
                            'left' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Left', 'bb-powerpack'),
                                'tooltip'       => __('Left', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-left',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'padding-left',
									'unit'            => 'px'
		                        ),
                    		),
                            'right' => array(
                                'maxlength' => 3,
                                'placeholder'   => __('Right', 'bb-powerpack'),
                                'tooltip'       => __('Right', 'bb-powerpack'),
                    			'icon'		=> 'fa-long-arrow-right',
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'padding-right',
									'unit'            => 'px'
		                        ),
                    		),
                    	)
                    ),
				)
			),
			'filter_settings'	=> array(
				'title'				=> __('Responsive', 'bb-powerpack'),
				'fields'			=> array(
					'responsive_filter'	=> array(
						'type'				=> 'pp-switch',
						'label'				=> __('Enable Filter Dropdown', 'bb-powerpack'),
						'default'			=> 'no',
						'options'			=> array(
							'yes'				=> __('Yes', 'bb-powerpack'),
							'no'				=> __('No', 'bb-powerpack'),
						),
						'help'				=> __('By eneabling this option will convert filters into a dropdown on responsive devices. If you want to display the filters as they are appearing on desktop, keep it disabled.', 'bb-powerpack')
					),
				)
			),
		)
	),
	'typography'	=> array( // Tab
		'title'			=> __('Typography', 'bb-powerpack'),
		'sections'		=> array(
			'title_typography'		=> array(
				'title'		=> __('Title', 'bb-powerpack'),
				'fields' 	=> array(
					'title_tag'		=> array(
						'type'		=> 'select',
						'label'		=> __('HTML Tag', 'bb-powerpack'),
						'options'	=> array(
							'h1'	=> 'H1',
							'h2'	=> 'H2',
							'h3'	=> 'H3',
							'h4'	=> 'H4',
							'h5'	=> 'H5',
							'h6'	=> 'H6',
						),
						'default'	=> 'h3',
						'help' 		=> __('Set the HTML tag for title output', 'bb-powerpack'),
					),
					'title_font'	=> array(
						'type'		=> 'font',
						'label'		=> __('Font', 'bb-powerpack'),
						'default'	=> array(
							'family'	=> 'Default',
							'weight'	=> '400',
						),
						'preview'       => array(
							'type'		=> 'font',
							'selector'        => '.pp-content-post .pp-post-title, .pp-content-post .pp-post-title a',
						),
					),
					'title_font_size_toggle' => array(
						'type'		=> 'pp-switch',
						'label'		=> __('Font Size', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'custom'         => __('Custom', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'custom'	=> array(
								'fields'	=> array('title_custom_font_size')
							)
						),
					),
					'title_custom_font_size'	=> array(
						'type' 		=> 'pp-multitext',
						'label'		=> __('Custom Font Size', 'bb-powerpack'),
						'default'		=> array(
							'desktop'	=> 24,
							'tablet'	=> '',
							'mobile'	=> '',
						),
						'options' 		=> array(
							'desktop' => array(
								'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-post-title, .pp-content-post .pp-post-title a',
									'property'        => 'font-size',
									'unit'            => 'px'
		                        ),
							),
							'tablet' => array(
								'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
							),
							'mobile' => array(
								'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
							),

						),
					),
					'title_line_height_toggle' => array(
						'type'		=> 'pp-switch',
						'label'		=> __('Line Height', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'custom'         => __('Custom', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'custom'	=> array(
								'fields'	=> array('title_custom_line_height')
							)
						),
					),
					'title_custom_line_height'	=> array(
						'type' 		=> 'pp-multitext',
						'label'		=> __('Custom Line Height', 'bb-powerpack'),
						'help' 		=> __('Recommended values between 1-2', 'bb-powerpack'),
						'default'	=> array(
							'desktop'	=> 1.6,
							'tablet'	=> '',
							'mobile'	=> '',
						),
						'options' 		=> array(
							'desktop' => array(
								'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-post-title',
									'property'        => 'line-height',
		                        ),
							),
							'tablet' => array(
								'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
							),
							'mobile' => array(
								'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
							),

						),
					),
					'title_font_color' 		=> array(
						'type'			=> 'color',
						'label'			=> __('Text Color', 'bb-powerpack'),
						'show_reset'	=> true,
						'preview'       => array(
							'type'		=> 'css',
							'rules'           => array(
							   array(
								   'selector'        => '.pp-content-post .pp-post-title',
								   'property'        => 'color',
							   ),
							   array(
								   'selector'        => '.pp-content-post .pp-post-title a',
								   'property'        => 'color',
							   ),
						   ),
						),
					),
					'title_text_transform' => array(
						'type'		=> 'select',
						'label'		=> __('Text Transform', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'lowercase'         => __('lowercase', 'bb-powerpack'),
							'uppercase'         => __('UPPERCASE', 'bb-powerpack'),
						),
					),
					'title_margin' 	=> array(
                    	'type' 				=> 'pp-multitext',
                    	'label' 			=> __('Margin', 'bb-powerpack'),
                        'description'   	=> 'px',
                        'default'       	=> array(
                            'top' 				=> 5,
                            'bottom' 			=> 5,
                        ),
                    	'options' 		=> array(
                    		'top' 			=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'		=> '.pp-content-post .pp-post-title',
									'property'      => 'margin-top',
									'unit'          => 'px'
		                        ),
                    		),
                            'bottom' 		=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'      => '.pp-content-post .pp-post-title',
									'property'      => 'margin-bottom',
									'unit'          => 'px'
		                        ),
                    		),
                    	)
                    ),
				)
			),
			'content_typography'		=> array(
				'title'		=> __('Description', 'bb-powerpack'),
				'fields' 	=> array(
					'content_font'	=> array(
						'type'		=> 'font',
						'label'		=> __('Font', 'bb-powerpack'),
						'default'	=> array(
							'family'	=> 'Default',
							'weight'	=> '400',
						),
						'preview'       => array(
							'type'		=> 'font',
							'selector'        => '.pp-content-post .pp-post-content',
						),
					),
					'content_font_size_toggle' => array(
						'type'		=> 'pp-switch',
						'label'		=> __('Font Size', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'custom'         => __('Custom', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'custom'	=> array(
								'fields'	=> array('content_custom_font_size')
							)
						),
					),
					'content_custom_font_size'	=> array(
						'type' 		=> 'pp-multitext',
						'label'		=> __('Custom Font Size', 'bb-powerpack'),
						'default'	=> array(
							'desktop'	=> 16,
							'tablet'	=> '',
							'mobile'	=> '',
						),
						'options' 		=> array(
							'desktop' => array(
								'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-post-content',
									'property'        => 'font-size',
									'unit'			=> 'px'
								),
							),
							'tablet' => array(
								'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
							),
							'mobile' => array(
								'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
							),

						),
					),
					'content_line_height_toggle' => array(
						'type'		=> 'pp-switch',
						'label'		=> __('Line Height', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'custom'         => __('Custom', 'bb-powerpack'),
						),
						'toggle'	=> array(
							'custom'	=> array(
								'fields'	=> array('content_custom_line_height')
							)
						),
					),
					'content_custom_line_height'	=> array(
						'type' 		=> 'pp-multitext',
						'label'		=> __('Custom Line Height', 'bb-powerpack'),
						'help' => __('Recommended values between 1-2', 'bb-powerpack'),
						'default'	=> array(
							'desktop'	=> 1.6,
							'tablet'	=> '',
							'mobile'	=> '',
						),
						'options' 		=> array(
							'desktop' => array(
								'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-post-content',
									'property'        => 'line-height',
								),
							),
							'tablet' => array(
								'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
							),
							'mobile' => array(
								'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
							),

						),
					),
					'content_font_color' 		=> array(
						'type'			=> 'color',
						'label'			=> __('Text Color', 'bb-powerpack'),
						'show_reset'	=> true,
						'preview'       => array(
							'type'		=> 'css',
							'selector'        => '.pp-content-post .pp-post-content',
							'property'        => 'color',
						),
					),
					'description_margin' 	=> array(
                    	'type' 				=> 'pp-multitext',
                    	'label' 			=> __('Margin', 'bb-powerpack'),
                        'description'   	=> 'px',
                        'default'       	=> array(
                            'top' 				=> 5,
                            'bottom' 			=> 5,
                        ),
                    	'options' 		=> array(
                    		'top' 			=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Top', 'bb-powerpack'),
                                'tooltip'       => __('Top', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-up',
								'preview'       => array(
									'selector'		=> '.pp-content-post .pp-post-content',
									'property'      => 'margin-top',
									'unit'          => 'px'
		                        ),
                    		),
                            'bottom' 		=> array(
                                'maxlength' 	=> 3,
                                'placeholder'   =>  __('Bottom', 'bb-powerpack'),
                                'tooltip'       => __('Bottom', 'bb-powerpack'),
                    			'icon'			=> 'fa-long-arrow-down',
								'preview'       => array(
									'selector'      => '.pp-content-post .pp-post-content',
									'property'      => 'margin-bottom',
									'unit'          => 'px'
		                        ),
                    		),
                    	)
                    ),
				)
			),
			'post_meta_typography'	=> array(
				'title' => __('Meta', 'bb-powerpack'),
                'fields'    => array(
                    'post_meta_font'    => array(
                        'type'      => 'font',
						'label'         => __('Font', 'bb-powerpack'),
                        'default'		=> array(
                            'family'		=> 'Default',
                            'weight'		=> 300
                        ),
						'preview'       => array(
							'type'		=> 'font',
							'selector'        => '.pp-content-post .pp-post-meta',
						),
                    ),
					'post_meta_font_size' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Font Size', 'bb-powerpack'),
                        'default'       => array(
                            'desktop' => 14,
                            'tablet' => '',
                            'mobile' => ''
                        ),
                    	'options' 		=> array(
                    		'desktop' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-post-meta',
									'property'        => 'font-size',
									'unit'			=> 'px'
								),
                    		),
                            'tablet' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
                    		),
                            'mobile' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
                    		),
                    	)
                    ),
					'post_meta_font_color' 		=> array(
						'type'			=> 'color',
						'label'			=> __('Text Color', 'bb-powerpack'),
						'default'		=> '606060',
						'show_reset' 	=> true,
						'preview'       => array(
							'type'		=> 'css',
							'rules'			  => array(
								array(
									'selector'        => '.pp-content-post .pp-post-meta',
									'property'        => 'color',
								),
								array(
									'selector'        => '.pp-content-post .pp-post-meta a',
									'property'        => 'color',
								)
							)
						),
					),
					'post_meta_text_transform' => array(
						'type'		=> 'select',
						'label'		=> __('Text Transform', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'lowercase'         => __('lowercase', 'bb-powerpack'),
							'uppercase'         => __('UPPERCASE', 'bb-powerpack'),
						),
					),
				)
			),
			'button_typography'  => array(
                'title' => __('Read More Link/Button', 'bb-powerpack'),
                'fields'    => array(
                    'button_font'    => array(
                        'type'      => 'font',
						'label'         => __('Font', 'bb-powerpack'),
                        'default'		=> array(
                            'family'		=> 'Default',
                            'weight'		=> 300
                        ),
						'preview'       => array(
							'type'		=> 'font',
							'selector'        => '.pp-content-post .pp-more-link-button',
						),
                    ),
                    'button_font_size' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Font Size', 'bb-powerpack'),
                        'default'       => array(
                            'desktop' => 14,
                            'tablet' => '',
                            'mobile' => ''
                        ),
                    	'options' 		=> array(
                    		'desktop' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-post .pp-more-link-button',
									'property'        => 'font-size',
									'unit'			=> 'px'
								),
                    		),
                            'tablet' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
                    		),
                            'mobile' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
                    		),
                    	)
                    ),
					'button_text_transform' => array(
						'type'		=> 'select',
						'label'		=> __('Text Transform', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'lowercase'         => __('lowercase', 'bb-powerpack'),
							'uppercase'         => __('UPPERCASE', 'bb-powerpack'),
						),
					),
                ),
            ),
			'filter_typography'  => array(
                'title' => __('Filter', 'bb-powerpack'),
                'fields'    => array(
                    'filter_font'    => array(
                        'type'      => 'font',
						'label'         => __('Font', 'bb-powerpack'),
                        'default'		=> array(
                            'family'		=> 'Default',
                            'weight'		=> 300
                        ),
						'preview'       => array(
							'type'		=> 'font',
							'selector'        => '.pp-post-filters li',
						),
                    ),
                    'filter_font_size' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Font Size', 'bb-powerpack'),
                        'default'       => array(
                            'desktop' => 14,
                            'tablet' => '',
                            'mobile' => ''
                        ),
                    	'options' 		=> array(
                    		'desktop' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-post-filters li',
									'property'        => 'font-size',
									'unit'			=> 'px'
								),
                    		),
                            'tablet' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
                    		),
                            'mobile' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
                    		),
                    	)
                    ),
					'filter_text_transform' => array(
						'type'		=> 'select',
						'label'		=> __('Text Transform', 'bb-powerpack'),
						'default'	=> 'default',
						'options'       => array(
							'default'          => __('Default', 'bb-powerpack'),
							'lowercase'         => __('lowercase', 'bb-powerpack'),
							'uppercase'         => __('UPPERCASE', 'bb-powerpack'),
						),
					),
                ),
            ),
			'pagination_typography'  => array(
                'title' => __('Pagination', 'bb-powerpack'),
                'fields'    => array(
					'pagination_font_size' 	=> array(
                    	'type' 			=> 'pp-multitext',
                    	'label' 		=> __('Font Size', 'bb-powerpack'),
                        'default'       => array(
                            'desktop' => 14,
                            'tablet' => '',
                            'mobile' => ''
                        ),
                    	'options' 		=> array(
                    		'desktop' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-desktop',
								'placeholder'	=> __('Desktop', 'bb-powerpack'),
								'tooltip'	=> __('Desktop', 'bb-powerpack'),
								'preview'       => array(
									'selector'        => '.pp-content-grid-pagination li a.page-numbers, .pp-content-grid-pagination li span.page-numbers',
									'property'        => 'font-size',
									'unit'			=> 'px'
								),
                    		),
                            'tablet' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-tablet',
								'placeholder'	=> __('Tablet', 'bb-powerpack'),
								'tooltip'	=> __('Tablet', 'bb-powerpack'),
                    		),
                            'mobile' => array(
                                'maxlength' => 3,
                    			'icon'		=> 'fa-mobile',
								'placeholder'	=> __('Mobile', 'bb-powerpack'),
								'tooltip'	=> __('Mobile', 'bb-powerpack'),
                    		),
                    	)
                    ),
				)
			),
		)
	)
));
