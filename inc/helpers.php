<?php

/**
 * Parse single post
 *
 * @param array $args
 * @return array|null
 */
if ( ! function_exists( 'mks_map_parse_item' ) ) :
	function mks_map_parse_item( $args = array() ) {

		$item = array();

		$defaults = array(
			'id'         => 0,
			'thumbnail'  => '',
			'link'       => '#',
			'title'      => 'No title',
			'meta'       => array(),
			'excerpt'    => '',
			'address'    => 'Beograd, Srbija',
			'latitude'   => '44.8149028',
			'longitude'  => '20.1424149',
			'pinColor'   => '',
			'categories' => '',
			'format'     => '',
		);

		if ( ! empty( $args ) ) {
			$item = mks_map_parse_args( $args, $defaults );
		}

		if ( ! empty( $item['thumbnail'] ) ) {
			preg_match( '@src="([^"]+)"@', $item['thumbnail'], $match );
			$src               = array_pop( $match );
			$item['thumbnail'] = '<img src="' . $src . '"/>';
		}

		return $item;
	}
endif;

/**
 * Get post meta data
 *
 * @param boolean $field specific option key
 * @return mixed meta data value or set of values
 * @since  1.0.0
 */

if ( ! function_exists( 'mks_map_get_post_meta' ) ) :
	function mks_map_get_post_meta( $post_id = false, $field = false ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$defaults = array(
			'enabled'    => false,
			'latitude'   => '',
			'longitude'  => '',
			'address'    => '',
			'zoom'       => 8,
			'streetView' => 0,
		);

		$meta            = get_post_meta( $post_id, '_mks_map_geolocation_data', true );
		$meta            = mks_map_parse_args( $meta, $defaults );
		$meta['enabled'] = get_post_meta( $post_id, '_mks_map_is_map_enabled', true );

		if ( $field ) {
			if ( isset( $meta[ $field ] ) ) {
				return $meta[ $field ];
			} else {
				return false;
			}
		}

		return $meta;
	}
endif;


/**
 * Get category meta data
 *
 * @param unknown $field specific option key
 * @return mixed meta data value or set of values
 * @since  1.0
 */

if ( ! function_exists( 'mks_map_get_category_meta' ) ) :
	function mks_map_get_category_meta( $cat_id = false, $field = false ) {

		$defaults = array(
			'latitude'   => '',
			'longitude'  => '',
			'address'    => '',
			'zoom'       => 8,
			'streetView' => 0,
		);

		if ( $cat_id ) {
			$meta = get_term_meta( $cat_id, '_mks_map_geolocation_data', true );
			$meta = mks_map_parse_args( $meta, $defaults );
		} else {
			$meta = $defaults;
		}
		if ( $field ) {
			if ( isset( $meta[ $field ] ) ) {

				return $meta[ $field ];

			} else {

				return false;

			}
		}

		return $meta;
	}
endif;

/**
 * Returns list of default settings that will be applied in Google MAP initialization
 *
 * @return mixed
 * @filter mks_map_modify_map_settings_defaults
 * @since  1.0.0
 */

if ( ! function_exists( 'mks_map_get_settings' ) ) :
	function mks_map_get_settings() {
		$defaults = array(
			'zoom'               => 8,
			'mapTypeId'          => 'roadmap',
			'scrollwheel'        => false,
			'zoomControl'        => 1,
			'pinColor'           => '098DA3',
			'clusterEnable'      => 1,
			'clusterColor'       => '098DA3',
			'clusterTextColor'   => '#fff',
			'clusterTextSize'    => '16',
			'printPolylines'     => 1,
			'polylinesLimit'     => 10,
			'infoBox'            => 1,
			'styles'             => false,
			'streetView'         => 0,
			'single_map'         => 'above',
			'category_map'       => 'posts',
			'osm_default_map'    => 'osm',
			'osm_mapbox_token'   => '',
			'osm_mapbox_styles'  => 'streets-v11',
			'osm_pin_color'      => '098DA3',
			'osm_cluster_enable' => 1,
			'osm_cluster_color'  => '098DA3',
			'osm_print_polylines'  => 0,
			'osm_polylines_limit'  => 10,
			'osm_single_map'     => 'above',
			'osm_category_map'   => 'posts',
			// Note: this will work only if there is one pin on the map
			'display'            => array(
				'meta'     => true,
				'category' => true,
				'format'   => true,
				'excerpt'  => true,
			),
		);

		/* Switch for new code, Google/OSM */
		$general_settings = get_option( 'meks-easy-maps-general' );
		$google_settings  = get_option( 'mks-map-settings' );
		$osm_settings     = get_option( 'meks-easy-maps-osm' );

		if ( empty( $general_settings )  ) {
			$general_settings['map_source'] =  'google';
		}

		$settings = $general_settings['map_source'] == 'google' ? $google_settings : $osm_settings;

		$settings = mks_map_parse_args( $settings, $defaults );

		if ( $general_settings['map_source'] == 'osm'  ) {
			$settings['single_map'] = $settings['osm_single_map'];
			$settings['category_map'] = $settings['osm_category_map'];
		}

		if ( is_single() ) {
			$geo_data               = mks_map_get_post_meta( get_queried_object_id() );
			$settings['streetView'] = $geo_data['streetView'];
			$settings['zoom']       = $geo_data['zoom'];
		}
		if ( is_category() ) {
			$geo_data               = mks_map_get_category_meta( get_queried_object_id() );
			$settings['streetView'] = $geo_data['streetView'];
			$settings['zoom']       = $geo_data['zoom'];
		}
		// If we pass off, false, 0, to JavaScript it will see it as true, that's why we are passing null
		foreach ( $settings as $setting_id => $setting ) {
			if ( $setting === 'off' ) {
				$settings[ $setting_id ] = null;
			}
		}

		$settings = apply_filters( 'mks_map_modify_settings', $settings );

		return $settings;
	}
