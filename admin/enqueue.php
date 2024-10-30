<?php


/* Load admin scripts and styles */
add_action( 'admin_enqueue_scripts', 'mks_map_get_cat_map_data' );

/**
 * Enqueue category scripts
 *
 * @since  1.0
 */

function mks_map_get_cat_map_data() {
	
	global $pagenow, $typenow;
	
	if ( !in_array( $pagenow, array( 'term.php', 'edit-tags.php', )) || !isset( $_GET['taxonomy'] ) || $_GET['taxonomy'] != 'category' ) {
		return false;
	}
	
	$general_settings = get_option( 'meks-easy-maps-general' );
	$google_settings = get_option( 'mks-map-settings' );
	$osm_settings = get_option( 'meks-easy-maps-osm' );

	if ( empty( $general_settings )  ) {
		$general_settings['map_source'] =  'google';
	}
	
	if ( $general_settings['map_source'] == 'google' && empty( $google_settings['api_key'] ) ) {
		return false;
	}
	
	if ( $general_settings['map_source'] == 'google' ) {

		wp_enqueue_script( 'mks-map-google-map-api-3', 'https://maps.google.com/maps/api/js?key=' . $google_settings['api_key'] . '&libraries=places', array(), true, MKS_MAP_VER );
		wp_enqueue_script( 'mks-map-admin-map-js', MKS_MAP_URL . 'admin/js/admin-map.js', array('jquery'), MKS_MAP_VER, true );
	}
	
	if ( $general_settings['map_source'] == 'osm' ) {
		
		wp_enqueue_style( 'mks-map-admin-css',  MKS_MAP_URL . 'admin/css/admin.css', false, MKS_MAP_VER );
		wp_enqueue_style( 'mks-map-leaflet-css',  MKS_MAP_URL . 'public/css/leaflet.css', false, MKS_MAP_VER );
		wp_enqueue_style( 'mks-map-leaflet-geocoder-css',  MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder.css', false, MKS_MAP_VER );
		
		wp_enqueue_script( 'mks-map-leaflet-js', MKS_MAP_URL . 'public/js/leaflet.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-js', MKS_MAP_URL . 'admin/js/esri-leaflet.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-geocoder-js', MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-geocoder-input-js', MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder-input.js', array(), MKS_MAP_VER, true );
		
		wp_enqueue_script( 'mks-map-admin-osm-js', MKS_MAP_URL . 'admin/js/admin-osm.js', array('jquery'), MKS_MAP_VER, true );
	}
}

/* Load admin scripts and styles */
add_action( 'admin_enqueue_scripts', 'mks_map_get_map_data' );

/**
 * Enqueue posts scripts
 *
 * @since  1.0
 */
function mks_map_get_map_data() {
	
	
	global $pagenow, $typenow;
	
	if( !in_array( $pagenow, array('post.php', 'post-new.php' ) ) || $typenow != 'post') {
		return false;
	}
	
	$general_settings = get_option( 'meks-easy-maps-general' );
	$google_settings = get_option( 'mks-map-settings' );
	$osm_settings = get_option( 'meks-easy-maps-osm' );

	if ( empty( $general_settings )  ) {
		$general_settings['map_source'] =  'google';
	}
	
	if ( $general_settings['map_source'] == 'google' && empty( $google_settings['api_key'] ) ) {
		return false;
	}
	
	if ( $general_settings['map_source'] == 'google' ) {

		wp_enqueue_script( 'mks-map-google-map-api-3', 'https://maps.google.com/maps/api/js?key=' . $google_settings['api_key'] . '&libraries=places', array(), true, MKS_MAP_VER );
		wp_enqueue_script( 'mks-map-admin-map-js', MKS_MAP_URL . 'admin/js/admin-map.js', array('jquery'), MKS_MAP_VER, true );
	}
	
	if ( $general_settings['map_source'] == 'osm' ) {

		wp_enqueue_style( 'mks-map-admin-css',  MKS_MAP_URL . 'admin/css/admin.css', false, MKS_MAP_VER );
		wp_enqueue_style( 'mks-map-leaflet-css',  MKS_MAP_URL . 'public/css/leaflet.css', false, MKS_MAP_VER );
		wp_enqueue_style( 'mks-map-leaflet-geocoder-css',  MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder.css', false, MKS_MAP_VER );
		
		wp_enqueue_script( 'mks-map-leaflet-js', MKS_MAP_URL . 'public/js/leaflet.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-js', MKS_MAP_URL . 'admin/js/esri-leaflet.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-geocoder-js', MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder.js', array(), MKS_MAP_VER, true );
		wp_enqueue_script( 'mks-map-esri-leaflet-geocoder-input-js', MKS_MAP_URL . 'admin/js/esri-leaflet-geocoder-input.js', array(), MKS_MAP_VER, true );
		
		wp_enqueue_script( 'mks-map-admin-osm-js', MKS_MAP_URL . 'admin/js/admin-osm.js', array('jquery'), MKS_MAP_VER, true );
	}
	
}
