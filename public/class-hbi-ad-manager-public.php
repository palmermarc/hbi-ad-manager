<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the dashboard-specific stylesheet and JavaScript.
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/public
 * @author     Marc Palmer <mapalmer@hbi.com>
 */
class HBI_Ad_Manager_Public {

  /**
   * The ID of this plugin.
   *
   * @since    0.1
   * @access   private
   * @var      string    $plugin_name    The ID of this plugin.
   */
  private $plugin_name;

  /**
   * The version of this plugin.
   *
   * @since    0.1
   * @access   private
   * @var      string    $version    The current version of this plugin.
   */
  private $version;


  /**
   * List of conditional functions that are possible
   *
   * @since   2.0
   * @access  private
   * @var     array
   */
  private $conditional_functions;

  /**
   * Cached array of post ids that have their
   * location set to the current page.
   *
   * @since 1.0
   * @access private
   * @var array $current_page_posts
   */
  static private $current_page_posts = null;


  /**
   * Initialize the class and set its properties.
   *
   * @since    0.1
   * @var      string    $plugin_name       The name of the plugin.
   * @var      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

    /**
     * List of conditionals taken directly from the codex:
     *
     * http://codex.wordpress.org/Conditional_Tags
     *
     * @since   2.0
     */
    $this->conditional_functions = array('comments_open','has_tag','has_term','in_category','is_404','is_admin','is_archive','is_attachment','is_author','is_category','is_child_theme','is_comments_popup','is_date','is_day','is_feed','is_front_page','is_home','is_month','is_multi_author','is_multisite','is_main_site','is_page','is_page_template','is_paged','is_preview','is_rtl','is_search','is_single','is_singular','is_sticky','is_super_admin','is_tag','is_tax','is_time','is_trackback','is_year','pings_open');
  }

  public function enqueue_targeting_scripts(){
    if( current_user_can( 'edit_theme_options' ) ) {
      wp_register_script( 'toolbar-dfp-ad-targeting', plugin_dir_url( __FILE__ ) . 'js/ad-targeting.js', array( 'jquery' ), false, true );
      wp_localize_script( 'toolbar-dfp-ad-targeting', 'dfp_ad_targets', self::get_ad_targeting() );
      wp_enqueue_script( 'toolbar-dfp-ad-targeting' );
    }
  }


  public function inject_hbi_ad_manager_into_header() {
    $options =  get_option('hbi_ad_manager_settings');

    /** Call in the appropriate header based on whether or not asynchronous rendering is enabled */
    echo ( $options['async_rendering'] ) ?
      "<script async='async' src='https://www.googletagservices.com/tag/js/gpt.js'></script>
      <script>
        var googletag = googletag || {};
        googletag.cmd = googletag.cmd || [];
      </script>"
    : "<script src='https://www.googletagservices.com/tag/js/gpt.js'></script>";

    ?>
    <script>
      googletag.cmd.push(function() {
        <?php
        $admaps = self::get_ad_maps();

        /**
         * Echo the admaps, if there are any
         */
        if( !empty( $admaps ) ) :
          foreach( $admaps as $admap ) :

            echo "var {$admap['slug']} = googletag.sizeMapping().";

            foreach( $admap['sizes'] as $size ) :
              $admap_ad_size = ( 0 == $size['ad_width'] && 0 == $size['ad_height'] ) ? '' : $size['ad_width'] . ", " . $size['ad_height'];
              echo "addSize([{$size['browser_width']}, {$size['browser_height']}], [{$admap_ad_size}]).";
            endforeach;

            echo "build();\r\n";

          endforeach;
          echo "\r\n";
        endif;

        /**
         * Get all of the ad units and then echo them to the page if there are any published
         */
        $ad_units = $this->get_matching_ad_units();

        if( !empty( $ad_units ) ) :
          foreach( $ad_units as $ad_unit ) :

            /**
             * Only echo the add slot if the unit is defined
             */
            if( !empty( $ad_unit['dfp_ad_unit'] ) ) {
              $admap = $ad_unit['admap'];

              $admap_string = (0 != absint( $ad_unit['admap'] )) ? ".defineSizeMapping({$admaps[$admap]['slug']})" :  '';
              $ad_slot = str_replace( '-', '_', $ad_unit['tag_id'] );
              echo "var $ad_slot = googletag.defineSlot('/{$ad_unit['dfp_network_code']}/{$ad_unit['dfp_ad_unit']}', [{$ad_unit['width']}, {$ad_unit['height']}], '{$ad_unit['tag_id']}'){$admap_string}.setCollapseEmptyDiv(true).addService(googletag.pubads());\r\n";
              echo "console.log($ad_slot);";
            }

          endforeach;
        endif;


        /**
         * If single_request is turned on, then output the required declaration
         */
        echo ( 1 == $options['single_request'] ) ? "googletag.pubads().enableSingleRequest();\r\n" : "";


        /**
         * Echo the page-level targeting options that have been set
         */
        foreach( self::get_ad_targeting() as $key => $val ) { ?>
        googletag.pubads().setTargeting("<?php echo $key; ?>", <?php echo json_encode( $val ); ?>);
        <?php } ?>

        googletag.enableServices();

        googletag.pubads().addEventListener('slotRenderEnded', function(event) {
          console.log(event);
        });
      });
    </script>
    <?php
  }

  /**
   * Get all of the published ad maps and return an array with all of the
   * information needed for Google DFP
   *
   * @return  array   $ad_maps    Ad maps used for responsive ads
   */
  public function get_ad_maps() {
    $terms = get_terms( 'admap', array( 'hide_empty' => 0 ) );

    $ad_maps = array();
    if( !empty( $terms ) ) :
      foreach( $terms as $term ) :

        $ad_map = [
          'slug' => str_replace( '-', '_', $term->slug ),
        ];

        $term_meta = get_option( "taxonomy_term_$term->term_id" );

        foreach( $term_meta['admap_sizes'] as $ad_map_size ) :
          $ad_map['sizes'][] = [
            'ad_height' => $ad_map_size['ad_height'],
            'ad_width' => $ad_map_size['ad_width'],
            'browser_height' => $ad_map_size['browser_height'],
            'browser_width' => $ad_map_size['browser_width'],
          ];
        endforeach;

        $ad_maps[ $term->term_id ] = $ad_map;
      endforeach;
    endif;

    return $ad_maps;
  }

  /**
   * Get all of the ad targeting data based on which page the user is on
   *
   * @return    array   @ad_targets   Ad targets to help pinpoint ads to specific ad slots
   */
  public function get_ad_targeting() {
    $post = false;
    if ( is_singular() ) {
      $post = get_post();
    }

    $term = false;
    if ( is_category() || is_tax() || is_tag() ) {
      $term = get_queried_object();
      $term = $term->name;
    }

    $author = false;
    if ( is_author() ) {
      $author = get_queried_object();
      $author = isset($author->display_name) ? $author->display_name : '';
    } else if ( is_singular('post') ) {
      $author = get_the_author_meta( 'user_nicename' , $post->post_author );
    }

    $terms = array(
      'category' => '',
      'gallery-collection' => '',
      'post_tag' => '',
    );

    if ( ( is_singular('post')  || is_singular('photo-gallery') ) && $post ) {
      foreach ( array_keys($terms) as $taxonomy ) {
        // Just the slugs, please
        $terms[$taxonomy] = wp_get_object_terms( $post->ID, $taxonomy, array( 'fields' => 'slugs' ) );
        if ( is_array($terms[$taxonomy]) ) {
          $terms[$taxonomy] = $terms[$taxonomy];
        }
      }
    }

    $targets = [
      'is_front'            => is_front_page() ? 'true' : 'false',
      'is_archive'          => is_archive() ? 'true' : 'false',
      'is_article'          => is_singular( 'post' ) ? 'true' : 'false',
      'is_gallery'          => is_singular( 'photo-gallery' ) ? 'true' : 'false',
      'is_page'             => is_page() && ! is_front_page() ? 'true' : 'false',
      'is_search'           => is_search() ? 'true' : 'false',
      'is_author'           => is_author() ? 'true' : 'false',
      'slug'                => $post ? $post->post_name : '',
      'category'            => is_category() && $term ? $term : $terms['category'],
      'gallery_collection'  => is_singular( 'photo-gallery' ) && $term ? $term : $terms['gallery-collection'],
      'tag'                 => is_tag() && $term ? $term->slug : $terms['post_tag'],
      'author'              => $author ? $author : '',
      'has_takeover'        => 'false',
      'takeover_id'         => '0',
      'has_parent'          => wp_get_post_parent_id( $post->ID ),
    ];

    $active_takeover = self::get_takeover_id();

    if( is_numeric( $active_takeover ) && get_post_meta( $active_takeover, 'takeover_ad_targeting', TRUE ) ) {
      $targets['has_takeover'] = true;
      $targets['takeover_id'] = "$active_takeover";
    }

    return $targets;
  }

  /**
   * Get all of the ad units that have been registered in the ad_unit
   * post type
   *
   * @since 1.0.0
   *
   * @return array|bool|mixed
   */
  static function get_ad_units() {
    $cache_key = 'ad_units';
    $cache_group = 'hbi-ad-manager';
    $ad_units_formatted = wp_cache_get( $cache_key, $cache_group );

    if( false === $ad_units_formatted ) :
      $ad_units_formatted = array();

      $ad_unit_args = array(
        'posts_per_page' => '-1',
        'nopaging' => 'true',
        'post_type' => 'ad_unit',
        'oderby' => 'title',
        'order' => 'DESC',
      );

      $dfp_ad_units = get_posts( $ad_unit_args );

      foreach( $dfp_ad_units as $dfp_ad_unit ) :
        $priority = get_post_meta( $dfp_ad_unit->ID, 'logical_priority', TRUE );
        $priority = ( !empty( $priority ) ) ? abs( $priority ) : 10;

        $operator = get_post_meta( $dfp_ad_unit->ID, 'operator', TRUE );
        $operator = ( !empty( $operator ) ) ? esc_attr( $operator ) : 'OR';

        $conditionals = get_post_meta( $dfp_ad_unit->ID, 'conditionals', TRUE );
        $conditionals = ( empty( $conditionals ) ) ? array() : $conditionals;
        $collapse = (  get_post_meta( $dfp_ad_unit->ID, 'collapse_empty_div', true ) ) ? 'true' : 'false';

        $tag_id = ( get_post_meta( $dfp_ad_unit->ID, 'tag_id', TRUE ) ) ? 'ad-unit-' . get_post_meta( $dfp_ad_unit->ID, 'tag_id', TRUE ) : 'ad-unit-' . $dfp_ad_unit->post_name;
        $ad_units_formatted[] = array(
          'conditionals' => $conditionals,
          'priority' => $priority,
          'operator' => $operator,
          'post_title' => $dfp_ad_unit->post_title,
          'post_name' => $dfp_ad_unit->post_name,
          'post_id' => $dfp_ad_unit->ID,
          'admap' => get_post_meta( $dfp_ad_unit->ID, 'admap_to_use', true ),
          'dfp_ad_unit' => get_post_meta( $dfp_ad_unit->ID, 'dfp_ad_unit', true ),
          'dfp_network_code' => get_post_meta( $dfp_ad_unit->ID, 'dfp_network_code', true ),
          'height' => get_post_meta( $dfp_ad_unit->ID, 'ad_height', true ),
          'width' => get_post_meta( $dfp_ad_unit->ID, 'ad_width', true ),
          'collapse' => $collapse,
          'tag_id' => $tag_id,
          'targeting_position' => get_post_meta( $dfp_ad_unit->ID, 'targeting_position', TRUE )
        );
      endforeach;

    /**
     * Sets the transient object cache
     */
    wp_cache_set( $cache_key, $ad_units_formatted, $cache_group, 3600 );

   endif;

    /**
     * Return the ad units to the function that calls this
     */
    return $ad_units_formatted;
  }

  public function get_matching_ad_units() {
    global $wp_query;

    $ad_units = array();
    /**
     * Grab the ad units from above and loop through them to see if they are needed
     */
    foreach( (array)$this->get_ad_units() as $ad_unit ) :
      /**
       * If no conditionals are set, then drop it in the array and bounce to the next
       */
      if( empty( $ad_unit['conditionals'] ) ) :
        $ad_units[] = $ad_unit;
        continue;
      endif;


      $display = true;

      /**
       * Loop through the conditionals and let's see if this guy is worth using or not.
       */
      foreach( $ad_unit['conditionals'] as $conditional ) :
        if ( is_array( $conditional ) ) {
          $conditional_function = $conditional['function'];
          if ( !empty( $conditional['arguments'] ) )
            $conditional_arguments = $conditional['arguments'];
          else
            $conditional_arguments = array();
          if ( isset( $conditional['result'] ) )
            $condition_result = $conditional['result'];
          else
            $condition_result = true;
        } else {
          $conditional_function = $conditional;
          $conditional_arguments = array();
          $condition_result = true;
        }

        // Taken from the ACM plugin, it's pretty damned smart, actually
        if ( 0 === strpos( $conditional_function, '!' ) ) {
          $conditional_function = ltrim( $conditional_function, '!' );
          $condition_result = false;
        }

        if ( !is_callable( $conditional_function ) || !in_array( $conditional_function, $this->conditional_functions ) )
          continue;

        // Run our conditional and use any arguments that were passed
        if ( !empty( $conditional_arguments ) ) {
          $result = call_user_func_array( $conditional_function, $conditional_arguments );
        } else {
          $result = call_user_func( $conditional_function );
        }

        // If our results don't match what we need, don't include this ad code
        if ( $condition_result !== $result )
          $display = false;
        else
          $display = true;

        // If we have matching conditional and $ad_code['operator'] equals OR just break from the loop and do not try to evaluate others
        if ( $display && $ad_unit['operator'] == 'OR' )
          break;

        // If $ad_code['operator'] equals AND and one conditional evaluates false, skip this ad code
        if ( !$display && $ad_unit['operator'] == 'AND' )
          break;
      endforeach;

      /**
       * If we made it through all of the conditionals and we haven't hit anything yet, then add it to the array
       */
      if( $display )
        $ad_units[] = $ad_unit;

    endforeach;

    // Don't do anything if we've ended up with no ad codes
    if ( empty( $ad_units ) )
      return;

    // Prioritize the display of the ad codes based on
    // the priority argument for the ad code
    $prioritized_ad_units = array();

    foreach ( $ad_units as $ad_unit ) {
      $priority = $ad_unit['priority'];
      $prioritized_ad_units[$priority][] = $ad_unit;
    }

    ksort( $prioritized_ad_units, SORT_NUMERIC );

    $shifted_prioritized_ad_units = array_shift( $prioritized_ad_units );

    return $shifted_prioritized_ad_units;
  }

  /**
   * Register the Ad Unit widget to display an ad unit in the sidebar of any
   * website.
   *
   * @since   1.0.0
   */
  public function register_display_dfp_ads_widget() {
    register_widget( 'DFP_Ad_Unit' );
  }

  /**
   * Load the module for Beaver Builder
   *
   * @since		2.0.0
   */
  function load_bb_module() {
      include_once( HBI_AD_DIR . 'bb-modules/ad-unit/ad-unit.php' );
      //include_once( HBI_AD_DIR . 'bb-modules/dfp-content-grid/dfp-content-grid.php' );
  }

  /**
   * Generate the HTML code to be rendered to the browser based on the ad unit
   * provided. This function checks to see if the website is requesting
   * asynchronous ad units or not. Async loading is almost always recommended.
   *
   * This function accepts a post object, post ID, or post title
   *
   * @param   string    $ad_unit    The ad unit that is trying to be displayed
   * @return  string    $ad_code    The actual HTML code to render the ad unit
   *
   * @since   3.0.0
   */
  static function generate_ad_code_html( $ad_unit = NULL ) {
    if( !is_object( $ad_unit ) ) {
      if( is_numeric( $ad_unit ) ) {
        $ad_unit = get_post( $ad_unit );
      } else {
        $ad_unit = get_page_by_title( $ad_unit, 'OBJECT', 'ad_unit' );
      }
    }

    if( $ad_unit == NULL || empty( $ad_unit ) )
      return;

    ob_start();
    $options =  get_option('hbi_ad_manager_settings');

    $ad_unit_id_tag = ( get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) ) ? get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) : $ad_unit->post_name;

    if( isset( $_GET['fl_builder'] ) ) {
      $ad_height = get_post_meta( $ad_unit->ID, 'ad_height', TRUE );
      $ad_width = get_post_meta( $ad_unit->ID, 'ad_width', TRUE );
      ?>
      <div class="admin_advertising_display">
        <?php echo $ad_unit->post_title; ?> (<?php echo $ad_width; ?>x<?php echo $ad_height; ?>)
      </div>
      <?php } else { ?>
      <div id="ad-unit-<?php echo $ad_unit_id_tag; ?>">
        <script type='text/javascript'>
          <?php if( 1 === $options['async_rendering'] ) : ?> googletag.cmd.push(function () { <?php endif; ?>
            googletag.display('ad-unit-<?php echo $ad_unit_id_tag; ?>');
            <?php if( 1 === $options['async_rendering'] ) : ?> }); <?php endif; ?>
        </script>
      </div>
      <?php
    }
    $ad_code = ob_get_clean();
    return str_replace(array("\r", "\n"), '', $ad_code );
  }

  /**
   * Returns an array of post ids that have their
   * location set to the current page.
   *
   * @since 1.0
   * @access private
   * @return array
   */
  function _get_takeover_current_page_posts() {
    // This function was jacked from plugins/bb-theme-builder/classes/class-fl-theme-builder-rules-locations.php
    global $wpdb;

    self::$current_page_posts = array();

    $data       = FLThemeBuilderRulesLocation::get_current_page_location();
    $location   = esc_sql( $data['location'] );
    $meta_query = "pm.meta_value LIKE '%\"{$location}\"%' OR pm.meta_value LIKE '%\"general:site\"%'";
    $query      = "SELECT p.ID, pm.meta_value FROM {$wpdb->postmeta} as pm
					   INNER JOIN {$wpdb->posts} as p ON pm.post_id = p.ID
					   WHERE pm.meta_key = '_fl_theme_builder_locations'
					   AND p.post_type = 'takeover'
					   AND p.post_status = 'publish'";

    if ( $data['object'] ) {
      $object      = esc_sql( $data['object'] );
      $meta_query .= " OR pm.meta_value LIKE '%\"{$object}\"%'";
    }
    if ( is_archive() || is_home() || is_search() ) {
      $meta_query .= " OR pm.meta_value LIKE '%\"general:archive\"%'";
    }
    if ( is_singular() ) {
      $meta_query .= " OR pm.meta_value RLIKE '\"{$location}:post:.*\"'";
      $meta_query .= " OR pm.meta_value RLIKE '\"{$location}:taxonomy:.*\"'";
      $meta_query .= " OR pm.meta_value LIKE '%\"general:single\"%'";
    }

    // cache query
    $query = $query . ' AND (' . $meta_query . ')';

    $query = $wpdb->get_results( $query );

    foreach ( $query as $post ) {
      self::$current_page_posts[ $post->ID ] = array(
        'id'        => $post->ID,
        'locations' => unserialize( $post->meta_value ),
      );
    }

    self::_exclude_current_page_posts();

    return self::$current_page_posts;
  }

  static public function get_saved_exclusions( $post_id, $sorted = false ) {
    // This function was jacked from plugins/bb-theme-builder/classes/class-fl-theme-builder-rules-locations.php

    $saved = get_post_meta( $post_id, '_fl_theme_builder_exclusions', true );
    $saved = ! $saved ? array() : $saved;
    $saved = ! $sorted ? $saved : self::sort_saved( $saved );

    return $saved;
  }

  /**
   * Excludes posts from the current page if they have
   * any matching exclusion rules.
   *
   * @since 1.0
   * @access private
   */
  static private function _exclude_current_page_posts() {
    // This function was jacked from plugins/bb-theme-builder/classes/class-fl-theme-builder-rules-locations.php

    $post_id  = get_the_ID();
    $location = FLThemeBuilderRulesLocation::get_current_page_location();

    foreach ( self::$current_page_posts as $i => $post ) {

      $exclusions = self::get_saved_exclusions( $post['id'] );
      $exclude    = false;

      if ( empty( $exclusions ) ) {
        continue;
      } elseif ( 'general:404' == $location['location'] && in_array( 'general:404', $exclusions ) ) {
        $exclude = '404' != get_post_meta( $post['id'], '_fl_theme_layout_type', true );
      } elseif ( $location['object'] && in_array( $location['object'], $exclusions ) ) {
        $exclude = true;
      } elseif ( in_array( $location['location'], $exclusions ) ) {
        $exclude = true;
      } else {
        foreach ( $exclusions as $exclusion ) {
          if ( is_archive() || is_home() ) {
            if ( 'general:archive' == $exclusion ) {
              $exclude = true;
            }
          } elseif ( is_singular() ) {
            if ( 'general:single' == $exclusion ) {
              $exclude = true;
            } elseif ( strstr( $exclusion, ':taxonomy:' ) ) {
              $parts = explode( ':', $exclusion );
              if ( ( 4 === count( $parts ) && has_term( '', $parts[3] ) ) || has_term( $parts[4], $parts[3] ) ) {
                $exclude = true;
              }
            } elseif ( stristr( $exclusion, ':post:' ) ) {
              $parts = explode( ':', $exclusion );
              if ( 5 === count( $parts ) && wp_get_post_parent_id( $post_id ) == $parts[4] ) {
                $exclude = true;
              }
            }
          }
        }
      }

      if ( $exclude ) {
        unset( self::$current_page_posts[ $i ] );
      }
    }
  }

  private function get_takeover_id() {
    if( class_exists( 'FLThemeBuilderLoader' ) ) {
      $active_takeover = self::_get_takeover_current_page_posts();

      if ( empty( $active_takeover ) )
        return null;

      $takeover_id = '';
      foreach( $active_takeover as $post_id => $data ) {
        if( $takeover_id !== '' )
          continue;

        $today = current_time( 'Y-m-d' );
        $start = get_post_meta( $post_id, 'takeover_start', TRUE );
        $end = get_post_meta( $post_id, 'takeover_end', TRUE );

        if( $today >= $start && $today <= $end ) {
          $takeover_id = $post_id;
        }
      }

      return $takeover_id;
    }
    else {
      $takeovers = get_posts( [ 'post_type' => 'takeover' ] );

      if( !empty( $takeovers ) ) {
        return $takeovers[0]->ID;
      }
    }

    return null;
  }

  public function render_bb_takeover() {
    $active_takeover = self::get_takeover_id();

    if( is_numeric( $active_takeover ) ) {

      $takeover_background = get_post_meta($active_takeover, 'takeover_background', TRUE);
      ?> <style> body.has_takeover.takeover-<?php echo $active_takeover; ?> { background-image: url('<?php echo esc_url( $takeover_background );  ?>') !important; }</style> <?php
    }
  }

  public function set_body_class_on_takeover( $classes ) {
    $active_takeover = self::get_takeover_id();
    if( is_numeric( $active_takeover ) ) {
      $classes[] = 'has_takeover';
      $classes[] = "takeover-{$active_takeover}";
    }
    return $classes;
  }

  function add_dfp_add_targeting_toolbar( $wp_admin_bar ) {
    $args = array(
      'id'    => 'dfp_ad_targeting',
      'title' => 'DFP Ad Targeting',
      'href'  => "#",
    );

    $wp_admin_bar->add_node( $args );

    $wp_admin_bar->add_group(
      [
        'id' => 'dfp_ad_targets',
        'parent' => 'dfp_ad_targeting',
      ]
    );
  }
}