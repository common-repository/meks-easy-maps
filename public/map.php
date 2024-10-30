<?php

add_action( 'wp_enqueue_scripts', 'mks_map_enqueue_front_scripts' );

/**
 * Enqueue fronted scripts
 *
 * @version 1.0.0
 * @return void
 */
if ( !function_exists( 'mks_map_enqueue_front_scripts' ) ) :

	function mks_map_enqueue_front_scripts() {

		//delete_option( 'meks-easy-maps-general' );
		$general_options = get_option( 'meks-easy-maps-general' );
		$google_options = get_option( 'mks-map-settings' );
		$osm_options = get_option( 'meks-easy-maps-osm' );

		$display_map = apply_filters( 'mks_map_display_map', true );

		if ( empty( $general_options )  ) {
			$general_options['map_source'] =  'google';
		}

		if ( !$display_map || ( $general_options['map_source'] == 'google' && empty( $google_options['api_key'] ) ) ) {
			return false;
		}

		wp_enqueue_style( 'mks-map-css',  MKS_MAP_URL . 'public/css/map.css', false, MKS_MAP_VER );

		if ( $general_options['map_source'] == 'google' ) {
			
			wp_enqueue_script( 'mks-map-google-map-api-3', 'https://maps.google.com/maps/api/js?key=' . $google_options['api_key'] , MKS_MAP_VER, true );
			wp_enqueue_script( 'mks-map-google-map-infoBox', MKS_MAP_URL . 'public/js/infoBox.js', array(),  MKS_MAP_VER, true );
			wp_enqueue_script( 'mks-map-google-map-markerClusterer', MKS_MAP_URL . 'public/js/markerClusterer.js', array(), MKS_MAP_VER, true );
			
			wp_enqueue_script( 'mks-map-js', MKS_MAP_URL . 'public/js/main.js', array( 'jquery', 'mks-map-google-map-infoBox', 'mks-map-google-map-markerClusterer' ), MKS_MAP_VER, true );
		}
		
		if ( $general_options['map_source'] == 'osm' ) {
			
			wp_enqueue_style( 'mks-map-leaflet-css',  MKS_MAP_URL . 'public/css/leaflet.css', false, MKS_MAP_VER );
			wp_enqueue_style( 'mks-map-leaflet-marker-cluster-default-css',  MKS_MAP_URL . 'public/css/MarkerCluster.Default.css', false, MKS_MAP_VER );
			wp_enqueue_style( 'mks-map-leaflet-marker-cluster-css',  MKS_MAP_URL . 'public/css/MarkerCluster.css', false, MKS_MAP_VER );

			wp_enqueue_script( 'mks-map-leaflet-js', MKS_MAP_URL . 'public/js/leaflet.js', array( 'jquery' ), MKS_MAP_VER, true );
			wp_enqueue_script( 'mks-map-leaflet-markercluster-js', MKS_MAP_URL . 'public/js/leaflet.markercluster.js', array( 'jquery' ), MKS_MAP_VER, true );
			
			wp_enqueue_script( 'mks-map-osm-js', MKS_MAP_URL . 'public/js/main-osm.js', array( 'jquery' ), MKS_MAP_VER, true );
		}
		
	}

endif;

/**
 * Add map on content with the_content filter
 *
 * @version 1.0
 *
 * @param string  $content
 * @return string
 */
function mks_map_content_filter( $content ) {
	$settings =  mks_map_get_settings();

	if ( !is_single() || $settings['single_map'] == 'none' ) {
		return $content;
	}

	$item = mks_map_get_single_post();

	if ( !empty( $item ) ) {
		$settings['infoBox'] = 0;
		$settings = apply_filters( 'mks_map_modify_single_content_filter_settings', $settings );

		return ( $settings['single_map'] == 'above' ) ? mks_map_render( $settings, $item ) . $content : $content . mks_map_render( $settings, $item );
	}

	return $content;
}
add_filter( 'the_content', 'mks_map_content_filter' );

/**
 * Add map below category description
 *
 * @version 1.0
 *
 *
 * @param unknown $description
 * @return string
 */
