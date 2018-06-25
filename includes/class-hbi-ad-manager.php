<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the dashboard.
 *
 * @link       http://example.com
 * @since      0.1
 *
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, dashboard-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.1
 * @package    HBI_Ad_Manager
 * @subpackage HBI_Ad_Manager/includes
 * @author     Marc Palmer
 */
class HBI_Ad_Manager {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    0.1
	 * @access   protected
	 * @var      HBI_Ad_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    0.1
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    0.1
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the Dashboard and
	 * the public-facing side of the site.
	 *
	 * @since    0.1
	 */
	public function __construct() {

		$this->plugin_name = 'hbi-ad-manager';
		$this->version = '2.0.1';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
    $this->define_post_type_hooks();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - HBI_Ad_Manager_Loader. Orchestrates the hooks of the plugin.
	 * - HBI_Ad_Manager_i18n. Defines internationalization functionality.
	 * - HBI_Ad_Manager_Admin. Defines all hooks for the dashboard.
	 * - HBI_Ad_Manager_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    0.1
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hbi-ad-manager-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-hbi-ad-manager-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the Dashboard.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-hbi-ad-manager-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hbi-ad-manager-public.php';

    /**
     * The class responsible for defining all actions that occur involving post-type
     * actions of the site.
     */
    require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-hbi-ad-manager-post-types.php';

		$this->loader = new HBI_Ad_Manager_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the HBI_Ad_Manager_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    0.1
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new HBI_Ad_Manager_i18n();
		$plugin_i18n->set_domain( $this->get_plugin_name() );

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the dashboard functionality
	 * of the plugin.
	 *
	 * @since    0.1
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new HBI_Ad_Manager_Admin( $this->get_plugin_name(), $this->get_version() );

    /* Add the necessary style and script files in the admin */
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
        
    /* Update the "Enter Title Here" placeholder */
    $this->loader->add_action( 'enter_title_here', $plugin_admin, 'custom_ad_unit_title' );

    /* Register the settings page */
    $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_hbi_ad_manager_options_page' );
    $this->loader->add_action( 'admin_init', $plugin_admin, 'register_hbi_ad_manager_settings' );

    /* Register the metabox */
    $this->loader->add_action( 'load-post.php', $plugin_admin, 'hbi_ad_manager_register_meta_boxes');
    $this->loader->add_action( 'load-post-new.php', $plugin_admin, 'hbi_ad_manager_register_meta_boxes');

    /* Register and display the custom columns */
    $this->loader->add_action( 'admap_edit_form_fields', $plugin_admin, 'display_admap_custom_fields' );
    $this->loader->add_action( 'edited_admap', $plugin_admin, 'save_admap_custom_fields', 10, 2 );

    /* Register the custom columns and display their values */
    $this->loader->add_filter( 'manage_edit-ad_unit_columns', $plugin_admin, 'set_custom_ad_unit_columns' );
    $this->loader->add_action( 'manage_ad_unit_posts_custom_column', $plugin_admin, 'custom_ad_unit_column', 10, 2 );
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    0.1
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new HBI_Ad_Manager_Public( $this->get_plugin_name(), $this->get_version() );
        
    /* Display the ad units into the WordPress Header */
    $this->loader->add_action( 'wp_head', $plugin_public, 'inject_hbi_ad_manager_into_header' );

    /* Register the DFP Ad Unit widget */
    $this->loader->add_action( 'widgets_init', $plugin_public, 'register_display_dfp_ads_widget' );
    $this->loader->add_action( 'init', $plugin_public, 'load_bb_module' );

    $this->loader->add_action( 'wp_head', $plugin_public, 'render_bb_takeover' );

    $this->loader->add_filter( 'body_class', $plugin_public, 'set_body_class_on_takeover' );

  }

  /**
   * Register all of the hooks related to the public-facing functionality
   * of the plugin.
   *
   * @since    0.1
   * @access   private
   */
  private function define_post_type_hooks() {
    $plugin_post_types = new HBI_Ad_Manager_Post_Types( $this->get_plugin_name(), $this->get_version() );

    /* Register the post type and taxonomies that the plugin requires */
    $this->loader->add_action( 'init', $plugin_post_types, 'register_ad_mapping' );
    $this->loader->add_action( 'init', $plugin_post_types, 'register_ad_units_post_type' );
  }

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    0.1
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     0.1
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     0.1
	 * @return    HBI_Ad_Manager_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     0.1
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
