<?php
/*
Plugin Name: Meks Easy Maps
Plugin URI: https://mekshq.com/plugin/easy-maps/
Description: Assign locations to your posts or categories and display it automatically with Google Maps.
Version: 2.1.4
Author: Meks
Author URI: https://mekshq.com
Domain Path: /languages
License: GPL3
*/


if ( ! defined( 'ABSPATH' ) ) {
    exit;
} // Exit if accessed directly


/* Define the plugin vars */
define( 'MKS_MAP_BASENAME', plugin_basename( __FILE__ ) );
define( 'MKS_MAP_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'MKS_MAP_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'MKS_MAP_VER', '2.1.3' );
define( 'MKS_MAP_TEXTDOMAIN', 'meks-easy-maps' );


/* Init Plugin */

add_action( 'plugins_loaded', 'mks_map_start_plugin' );

function mks_map_start_plugin() {
    
    /* Load translation */
     load_plugin_textdomain( 'meks-easy-maps', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
    require_once MKS_MAP_DIR . '/inc/helpers.php';

    if ( is_admin() ) {

        require_once MKS_MAP_DIR . 'admin/enqueue.php';
        require_once MKS_MAP_DIR . 'admin/post-metabox-map.php';
        require_once MKS_MAP_DIR . 'admin/category-metabox-map.php';

        $mks_map_display_settings_page = apply_filters( 'mks_map_modify_display_settings_page', true );

        if ( $mks_map_display_settings_page ) {
            require_once MKS_MAP_DIR . 'admin/settings-page.php';
            $mks_map_settings_page = new Mks_Map_Options_Page();
        }
    }

    if ( !is_admin() ) {
        require_once MKS_MAP_DIR . '/public/map.php';
    }
}


/* Add the plugin settings link */

add_filter('plugin_action_links_' . MKS_MAP_BASENAME, 'mks_map_add_settings_link' );

function mks_map_add_settings_link( $links ){
	
	$plugin_links = array(
		'<a href="' . esc_url(admin_url('options-general.php?page=meks-easy-maps')) . '">' . esc_html__('Settings', MKS_MAP_TEXTDOMAIN) . '</a>',
	);
	return array_merge( $links, $plugin_links );
}