function mks_map_category_content_filter( $description ) {
	if ( is_category() ) {
		$settings = mks_map_get_settings();

		if ( $settings['category_map'] == 'none' ) {
			return $description;
		}

		$cat_id = get_queried_object_id();

		$items = array();

		if ( $settings['category_map'] == 'posts' ) {
			$query = new WP_Query( mks_map_get_query( array(
						'posts_per_page' => -1,
						'cat' => $cat_id
					) ) );

			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$items[] = mks_map_parse_item( mks_map_get_loop_post()  );
				}
			}
			wp_reset_postdata();
		}else {
			$category = get_category( $cat_id );
			$cat_item = mks_map_get_loop_category( $category );
			$settings['infoBox'] = 0;
			if ( !empty( $cat_item['address'] ) ) {
				$items[] = $cat_item;
			}
		}

		if ( empty( $items ) ) {
			return $description;
		}

		return $description . mks_map_render( $settings, $items );

	}
	return $description;
}
add_filter( 'category_description',  'mks_map_category_content_filter' );

/**
 * Shortcode for displaying map wherever you need it
 *
 * @version 1.0
 *
 * @param unknown $atts
 * @return string
 */
function mks_map_shortcode( $atts ) {
	$atts = shortcode_atts( array(
			'type' => 'posts',
			'cat' => ''
		), $atts, 'mks_map' );

	$settings = mks_map_get_settings();
	$items = array();

	if ( $atts['type'] ==  'categories' ) {
		$categories = get_categories( mks_map_get_query( ) );
		foreach ( $categories as $category ) {
			$items[] = mks_map_get_loop_category( $category );
		}
	} else if ( $atts['type'] == 'posts' ) {
			$args = array(
				'posts_per_page' => -1
			);

			if ( !empty( $atts['cat'] ) ) {
				$args['cat'] = $atts['cat'];
			}

			$query = new WP_Query( mks_map_get_query( $args ) );
			if ( $query->have_posts() ) {
				while ( $query->have_posts() ) {
					$query->the_post();

					$items[] = mks_map_get_loop_post();
				}
			}
			wp_reset_postdata();
		}

	return mks_map_render( $settings, $items );
}
add_shortcode( 'mks_map', 'mks_map_shortcode' );


/**
 * Add html wrapper for global map
 *
 * @version 1.0.0
 * @return string
 */
if ( !function_exists( 'mks_map_render' ) ) :
	function mks_map_render( $map_settings, $items ) {

		global $mks_map_div_id; //global map id (we increment it each time so each map on the page has a unique id)

		$error = '';

		// if ( empty( $map_settings ) ) {
		// 	$error = esc_html__( 'Something went wrong, reload and check the plugin settings.', MKS_MAP_TEXTDOMAIN );
		// }

		// if ( empty( $map_settings['api_key'] ) ) {
		// 	$error = wp_kses_post( sprintf( __( 'Google Maps API key is required to display the map. Please provide your API key in the <a href="%s">plugin settings</a>.' ),  admin_url( 'options-general.php?page=meks-easy-maps' ) ) );
		// }

		// if ( empty( $items ) ) {
		// 	$error = esc_html__( 'In order to see the map you need to have at least one post with a location.', MKS_MAP_TEXTDOMAIN );
		// }

		// if ( !empty( $error ) ) {
		// 	return '<div id="mks-maps-error">' . $error . '</div>';
		// }

		unset( $map_settings['api_key'] );
		unset( $map_settings['single_map'] );
		unset( $map_settings['category_map'] );

		if ( empty( $mks_map_div_id ) ) {
			$mks_map_div_id = 1;
		}

		$output = '<div id="mks-maps-'. $mks_map_div_id .'" class="mks-maps" data-settings=\'' . esc_attr( json_encode( $map_settings, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT ) ) . '\' data-items=\'' . esc_attr( json_encode( $items,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_FORCE_OBJECT ) ). '\'></div>';

		$mks_map_div_id++;

		return $output;
	}
endif;
