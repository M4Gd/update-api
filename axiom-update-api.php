<?php
/**
 * Update API
 *
 *
 * @package   axiom
 * @author    averta
 * @license   GPL-2.0+
 * @copyright 2014 averta
 *
 * @wordpress-plugin
 * Plugin Name:       Averta Update API
 * Plugin URI:        https://github.com/M4Gd/update-api
 * Description:       A WordPress plugin that allows you to add update api to your WordPress website. 
 * Version:           1.0.0
 * Author:            averta
 * Author URI:        http://averta.net
 * Text Domain:       update-api
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/M4Gd/update-api
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
} // end if

require_once( plugin_dir_path( __FILE__ ) . 'class-axiom-update-api.php' );
add_action( 'plugins_loaded', array( 'AxiomUpdateAPI', 'get_instance' ) );