endif;

/**
 * Parse args ( merge arrays )
 *
 * Similar to wp_parse_args() but extended to also merge multidimensional arrays
 *
 * @param array $a - set of values to merge
 * @param array $b - set of default values
 * @return array Merged set of elements
 * @since  1.0.0
 */

if ( ! function_exists( 'mks_map_parse_args' ) ) :
	function mks_map_parse_args( &$a, $b ) {
		$a = (array) $a;
		$b = (array) $b;
		$r = $b;
		foreach ( $a as $k => &$v ) {
			if ( is_array( $v ) && isset( $r[ $k ] ) ) {
				$r[ $k ] = mks_map_parse_args( $v, $r[ $k ] );
			} else {
				$r[ $k ] = $v;
			}
		}

		return $r;
	}
endif;

/**
 * Append any WP query args with posts pin meta query
 *
 * @param array $args
 * @return WP_Query
 * @since  1.0
 */
if ( ! function_exists( 'mks_map_get_query' ) ) :
	function mks_map_get_query( $args = array() ) {
		$meta_query = array(
			'meta_query' => array(
				array(
					'key'     => '_mks_map_is_map_enabled',
					'value'   => 1,
					'compare' => '=',
				),
			),
		);

		$args = array_merge( $args, $meta_query );

		return $args;
	}
endif;

/**
 * Get post excerpt
 *
 * Function outputs post excerpt for specific layout
 *
 * @param int $limit Number of characters to limit excerpt
 * @return string HTML output of category links
 * @since  1.0
 */

if ( ! function_exists( 'mks_map_get_excerpt' ) ) :
	function mks_map_get_excerpt( $limit = 250, $excerpt = false, $content = false ) {

		$manual_excerpt = false;

		if ( $excerpt ) {
			$content        = $excerpt;
			$manual_excerpt = true;
		} else {
			$text    = $content;
			$text    = strip_shortcodes( $text );
			$content = str_replace( ']]>', ']]&gt;', $text );
		}

		if ( ! empty( $content ) ) {
			if ( ! empty( $limit ) || ! $manual_excerpt ) {
				$more    = '...';
				$content = wp_strip_all_tags( $content );
				$content = preg_replace( '/\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[A-Z0-9+&@#\/%=~_|$]/i', '', $content );
				$content = mks_map_trim_chars( $content, $limit, $more );
			}

			return wp_kses_post( wpautop( $content ) );
		}

		return '';

	}
endif;

/**
 * Trim chars of a string
 *
 * @param string  $string Content to trim
 * @param int     $limit  Number of characters to limit
 * @param string  $more   Chars to append after trimed string
 * @return string Trimmed part of the string
 * @since  1.0
 */

if ( ! function_exists( 'mks_map_trim_chars' ) ) :
	function mks_map_trim_chars( $string, $limit, $more = '...' ) {

		if ( ! empty( $limit ) ) {

			$text = trim( preg_replace( "/[\n\r\t ]+/", ' ', $string ), ' ' );
			preg_match_all( '/./u', $text, $chars );
			$chars = $chars[0];
			$count = count( $chars );

			if ( $count > $limit ) {

				$chars = array_slice( $chars, 0, $limit );

				for ( $i = ( $limit - 1 ); $i >= 0; $i-- ) {
					if ( in_array( $chars[ $i ], array( '.', ' ', '-', '?', '!' ) ) ) {
						break;
					}
				}

				$chars   = array_slice( $chars, 0, $i );
				$string  = implode( '', $chars );
				$string  = rtrim( $string, '.,-?!' );
				$string .= $more;
			}
		}

		return $string;
	}
endif;

/**
 * Clean coordinates
 *
 * @return number
 */
