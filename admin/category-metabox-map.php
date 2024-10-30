<?php

/* Save category meta */
add_action( 'edited_category', 'mks_map_save_cat_data', 10, 2 );
add_action( 'create_category', 'mks_map_save_cat_data', 10, 2 );

/* Add metabox on category create and edit and delete category meta on delete */
add_action( 'category_add_form_fields', 'mks_map_add_cat_fields', 10, 2 );
add_action( 'category_edit_form_fields', 'mks_map_edit_cat_fields', 10, 2 );
add_action( 'delete_category', 'mks_map_delete_cat_fields' );


/**
 * Save category meta
 *
 * Callback function to save category meta
 *
 * @since  1.0
 */

function mks_map_save_cat_data( $term_id ) {
	
	$address = isset( $_POST['mks_map_geolocation_address'] ) ? $_POST['mks_map_geolocation_address'] : '';
	$street_view = isset( $_POST['mks_map_street_view_enabled'] ) ? absint( $_POST['mks_map_street_view_enabled'] ) : 0;
	$is_enabled = 0;
	$cat_data = array();
	
	if ( !empty( $address ) && !empty( $_POST['mks_map_geolocation_latitude'] ) && !empty( $_POST['mks_map_geolocation_longitude'] ) ) {
		
		$latitude = mks_map_clean_coordinate( $_POST['mks_map_geolocation_latitude'] );
		$longitude = mks_map_clean_coordinate( $_POST['mks_map_geolocation_longitude'] );
		$zoom = absint( $_POST['mks_map_zoom'] );
		
		$cat_data = array(
			'latitude'    => $latitude,
			'longitude'   => $longitude,
			'address'     => $address,
			'zoom'        => $zoom,
			'streetView' => $street_view,
		);
		
		$is_enabled = isset( $_POST['mks_map_geolocation_enabled'] ) && !empty( $_POST['mks_map_geolocation_enabled'] ) ? $_POST['mks_map_geolocation_enabled'] : 0;
	}
	update_term_meta( $term_id, '_mks_map_geolocation_data', $cat_data );
	update_term_meta( $term_id, '_mks_map_is_map_enabled', $is_enabled );
	
	
	return $term_id;
}


/**
 * Add category meta
 *
 * Callback function to add category meta on "new category" screen
 *
 * @since  1.0
 */

function mks_map_add_cat_fields() {
	
	$geo_data = mks_map_get_category_meta();
    
    $general_options = get_option( 'meks-easy-maps-general' );
	$google_options = get_option( 'mks-map-settings' );
    $osm_options = get_option( 'meks-easy-maps-osm' );
    
    if ( empty( $general_options )  ) {
		$general_options['map_source'] =  'google';
	}

	$settings = $general_options['map_source'] == 'google' ? $google_options : $osm_options;

	$zoom = !empty( $geo_data['zoom'] ) ? $geo_data['zoom'] : $settings['zoom'];
	$is_street_view_enabled = !empty( $geo_data['streetView'] ) ? $geo_data['streetView'] : 0;
	
	?>
    <div class="form-field">

        <label for="mks_map_geolocation_address"><?php _e('Location/Search for place or address:', MKS_MAP_TEXTDOMAIN) ?></label>

        <?php if( $general_options['map_source'] == 'google' ): ?>
		<p>
			<input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" class="description" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />
			<span class="howto"><?php _e('Enter your address/city or drag marker on map', MKS_MAP_TEXTDOMAIN) ?></span>
		</p>
        <?php else: ?>
            <input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />

            <p class="meks-map-google-hidden">
                <label class=""><?php _e('Or set manually latitude and longitude:', MKS_MAP_TEXTDOMAIN) ?></label>
                <label for="mks_map_geolocation_latitude" class="mks-map-latitude">
                    <?php _e('Latitude', MKS_MAP_TEXTDOMAIN) ?>:
                    <input type="text" id="mks-map-geolocation-latitude" name="mks_map_geolocation_latitude" value="<?php echo esc_attr($geo_data['latitude']); ?>"/>
                </label>
                <label for="mks_map_geolocation_longitude" class="mks-map-longitude">
                    <?php _e('Longitude', MKS_MAP_TEXTDOMAIN) ?>:
                    <input type="text" id="mks-map-geolocation-longitude" name="mks_map_geolocation_longitude" value="<?php echo esc_attr($geo_data['longitude']); ?>"/>
                </label>
                <button class="button button-secondary meks-map-button-secondary"><?php _e('Apply Changes', MKS_MAP_TEXTDOMAIN) ?></button>
            </p>
        <?php endif; ?>


	    <?php if( $general_options['map_source'] == 'google' && empty($settings['api_key']) ) : ?>
            <p class="error-message">
                <?php printf(__( 'Google Maps API key is required to display the map. Please provide your API key in the <a href="%s">plugin settings</a>'),  admin_url('options-general.php?page=meks-easy-maps') ) ?>
            </p>
        <?php else: ?>
            <div id="mks-map-geolocation-map" style="width: 100%; height: 300px;"></div>
        <?php endif; ?>


        <br>
            <label for="mks_map_zoom" class="mks-map-zoom-label-category">
                <?php _e( 'Zoom level', MKS_MAP_TEXTDOMAIN ) ?>:
            </label>
            <input type="number" id="mks-map-zoom" name="mks_map_zoom" class="small-text" value="<?php echo absint( $zoom ); ?>" min="1" max="20">
        <br>

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


        <input type="hidden" id="mks-map-geolocation-enabled" name="mks_map_geolocation_enabled" value="1">

    </div>
	<?php
}


