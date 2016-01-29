<?php

/**
 * Fired during plugin activation
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      0.1
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/includes
 */
class HBI_Ad_Manager_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    0.1
	 */
	public static function activate() {
	    
        $default_conditionals = array( 
            'comments_open' => 0,
            'is_404' => 0,
            'is_admin' => 0,
            'is_admin_bar_showing' => 0,
            'is_archive' => 0,
            'is_attachment' => 0,
            'is_author' => 0,
            'is_category' => 1,
            'is_comments_popup' => 0,
            'is_date' => 0,
            'is_day' => 0,
            'is_feed' => 0,
            'is_front_page' => 1,
            'is_home' => 1,
            'is_local_attachment' => 0,
            'is_main_query' => 0,
            'is_multi_author' => 0,
            'is_month' => 0,
            'is_new_day' => 0,
            'is_page' => 1,
            'is_page_template' => 0,
            'is_paged' => 0,
            'is_plugin_active' => 0,
            'is_plugin_active_for_network' => 0,
            'is_plugin_inactive' => 0,
            'is_plugin_page' => 0,
            'is_post_type_archive' => 0,
            'is_preview' => 0,
            'is_search' => 0,
            'is_single' => 1,
            'is_singular' => 0,
            'is_sticky' => 0,
            'is_tag' => 1,
            'is_tax' => 0,
            'is_taxonomy_hierarchical' => 0,
            'is_time' => 0,
            'is_trackback' => 0,
            'is_year' => 0,
            'in_category' => 0,
            'in_the_loop' => 0,
            'is_active_sidebar' => 0,
            'is_active_widget' => 0,
            'is_blog_installed' => 0,
            'is_rtl' => 0,
            'is_dynamic_sidebar' => 0,
            'is_user_logged_in' => 0,
            'has_excerpt' => 0,
            'has_category' => 1,
            'has_post_thumbnail' => 0,
            'has_tag' => 1,
            'pings_open' => 0,
            'email exists' => 0,
            'post_type_exists' => 0,
            'taxonomy_exists' => 0,
            'term_exists' => 0,
            'username exists' => 0,
            'wp_attachment_is_image' => 0,
            'wp_script_is' => 0,
        );
        
        $defaults = array( 
            'single_request' => 0, 
            'async_rendering' => 0,
            'active_conditionals' => $default_conditionals
        );
        
        $result = add_option( 'hbi_ad_manager_settings', $defaults );
	}
}