if ( ! function_exists( 'mks_map_clean_coordinate' ) ) :
	function mks_map_clean_coordinate( $coordinate ) {
		$pattern = '/^(\-)?(\d{1,3})\.(\d{1,15})/';
		preg_match( $pattern, $coordinate, $matches );

		return $matches[0];
	}
endif;


/**
 * Get map location data for single posts
 *
 * @return array
 */

if ( ! function_exists( 'mks_map_get_single_post' ) ) :
	function mks_map_get_single_post() {

		global $post;

		$meta = mks_map_get_post_meta();

		if ( empty( $meta['enabled'] ) ) {
			return false;
		}

		$item = array(
			'id'        => get_the_ID(),
			'thumbnail' => has_post_thumbnail() ? get_the_post_thumbnail( get_the_ID(), 'medium' ) : '',
			'link'      => get_permalink(),
			'title'     => get_the_title(),
			'excerpt'   => mks_map_get_excerpt( 100, $post->post_excerpt ),
			'address'   => $meta['address'],
			'latitude'  => $meta['latitude'],
			'longitude' => $meta['longitude'],
		);

		$items[] = mks_map_parse_item( $item );

		// print_r($items);

		return $items;

	}
endif;

/**
 * Get map location data for post in the loop
 *
 * @return array
 */

if ( ! function_exists( 'mks_map_get_loop_post' ) ) :
	function mks_map_get_loop_post() {

		global $post;

		$meta = mks_map_get_post_meta();

		if ( empty( $meta['enabled'] ) ) {
			return false;
		}

		$item = array(
			'id'         => get_the_ID(),
			'thumbnail'  => has_post_thumbnail() ? get_the_post_thumbnail( get_the_ID(), 'medium' ) : '',
			'link'       => get_permalink(),
			'title'      => get_the_title(),
			'excerpt'    => mks_map_get_excerpt( 100, $post->post_excerpt ),
			'address'    => $meta['address'],
			'latitude'   => $meta['latitude'],
			'longitude'  => $meta['longitude'],
			'meta'       => '<span class="meta-item meta-date">' . get_the_modified_date() . '</span>',
			'categories' => mks_map_get_category(),
		);

		$item = apply_filters( 'mks_map_modify_loop_post', $item );

		return mks_map_parse_item( $item );
	}
endif;

/**
 * Get category in loop
 *
 * @param $category
 * @return array
 * @since  1.0
 */
if ( ! function_exists( 'mks_map_get_loop_category' ) ) :
	function mks_map_get_loop_category( $category ) {
		$category_meta = mks_map_get_category_meta( $category->term_id );

		$item = array(
			'id'        => $category->term_id,
			'link'      => get_category_link( $category->term_id ),
			'title'     => $category->name,
			'excerpt'   => wpautop( $category->description ),
			'address'   => $category_meta['address'],
			'latitude'  => $category_meta['latitude'],
			'longitude' => $category_meta['longitude'],
		);

		$item = apply_filters( 'mks_map_modify_loop_category', $item );

		return mks_map_parse_item( $item );
	}
endif;

/**
 * Get post categories data
 *
 * Function outputs category links with HTML
 *
 * @param int     $post_id
 * @return string HTML output of category links
 * @since  1.0
 */

if ( ! function_exists( 'mks_map_get_category' ) ) :
	function mks_map_get_category( $post_id = false ) {

		if ( empty( $post_id ) ) {
			$post_id = get_the_ID();
		}

		$terms = get_the_terms( $post_id, 'category' );

		if ( is_wp_error( $terms ) ) {
			return '';
		}

		if ( empty( $terms ) ) {
			return '';
		}

		$links = array();

		foreach ( $terms as $term ) {
			$link = get_term_link( $term, 'category' );
			if ( ! is_wp_error( $link ) ) {
				$links[] = '<a href="' . esc_url( $link ) . '" rel="tag" class="cat-' . esc_attr( $term->term_id ) . '">' . $term->name . '</a>';
			}
		}

		if ( ! empty( $links ) ) {
			return implode( '', $links );
		}

		return '';

	}
endif;


/* Check if Trawell theme is active */

function mks_map_is_trawell_active() {
	$theme = wp_get_theme();
	return $theme->get( 'TextDomain' ) == 'trawell';
}


/**
 * Logging helper
 *
 * @since    1.0.0
 */
if ( ! function_exists( 'meks_easy_maps_log' ) ) :
	function meks_easy_maps_log( $mixed ) {

		if ( is_array( $mixed ) ) {
			$mixed = print_r( $mixed, 1 );
		} elseif ( is_object( $mixed ) ) {
			ob_start();
			var_dump( $mixed );
			$mixed = ob_get_clean();
		}

		$handle = fopen( MKS_MAP_DIR . 'log', 'a' );
		fwrite( $handle, $mixed . PHP_EOL );
		fclose( $handle );
	}
endif;