/**
 * Edit category meta
 *
 * Callback function to edit category meta
 *
 * @since  1.0
 */

function mks_map_edit_cat_fields( $term ) {
	
    $geo_data = mks_map_get_category_meta( $term->term_id );
    
    $general_options = get_option( 'meks-easy-maps-general' );
	$google_options = get_option( 'mks-map-settings' );
	$osm_options = get_option( 'meks-easy-maps-osm' );

    if ( empty( $general_options )  ) {
		$general_options['map_source'] =  'google';
	}

	$settings = $general_options['map_source'] == 'google' ? $google_options : $osm_options;

	$zoom = !empty( $geo_data['zoom'] ) ? $geo_data['zoom'] : $settings['zoom'];
    $is_street_view_enabled = !empty( $geo_data['streetView'] ) ? $geo_data['streetView'] : 0;
    
	?>
    <tr class="form-field">
        <th scope="row" valign="top"><label><?php esc_html_e( 'Map', 'meks-easy-map' ); ?></label></th>
        <td>
            <div class="form-field">

                <?php if( $general_options['map_source'] == 'google' ): ?>

                    <label for="mks_map_geolocation_address"><?php _e('Location', MKS_MAP_TEXTDOMAIN) ?></label>
                    <input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" class="description" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />
                    <span class="howto"><?php _e('Enter your address/city or drag marker on map', MKS_MAP_TEXTDOMAIN) ?></span>

                <?php else: ?>

                    <label for="mks_map_geolocation_address"><?php _e('Location/Search for place or address:', MKS_MAP_TEXTDOMAIN) ?></label>
                    <input type="text" id="mks-map-geolocation-address" name="mks_map_geolocation_address" style="min-width: 100%" value="<?php echo esc_attr( $geo_data['address'] ) ?>" />
                    
                    <span class="mks-space">
                        <label class=""><?php _e('Or set manually latitude and longitude:', MKS_MAP_TEXTDOMAIN) ?></label>
                    </span>
                    <span class="mks-cat-label-options">
                        <label for="mks_map_geolocation_latitude" class="mks-map-latitude">
                            <?php _e('Latitude', MKS_MAP_TEXTDOMAIN) ?>:
                            <input type="text" id="mks-map-geolocation-latitude" name="mks_map_geolocation_latitude" value="<?php echo esc_attr($geo_data['latitude']); ?>"/>
                        </label>
                        <label for="mks_map_geolocation_longitude" class="mks-map-longitude">
                            <?php _e('Longitude', MKS_MAP_TEXTDOMAIN) ?>:
                            <input type="text" id="mks-map-geolocation-longitude" name="mks_map_geolocation_longitude" value="<?php echo esc_attr($geo_data['longitude']); ?>"/>
                        </label>
                        <button class="button button-secondary meks-map-button-secondary"><?php _e('Apply Changes', MKS_MAP_TEXTDOMAIN) ?></button>
                    </span>
                    <br>
                    <br>

                <?php endif; ?>


                <?php if( $general_options['map_source'] == 'google' && empty($settings['api_key']) ) : ?>
                    <p class="error-message">
                        <?php printf(__( 'Google Maps API key is required to display the map. Please provide your API key in the <a href="%s">plugin settings</a>'),  admin_url('options-general.php?page=meks-easy-maps') ) ?>
                    </p>
                <?php else: ?>
                    <div id="mks-map-geolocation-map" style="width: 100%; height: 300px;"></div>
                <?php endif; ?>

                <br>
                <label for="mks_map_zoom" class="mks-map-zoom-label-category-edit">
		            <?php _e( 'Zoom level', MKS_MAP_TEXTDOMAIN ) ?>
                    <input type="number" id="mks-map-zoom" name="mks_map_zoom" value="<?php echo absint( $zoom ); ?>" min="1" max="20">
                </label>

                <?php if( $general_options['map_source'] == 'google' ) : ?>
                    <br>
                    <br>
                    <label for="mks_map_street_view_enabled" class="mks-map-street-view-enabled-label">
                        <input type="checkbox" id="mks_map_street_view_enabled" name="mks_map_street_view_enabled" value="1" <?php echo checked( $is_street_view_enabled, 1 ); ?>>
                        <?php _e('Enable street view on map', MKS_MAP_TEXTDOMAIN) ?>
                    </label>

                    <input type="hidden" id="mks-map-geolocation-latitude" name="mks_map_geolocation_latitude" value="<?php echo esc_attr($geo_data['latitude']); ?>"/>
                    <input type="hidden" id="mks-map-geolocation-longitude" name="mks_map_geolocation_longitude" value="<?php echo esc_attr($geo_data['longitude']); ?>"/>

                <?php endif; ?>


                <input type="hidden" id="mks-map-geolocation-enabled" name="mks_map_geolocation_enabled" value="1">

            </div>
        </td>
    </tr>
	
	<?php
}