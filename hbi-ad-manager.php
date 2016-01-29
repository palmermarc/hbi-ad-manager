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

require_once( 'github-plugin-updater.php' );
if( is_admin() ) {
    new GitHubPluginUpdater( __FILE__, 'palmermarc', "hbi-ad-manager" );
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
