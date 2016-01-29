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
        
        add_shortcode( 'display_dfp_ad', array( $this, 'register_dfp_ad_shortcode' ) );
    }
    
    function register_ad_units_post_type() {

        $labels = array(
            'name'                => 'Ad Units',
            'singular_name'       => 'Ad Unit',
            'menu_name'           => 'Ad Units',
            'parent_item_colon'   => 'Parent Ad Unit:',
            'all_items'           => 'All Ad Units',
            'view_item'           => 'View Ad Unit',
            'add_new_item'        => 'Create New Ad Unit',
            'add_new'             => 'Create Ad Unit',
            'edit_item'           => 'Edit Ad Unit',
            'update_item'         => 'Update Ad Unit',
            'search_items'        => 'Search Ad Units',
            'not_found'           => 'Not found',
            'not_found_in_trash'  => 'Not found in Trash',
        );
        $args = array(
            'label'               => 'ad_unit',
            'description'         => 'Ad Units from Google DFP - previously known as Ad Slots',
            'labels'              => $labels,
            'supports'            => array( 'title' ),
            'hierarchical'        => false,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'show_in_nav_menus'   => true,
            'show_in_admin_bar'   => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-chart-line',
            'can_export'          => true,
            'has_archive'         => false,
            'exclude_from_search' => true,
            'publicly_queryable'  => false,
            'capability_type'     => 'page',
        );
        
        register_post_type( 'ad_unit', $args );
    }

    public function inject_hbi_ad_manager_into_header() {
        $this->options = get_option('hbi_ad_manager_settings');
        
        if( 1 === $this->options['async_rendering'] ) 
            $this->print_asynchronous_header( $this->options );
        else
            $this->print_synchronous_header( $this->options );
    }

    public function get_ad_units() {
        $cache_key = 'ad_units';
        $cache_group = 'hbi-ad-manager';
        wp_cache_delete( $cache_key, $cache_group );
        $ad_units_formatted = wp_cache_get( $cache_key, $cache_group );
        
        if( false === $ad_units_formatted ) :
            $ad_units_formatted = array();
            
            $ad_unit_args = array(
                'posts_per_page' => '-1',
                'nopaging' => 'true',
                'post_type' => 'ad_unit',
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

    private function print_synchronous_header( $options ) {
        
        $admaps = get_terms( 'admap', array( 'hide_empty' => 0 ) );
        $single_request = ( 1 == $options['single_request'] ) ? "googletag.pubads().enableSingleRequest();\r\n" : "";
        
        $ad_units = $this->get_matching_ad_units();
        ?>
        <script type='text/javascript'>
            (function() {
            var useSSL = 'https:' == document.location.protocol;
            var src = (useSSL ? 'https:' : 'http:') +
            '//www.googletagservices.com/tag/js/gpt.js';
            document.write('<scr' + 'ipt src="' + src + '"></scr' + 'ipt>');
            })();
        </script>
        <script type="text/javascript">
            <?php 
            $admaps_used = array();
             if( !empty( $admaps ) ) :
                foreach( $admaps as $admap ) :
                    $slug_for_use = str_replace( '-', '_', $admap->slug );
                    $admaps_used[$admap->term_id] = $slug_for_use;
                    $term_meta = get_option( "taxonomy_term_".$admap->term_id );
                    echo "var $slug_for_use = googletag.sizeMapping().";
					if( !empty( $term_meta['admap_sizes'] ) ) :
	                    foreach( $term_meta['admap_sizes'] as $admap_size ) :
	                        $browser_size = $admap_size['browser_width'] . ', ' . $admap_size['browser_height'];
	                        $admap_ad_size = ( 0 == $admap_size['ad_width'] && 0 == $admap_size['ad_height'] ) ? '' : $admap_size['ad_width'] . ", "  . $admap_size['ad_height'];
	                        echo "addSize([$browser_size], [$admap_ad_size]).";
	                    endforeach;
                    endif;
                    
                    echo "build();\r\n";
                    
                endforeach;
                echo "\r\n";
            endif; 
            if( !empty( $ad_units ) ) :
                foreach( $ad_units as $ad_unit ) :
                    $collapse = 'setCollapseEmptyDiv(' . $ad_unit['collapse'] . ')';
                    $dfp_pos = ( '' != $ad_unit['targeting_position']) ? '.setTargeting("pos",["' . $ad_unit['targeting_position'] . '"])' : '';
                    $ad_size = $ad_unit['width'] . ', ' . $ad_unit['height'];
                    $admap_used = '';
                    if( !empty( $ad_unit['dfp_ad_unit'] ) ) {
                        if( 0 != absint( $ad_unit['admap'] ) ) {
                        	$admap = $ad_unit['admap'] ;
                        	$admap_used = "defineSizeMapping(" . $admaps_used[$admap] . ").";
                        }
                            
                        echo "googletag.defineSlot('/$ad_unit[dfp_network_code]/$ad_unit[dfp_ad_unit]', [$ad_size], '$ad_unit[tag_id]')." . $admap_used . $collapse . ".addService(googletag.pubads())" . $dfp_pos . ";\r\n";
                    }
                        
                endforeach;
            endif; 
            echo "googletag.pubads().enableSyncRendering();\r\n";
            echo $single_request; ?>
            googletag.enableServices();
        </script>
        <?php
    }

    private function print_asynchronous_header( $options ) {
        $admaps = get_terms( 'admap', array( 'hide_empty' => 0 ) );
        $single_request = ( 1 == $options['single_request'] ) ? "googletag.pubads().enableSingleRequest();\r\n" : "";
        
        $ad_unit_args = array(
            'posts_per_page' => '-1',
            'nopaging' => 'true',
            'post_type' => 'ad_unit',
            'order' => 'DESC',
            'orderby' => 'meta_value_num',
            'meta_key' => 'admap_to_use'
        );
        
        $ad_units = $this->get_matching_ad_units();
        ?>
        <script type="text/javascript">
            var googletag = googletag || {};
            googletag.cmd = googletag.cmd || [];
            (function() {
                var gads = document.createElement("script");
                gads.async = true;
                gads.type = "text/javascript";
                var useSSL = "https:" == document.location.protocol;
                gads.src = (useSSL ? "https:" : "http:") + "//www.googletagservices.com/tag/js/gpt.js";
                var node =document.getElementsByTagName("script")[0];
                node.parentNode.insertBefore(gads, node);
            })();
        </script>
        <script>
            googletag.cmd.push(function() {
                <?php 
                $admaps_used = array();
                if( !empty( $admaps ) ) :
                    foreach( $admaps as $admap ) :
                        $slug_for_use = str_replace( '-', '_', $admap->slug );
                        $admaps_used[$admap->term_id] = $slug_for_use;
                        $term_meta = get_option( "taxonomy_term_$admap->term_id" );
                        
                        echo "var $slug_for_use = googletag.sizeMapping().";
                        
                        foreach( $term_meta['admap_sizes'] as $admap_size ) :
							$browser_size = $admap_size['browser_width'] . ', ' . $admap_size['browser_height'];
                            $admap_ad_size = ( 0 == $admap_size['ad_width'] && 0 == $admap_size['ad_height'] ) ? '' : $admap_size['ad_width'] . ", "  . $admap_size['ad_height'];
                            echo "addSize([$browser_size], [$admap_ad_size]).";
                        endforeach;
                        
                        echo "build();\r\n";
                        
                    endforeach;
                    echo "\r\n";
                endif; 
                if( !empty( $ad_units ) ) :
                    foreach( $ad_units as $ad_unit ) :
                        $collapse = 'setCollapseEmptyDiv(' . $ad_unit['collapse'] . ')';
                        $dfp_pos = ( '' != $ad_unit['targeting_position']) ? '.setTargeting("pos",["' . $ad_unit['targeting_position'] . '"])' : '';
                        $ad_size = $ad_unit['width'] . ', ' . $ad_unit['height'];
                        $admap_used = '';
                        if( !empty( $ad_unit['dfp_ad_unit'] ) ) {
                            
                            if( 0 != absint( $ad_unit['admap'] ) ) {
                                $admap_used = "defineSizeMapping(" . $admaps_used[$admap] . ").";
                            }
                            
                            echo "googletag.defineSlot('/$ad_unit[dfp_network_code]/$ad_unit[dfp_ad_unit]', [$ad_size], '$ad_unit[tag_id]')." . $admap_used . $collapse . ".addService(googletag.pubads())" . $dfp_pos . ";\r\n";
                        }
                            
                    endforeach;
                endif; 
                echo $single_request; ?>
                googletag.enableServices();
            });
        </script>
        <?php
    }
    
    function register_ad_mapping() {
        $labels = array(
            'name'                       => 'Ad Maps',
            'singular_name'              => 'Ad Map',
            'menu_name'                  => 'DFP Ad Maps',
            'all_items'                  => 'All Ad Maps',
            'parent_item'                => 'Parent Ad Map',
            'parent_item_colon'          => 'Parent Ad Map:',
            'new_item_name'              => 'New Ad Map Name',
            'add_new_item'               => 'Add New Ad Map',
            'edit_item'                  => 'Edit Ad Map',
            'update_item'                => 'Update Ad Map',
            'separate_items_with_commas' => 'Separate ad maps with commas',
            'search_items'               => 'Search Ad Maps',
            'add_or_remove_items'        => 'Add or remove ad maps',
            'choose_from_most_used'      => 'Choose from the most used ad maps',
            'not_found'                  => 'Not Found',
        );
        
        $args = array(
            'labels'                     => $labels,
            'hierarchical'               => false,
            'public'                     => true,
            'show_ui'                    => true,
            'show_admin_column'          => true,
            'show_in_nav_menus'          => false,
            'show_tagcloud'              => false,
        );
        
        register_taxonomy( 'admap', array( 'ad_unit' ), $args );
    }

    function register_dfp_ad_shortcode( $atts ) {
        extract( shortcode_atts( array( 'adunit_id' => false, 'ad_zone' => false ), $atts ) );
        
        if( !$adunit_id && !$ad_zone )
            return;
        
        if( 0 != $adunit_id && $ad_zone == false ) 
            $ad_zone = get_the_title( $adunit_id );
        
        $ad_unit = get_page_by_title( $ad_zone, 'OBJECT', 'ad_unit' );
        
        $this->options = get_option('hbi_ad_manager_settings');
        
        if( 1 === $this->options['async_rendering'] ) 
            return $this->generate_asynchronous_ad_code_for_display( $ad_unit );
        else
            return $this->generate_synchronous_ad_code_for_display( $ad_unit );
    }
    
    static function generate_synchronous_ad_code_for_display( $ad_unit = NULL ) {
        if( $ad_unit == NULL )
            return;
        ob_start();
        
        $ad_unit_id = ( get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) ) ? get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) : $ad_unit->post_name; 
        ?>
        <div id="ad-unit-<?php echo $ad_unit_id; ?>">
            <script type='text/javascript'>
                googletag.display('ad-unit-<?php echo $ad_unit_id; ?>');
            </script>
        </div>
        <?php
        $ad_code = ob_get_clean();
        return $ad_code;
    }
    
    static function generate_asynchronous_ad_code_for_display( $ad_unit = NULL ) {
        if( $ad_unit == NULL )
            return;
        ob_start();
        $ad_unit_id = ( get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) ) ? get_post_meta( $ad_unit->ID, 'tag_id', TRUE ) : $ad_unit->post_name; 
        ?>
        <div id="ad-unit-<?php echo $ad_unit_id; ?>">
            <script type='text/javascript'>
                googletag.cmd.push(function() { googletag.display('ad-unit-<?php echo $ad_unit_id; ?>'); });
            </script>
        </div>
        <?php
        $ad_code = ob_get_clean();
        return $ad_code;
    }
    
    function register_display_dfp_ads_widget() {
        register_widget( 'DFP_Ad_Unit' );
    }
    
}