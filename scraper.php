<?php
/*
* Plugin Name: External Website Scraper
* Description: This plugin scrapes products from external website and automate the process.
* Author: Muhammad Kashif
* Text Domain: scraper
* Version: 1.1.0
*/

// Make sure we don't expose any info if called directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'SC_PLUGIN_DIR_URL', __FILE__ );
// require_once __DIR__ . '/vendor/autoload.php';

function includeScrapingAssets(){
    wp_enqueue_style( 'scrape-styles', plugins_url( '/assets/css/scrape.css', __FILE__ ) );
    
	wp_enqueue_script( 'scrape-scripts', plugins_url( '/assets/js/scrape.js', __FILE__ ), array('jquery') );
	wp_enqueue_script( 'scrape-ui', plugins_url( '/assets/js/jquery.blockUI.js', __FILE__ ), array('jquery') );

	wp_localize_script(
		'scrape-scripts', 
		'ajax_object', 
		array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) 
	);
}
add_action('admin_enqueue_scripts', 'includeScrapingAssets');

require_once( SC_PLUGIN_DIR . 'includes/class.scrapeproducts.php' );
require_once( SC_PLUGIN_DIR . 'includes/functions.php' );
require_once( SC_PLUGIN_DIR . 'shortcodes/shortcode.php' );

add_filter( 'wp_terms_checklist_args', function( $args ) {
    $args['checked_ontop'] = false;
    return $args;
});

add_filter( 'cron_schedules', 'myprefix_add_weekly_cron_schedule' );
function myprefix_add_weekly_cron_schedule( $schedules ) {
	$options 	= get_option('hours_options');
	$hours 		= $options['scraper-field-hours'];
	
    $schedules['s_hours'] = array(
        'interval' => 60*60*$hours,
        'display'  => __( $hours.' hour(s)' ),
    );

    return $schedules;
}

add_action( 'update_option_hours_options', function($old_value, $value, $option){

    if( $old_value !== $value ){
		wp_schedule_event( time(), 's_hours', 'cron_simple_example_hook' );
    }

}, 10, 3 );

add_action( 'cron_simple_example_hook', 'cron_simple_example' );    //Add Action

function cron_simple_example()
{
	require_once SC_PLUGIN_DIR.'cronJobs/automatic.php';
}