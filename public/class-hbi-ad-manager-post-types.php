<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://hubbardradio.com
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
class HBI_Ad_Manager_Post_Types {

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
   * Initialize the class and set its properties.
   *
   * @since    0.1
   * @var      string    $plugin_name       The name of the plugin.
   * @var      string    $version    The version of this plugin.
   */
  public function __construct( $plugin_name, $version ) {

    $this->plugin_name = $plugin_name;
    $this->version = $version;

  }

  /**
   * Register the necessary ad units for the plugin. The ad units present are `ad_unit` and `takeover`.
   *
   * An Ad Unit is created in Google DFP, and all information in single ad_unit posts contain information
   * that can only come from Google DFP.
   *
   * Takeovers are custom elements on a website that allow the users to create custom backgrounds based
   * on a multitude of targeting elements. These backgrounds can be timed to make sure that they are only
   * displayed during certain times. You can also set Google DFP Ad Targeting to target ads on to the page
   * inside of DFP, so that the website can have takeover-specific ads for the client/campaign.
   *
   * @since         1.0.0
   * @last_update   3.0.0
   */
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


    $takeover_labels = array(
      'name'                  => 'Takeovers',
      'singular_name'         => 'Takeover',
      'menu_name'             => 'Takeovers',
      'name_admin_bar'        => 'Takeover',
      'archives'              => 'Takeover Archives',
      'attributes'            => 'Takeover Attributes',
      'parent_item_colon'     => 'Parent Takeover:',
      'all_items'             => 'All Takeovers',
      'add_new_item'          => 'Add New Takeover',
      'add_new'               => 'Add New',
      'new_item'              => 'New Takeover',
      'edit_item'             => 'Edit Takeover',
      'update_item'           => 'Update Takeover',
      'view_item'             => 'View Takeover',
      'view_items'            => 'View Takeovers',
      'search_items'          => 'Search Takeover',
      'not_found'             => 'Not found',
      'not_found_in_trash'    => 'Not found in Trash',
      'featured_image'        => 'Featured Image',
      'set_featured_image'    => 'Set featured image',
      'remove_featured_image' => 'Remove featured image',
      'use_featured_image'    => 'Use as featured image',
      'insert_into_item'      => 'Insert into takeover',
      'uploaded_to_this_item' => 'Uploaded to this takeover',
      'items_list'            => 'Takeovers list',
      'items_list_navigation' => 'Takeovers list navigation',
      'filter_items_list'     => 'Filter takeovers list',
    );
    $takeover_args = array(
      'label'                 => 'Takeover',
      'description'           => 'Takeovers with custom backgrounds',
      'labels'                => $takeover_labels,
      'supports'              => array( 'title' ),
      'hierarchical'          => false,
      'public'                => true,
      'show_ui'               => true,
      'show_in_menu'          => true,
      'menu_position'         => 60,
      'menu_icon'             => 'dashicons-desktop',
      'show_in_admin_bar'     => false,
      'show_in_nav_menus'     => false,
      'can_export'            => true,
      'has_archive'           => false,
      'exclude_from_search'   => true,
      'publicly_queryable'    => true,
      'rewrite'               => false,
      'capability_type'       => 'page',
    );
    register_post_type( 'takeover', $takeover_args );
  }

  /**
   * Register the Ad Mapping taxonomy that's used when initializing the ad units in
   * the header. Ad Maps allow different sized ads to be pulled in to the website
   * based on the size of the browser, essentially creating responsive ad units.
   *
   * @since 2.0.0
   */
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

}