<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * Dashboard. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             0.1
 * @package           HBI_Ad_Manager
 *
 * @wordpress-plugin
 * Plugin Name:       HBI Ad Manager
 * Plugin URI:        http://www.hubbardradio.com
 * Description:       Adds custom ACM filters into the site
 * Version:           2.0.2
 * Author:            Marc Palmer
 * Author URI:        http://www.hubardradio.com
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'HBI_AD_DIR', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hbi-ad-manager-activator.php';

/**
 * The code that runs during plugin deactivation.
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-hbi-ad-manager-deactivator.php';

/** This action is documented in includes/class-hbi-ad-manager-activator.php */
register_activation_hook( __FILE__, array( 'HBI_Ad_Manager_Activator', 'activate' ) );

/** This action is documented in includes/class-hbi-ad-manager-deactivator.php */
register_deactivation_hook( __FILE__, array( 'HBI_Ad_Manager_Deactivator', 'deactivate' ) );

/**
 * The core plugin class that is used to define internationalization,
 * dashboard-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-hbi-ad-manager.php';

/**
 * The widget that displays DFP ads in a sidebar 
 */
require plugin_dir_path( __FILE__ ) . 'includes/widgets/dfp_ad_widget.php';

if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin

  include_once 'updater.php';

  $config = array(
    'slug' => plugin_basename(__FILE__), // this is the slug of your plugin
    'proper_folder_name' => 'hbi-ad-manager', // this is the name of the folder your plugin lives in
    'api_url' => 'https://api.github.com/repos/palmermarc/hbi-ad-manager', // the GitHub API url of your GitHub repo
    'raw_url' => 'https://raw.github.com/palmermarc/hbi-ad-manager/master', // the GitHub raw url of your GitHub repo
    'github_url' => 'https://github.com/palmermarc/hbi-ad-manager', // the GitHub url of your GitHub repo
    'zip_url' => 'https://github.com/palmermarc/hbi-ad-manager/zipball/master', // the zip url of the GitHub repo
    'sslverify' => true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
    'requires' => '3.0', // which version of WordPress does your plugin require?
    'tested' => '4.8.3', // which version of WordPress is your plugin tested up to?
    'readme' => 'README.md', // which file to use as the readme for the version number
    'access_token' => '', // Access private repositories by authorizing under Appearance > GitHub Updates when this example plugin is installed
  );

  new WP_GitHub_Updater($config);
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    0.1
 */
function run_hbi_ad_manager() {

	$plugin = new HBI_Ad_Manager();
	$plugin->run();

}
run_hbi_ad_manager();
