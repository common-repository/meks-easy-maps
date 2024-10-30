<?php

/* Add metabox */
add_action( 'load-post.php', 'mks_map_meta_boxes_setup' );
add_action( 'load-post-new.php', 'mks_map_meta_boxes_setup' );

if ( !function_exists( 'mks_map_meta_boxes_setup' ) ) :
	function mks_map_meta_boxes_setup() {
	
		add_action( 'add_meta_boxes', 'mks_map_add_custom_box' );
		add_action( 'save_post', 'mks_map_save_postdata', 10, 2 );

	}
endif;


function mks_map_add_custom_box() {
	add_meta_box(
		'mks_map_metabox_id', 
		__( 'Post Location', 'mks' ), 
		'mks_map_custom_box', 
		'post', 
		'advanced' 
	);	
}

function mks_map_save_postdata($post_id) {

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
		return;

	if ( !isset( $_POST['mks_map_post_metabox_nonce'] ) || !wp_verify_nonce( $_POST['mks_map_post_metabox_nonce'], 'mks_map_post_metabox_save' ) ) {
   		return;
	}

	if(!current_user_can('edit_post', $post_id)) 
		return $post_id;
	
	$address = isset( $_POST['mks_map_geolocation_address'] ) ? $_POST['mks_map_geolocation_address'] : '';
	$street_view = isset( $_POST['mks_map_street_view_enabled'] ) ? absint($_POST['mks_map_street_view_enabled']) : 0;
	$is_enabled = 0;
	$post_data = array();

	if( !empty($address) && !empty($_POST['mks_map_geolocation_latitude']) && !empty($_POST['mks_map_geolocation_longitude']) ) {
		
		$latitude = mks_map_clean_coordinate($_POST['mks_map_geolocation_latitude']);
		$longitude = mks_map_clean_coordinate($_POST['mks_map_geolocation_longitude']);
		$zoom = absint($_POST['mks_map_zoom']);
		
		$post_data = array(
			'latitude' => $latitude,
			'longitude' => $longitude,
			'address' => $address,
			'zoom' => $zoom,
			'streetView' => $street_view,
		);
		
		$is_enabled = isset( $_POST['mks_map_geolocation_enabled'] ) && !empty( $_POST['mks_map_geolocation_enabled'] ) ? $_POST['mks_map_geolocation_enabled'] : 0;
	}
	update_post_meta( $post_id, '_mks_map_geolocation_data', $post_data );
	update_post_meta( $post_id, '_mks_map_is_map_enabled', $is_enabled);


	return $post_id;
}

function mks_map_custom_box() {
	wp_nonce_field( 'mks_map_post_metabox_save', 'mks_map_post_metabox_nonce' );
	$geo_data = mks_map_get_post_meta();

	$general_options = get_option( 'meks-easy-maps-general' );
	$google_options = get_option( 'mks-map-settings' );
	$osm_options = get_option( 'meks-easy-maps-osm' );

	if ( empty( $general_options )  ) {
		$general_options['map_source'] =  'google';
	}

	$settings = $general_options['map_source'] == 'google' ? $google_options : $osm_options;

	$is_street_view_enabled = !empty($geo_data['streetView']) ? $geo_data['streetView'] : 0;
	$zoom = !empty($geo_data['zoom']) ? $geo_data['zoom'] : $settings['zoom'];

	?>

	<?php if( $general_options['map_source'] == 'google' ): ?>
		<p>
			<input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />
			<span class="howto"><?php _e('Enter your address/city or drag marker on map', MKS_MAP_TEXTDOMAIN) ?></span>
		</p>
	<?php else: ?>
		<p>
			<span><?php _e('Search for place or address:', MKS_MAP_TEXTDOMAIN) ?></span>
			<input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />
		</p>
		<p class="meks-map-google-hidden">
			<span class=""><?php _e('Or set manually latitude and longitude:', MKS_MAP_TEXTDOMAIN) ?></span>
			<br>
			<label for="mks_map_geolocation_latitude" class="mks-map-latitude">
				<?php _e('Latitude', MKS_MAP_TEXTDOMAIN) ?>:
				<input type="text" id="mks-map-geolocation-latitude" name="mks_map_geolocation_latitude" value="<?php echo esc_attr($geo_data['latitude']); ?>"/>
			</label>
			<label for="mks_map_geolocation_longitude" class="mks-map-longitude">
				<?php _e('Longitude', MKS_MAP_TEXTDOMAIN) ?>:
				<input type="text" id="mks-map-geolocation-longitude" name="mks_map_geolocation_longitude" value="<?php echo esc_attr($geo_data['longitude']); ?>"/>
			</label>
			<button class="button button-secondary meks-map-button-geocode"><?php _e('Apply Changes', MKS_MAP_TEXTDOMAIN) ?></button>
		</p>
    <?php endif; ?>

    <?php if( $general_options['map_source'] == 'google' && empty($settings['api_key']) ) : ?>
        <p class="error-message">
            <?php printf(__( 'Google Maps API key is required to display the map. Please provide your API key in the <a href="%s">plugin settings</a>'),  admin_url('options-general.php?page=meks-easy-maps') ) ?>
        </p>
    <?php else: ?>
        <div id="mks-map-geolocation-map" style="width: 100%; height: 300px;"></div>
    <?php endif; ?>

    <p>
        <label for="mks_map_zoom" class="mks-map-zoom-label">
            <?php _e('Zoom level', MKS_MAP_TEXTDOMAIN) ?>:
            <input type="number" id="mks_map_zoom" name="mks_map_zoom" value="<?php echo absint($zoom); ?>" min="1" max="20">
        </label>
    </p>

	<?php if( $general_options['map_source'] == 'google' ) : ?>
		<p>
			<label for="mks_map_street_view_enabled" class="mks-map-street-view-enabled-label">
				<input type="checkbox" id="mks_map_street_view_enabled" name="mks_map_street_view_enabled" value="1" <?php echo checked( $is_street_view_enabled, 1 ); ?>>
				<?php _e('Enable street view on map', MKS_MAP_TEXTDOMAIN) ?>
			</label>
		</p>

		<input type="hidden" id="mks-map-geolocation-latitude" name="mks_map_geolocation_latitude" value="<?php echo esc_attr($geo_data['latitude']); ?>"/>
		<input type="hidden" id="mks-map-geolocation-longitude" name="mks_map_geolocation_longitude" value="<?php echo esc_attr($geo_data['longitude']); ?>"/>

	<?php endif; ?>

    <input type="hidden" id="mks_map_geolocation_enabled" name="mks_map_geolocation_enabled" value="1">
	
	<?php 
}