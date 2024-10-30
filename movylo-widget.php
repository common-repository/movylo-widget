<?php
/*
	Plugin Name: Movylo Marketing Automation
	Version: 2.0.7
	Description: Automated Customer Engagement and Sales Booster for local businesses. Build your Customer List by capturing leads from your Wordpress website (and also from Facebook, GMB, Instagram, and many more) and then automatically convert the list into real sales (in-store and/or online) with Movylo.
	Author: movylo
	Text Domain: movylo-widget
*/

/**
 *  Make sure the plugin is accessed through the appropriate channels
 */
defined( 'ABSPATH' ) || die;

/**
 * The current version of the Plugin.
 */
define( 'MOVYLO', '2.0.7' );

// Plugin URL
define( 'MOVYLO_URL', plugin_dir_url( __FILE__ ) );

/**
* Including files
*/
require_once( plugin_dir_path( __FILE__ ) . '/includes/movylo-main.php' );

?>
