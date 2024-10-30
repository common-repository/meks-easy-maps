<?php

class Mks_Map_Options_Page {
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Settings key in database, used in get_option() as first parameter
     *
     * @var string
     */
    private $settings_key = 'mks-map-settings';

    /**
     * Slug of the page, also used as identifier for hooks
     *
     * @var string
     */
    private $slug = 'meks-easy-maps';

    /**
     * Options group id, will be used as identifier for adding fields to options page
     *
     * @var string
     */
    private $options_group_id = 'mks-map-settings-group';

    /**
     * Array of all fields that will be printed on the settings page
     *
     * @var array
     */
    private $fields = array();

    /**
     * Start up
     */
    public function __construct() {

        add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
        
        $tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general';

        if ( $tab == 'google_maps' ) {
            add_action( 'admin_init', array( $this, 'page_google_init' ) );
        } elseif ( $tab == 'open_street_map' ) {
            add_action( 'admin_init', array( $this, 'page_osm_init' ) );
        } else {
            add_action( 'admin_init', array( $this, 'page_general_init' ) );
        }
    }

    /**
     * Add options page
     */
    public function add_plugin_page() {
        // This page will be under "Settings"
        add_options_page(
            esc_html__( 'Meks Easy Maps', MKS_MAP_TEXTDOMAIN ),
            esc_html__( 'Meks Easy Maps', MKS_MAP_TEXTDOMAIN ),
            'manage_options',
            $this->slug,
            array( $this, 'print_settings_page' )
        );
    }

    
    /**
     * Options page callback
     */
    public function print_settings_page() {
        // Set class property
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

            <?php $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'general'; ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=meks-easy-maps&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
                <a href="?page=meks-easy-maps&tab=google_maps" class="nav-tab <?php echo $active_tab == 'google_maps' ? 'nav-tab-active' : ''; ?>">Google Maps</a>
                <a href="?page=meks-easy-maps&tab=open_street_map" class="nav-tab <?php echo $active_tab == 'open_street_map' ? 'nav-tab-active' : ''; ?>">Open Street Map</a>
            </h2>

            <!-- <form method="post" action="options.php"> -->
            <form method="post" action="<?php echo esc_url( add_query_arg( 'tab', $active_tab, admin_url('options.php') ) ); ?>">
                <?php

                if( $active_tab == 'google_maps' ) {
                    settings_fields( $this->slug );
                    do_settings_sections( $this->settings_key );
                } elseif ( $active_tab == 'open_street_map' ) {
                    settings_fields( $this->slug );
                    do_settings_sections( 'meks-easy-maps-osm' );
                } else {
                    settings_fields( $this->slug );
                    do_settings_sections( 'meks-easy-maps-general' );
                }  
                
                echo '<input type="hidden" name="tab" value="' . esc_attr( $active_tab ) . '" />';
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function enqueue_scripts() {
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'mks_map_admin_scripts', MKS_MAP_URL . 'admin/js/settings-page.js', array( 'wp-color-picker' ), MKS_MAP_VER, true );
    }


    /* ****** TAB 1 ****** */


    /**
     * Register General Settings
     */
    public function page_general_init() {
        
        $this->fields = array(
            'map_source' => array(
                'id' => 'map_source',
                'title' => 'Choose map source',
                'sanitize' => 'radio',
                'default' => 'google'
            ),
        );

        $this->options = get_option( 'meks-easy-maps-general' );

        register_setting(
            $this->slug, // Option group
            'meks-easy-maps-general', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );


        $section_id = 'mks_map_general_fields_section';

        add_settings_section(
            $section_id, // id
            '',          // title
            '',          // callback
            'meks-easy-maps-general' // page
        );

        foreach ( $this->fields as $field ) {
            if ( empty( $field['id'] ) ) {
                continue;
            }

            $action = 'print_' . $field['id'] . '_field';
            $callback = method_exists( $this, $action ) ? array( $this, $action ) : $field['action'];
            $value = !empty( $this->options[$field['id']] ) ? $this->options[$field['id']] : $field['default'];

            add_settings_field(
                'mks_map_' . $field['id'] . '_id',
                esc_html__( $field['title'], MKS_MAP_TEXTDOMAIN ),
                $callback,
                'meks-easy-maps-general',
                $section_id,
                array( 'value' => esc_attr( $value ) )
            );
        }

    }

    /**
     * Print the map source field
     */
    public function print_map_source_field( array $args ) {
        ?>
        <label for="mks_map_map_source_google"><input id="mks_map_map_source_google" type="radio" name="meks-easy-maps-general[map_source]" value="google" <?php checked( "google", $args['value'] ); ?>><?php _e( 'Google Maps', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_map_source_osm"><input id="mks_map_map_source_osm" type="radio" name="meks-easy-maps-general[map_source]" value="osm" <?php checked( "osm", $args['value'] ); ?>><?php _e( 'Open Street Map', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }




    /* ****** TAB 2 ****** */


    /**
     * Register Google settings
     */
    public function page_google_init() {

        $google_fields = array(
            'api_key' => array(
                'id' => 'api_key',
                'title' => 'API key',
                'sanitize' => 'text',
                'default' => ''
            ),
            'mapTypeId' => array(
                'id' => 'mapTypeId',
                'title' => 'Map Type',
                'sanitize' => 'text',
                'default' => 'roadmap'
            ),
            'panControl' => array(
                'id' => 'panControl',
                'title' => 'Pan Control',
                'sanitize' => 'radio',
                'default' => 'on'
            ),
            'clusterEnable' => array(
                'id' => 'clusterEnable',
                'title' => 'Clustering',
                'sanitize' => 'radio',
                'default' => 'on'
            ),
            'printPolylines' => array(
                'id' => 'printPolylines',
                'title' => 'Polylines',
                'sanitize' => 'radio',
                'default' => 'off'
            ),
            'polylinesLimit' => array(
                'id' => 'polylinesLimit',
                'title' => 'Polylines Pin Limit',
                'sanitize' => 'absint',
                'default' => 10
            ),
            'pinColor' => array(
                'id' => 'pinColor',
                'title' => 'Pin Color',
                'sanitize' => 'text',
                'default' => '#098DA3'
            ),
            'clusterColor' => array(
                'id' => 'clusterColor',
                'title' => 'Cluster Color',
                'sanitize' => 'text',
                'default' => '#098DA3'
            ),
            'single_map' => array(
                'id' => 'single_map',
                'title' => 'Map on the single post template displays',
                'sanitize' => 'text',
                'default' => 'above'
            ),
            'category_map' => array(
                'id' => 'category_map',
                'title' => 'Map on the category template displays',
                'sanitize' => 'text',
                'default' => 'posts'
            )
        );

        $this->fields = apply_filters( 'mks_map_modify_settings_fields', $google_fields );
        $this->options = get_option( $this->settings_key );

        register_setting(
            $this->slug, // Option group
            $this->settings_key, // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        if ( empty( $this->fields ) ) {
            return false;
        }

        $section_id = 'mks_map_fields_sections';

        add_settings_section(
            $section_id,
            '',
            '',
            $this->settings_key
        );

        foreach ( $this->fields as $field ) {
            if ( empty( $field['id'] ) ) {
                continue;
            }

            $action = 'print_' . $field['id'] . '_field';
            $callback = method_exists( $this, $action ) ? array( $this, $action ) : $field['action'];
            $value = !empty( $this->options[$field['id']] ) ? $this->options[$field['id']] : $field['default'];

            add_settings_field(
                'mks_map_' . $field['id'] . '_id',
                esc_html__( $field['title'], MKS_MAP_TEXTDOMAIN ),
                $callback,
                $this->settings_key,
                $section_id,
                array( 'value' => esc_attr( $value ) )
            );
        }

        if( !mks_map_is_trawell_active() ){

            add_settings_field(
                'mks_map_trawell_note_id',
                esc_html__('Need more features?', MKS_MAP_TEXTDOMAIN ),
                array($this, 'print_trawell_note_field'),
                $this->settings_key,
                $section_id,
                array( 'value' => esc_attr( $value ) )
            );
        }
    }


    /**
     * Print the API key input
     */
    public function print_api_key_field( array $args ) {
        echo '<input type="text" id="mks_map_api_key" name="' . $this->settings_key . '[api_key]" value="' . $args['value'] . '" style="width: 50%;"/><br/>';
        echo wp_kses_post( sprintf( '<p class="description">'.__( 'How do I get my <a target="_blank" href="%s">Google Maps API key</a>?', MKS_MAP_TEXTDOMAIN ), esc_url( 'https://developers.google.com/maps/documentation/javascript/get-api-key' ) ) . '</p>' );
    }

    /**
     * Print the Pan control radio buttons
     */
    public function print_panControl_field( array $args ) {
        ?>
        <label for="mks_map_enable_panControl"><input id="mks_map_enable_panControl" type="radio" name="<?php echo $this->settings_key; ?>[panControl]" value="on" <?php checked( "on", $args['value'] ); ?>><?php _e( 'On', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_disable_panControl"><input id="mks_map_disable_panControl" type="radio" name="<?php echo $this->settings_key; ?>[panControl]" value="off" <?php checked( "off", $args['value'] ); ?>><?php _e( 'Off', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }


    /**
     * Print the Polylines radio buttons
     */
    public function print_printPolylines_field( array $args ) {
        ?>
        <label for="mks_map_enable_printPolylines"><input id="mks_map_enable_printPolylines" type="radio" name="<?php echo $this->settings_key; ?>[printPolylines]" value="on" <?php checked( "on", $args['value'] ); ?>><?php _e( 'On', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_disable_printPolylines"><input id="mks_map_disable_printPolylines" type="radio" name="<?php echo $this->settings_key; ?>[printPolylines]" value="off" <?php checked( "off", $args['value'] ); ?>><?php _e( 'Off', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }

    /**
     * Print the ZOOM input
     */
    public function print_polylinesLimit_field( array $args ) {
        printf(
            '<input type="number" class="small-text" id="mks_map_polylinesLimit" name="' . $this->settings_key . '[polylinesLimit]" value="%s" min="1" max="100" step="1" />',
            $args['value']
        );

        printf( '<p class="description">'.esc_html('Display polylines only if the map has less pins than the number specified above.', MKS_MAP_TEXTDOMAIN).'</p>');
    }
    /**
     * Print the Polylines radio buttons
     */
    public function print_clusterEnable_field( array $args ) {
        ?>
        <label for="mks_map_enable_clusterEnable"><input id="mks_map_enable_clusterEnable" type="radio" name="<?php echo $this->settings_key; ?>[clusterEnable]" value="on" <?php checked( "on", $args['value'] ); ?>><?php _e( 'On', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_disable_clusterEnable"><input id="mks_map_disable_clusterEnable" type="radio" name="<?php echo $this->settings_key; ?>[clusterEnable]" value="off" <?php checked( "off", $args['value'] ); ?>><?php _e( 'Off', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }

    /**
     * Print the Map Type input
     */
    public function print_mapTypeId_field( array $args ) {
        $types = array( 'roadmap', 'satellite', 'hybrid', 'terrain' );
        ?>
        <select name="<?php echo $this->settings_key; ?>[mapTypeId]" id="mks_map_mapTypeId">
            <?php foreach ( $types as $type ) :?>
                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $args['value'], $type ) ?>><?php echo ucfirst( $type ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Print the Pin Color input
     */
    public function print_pinColor_field( array $args ) {
        printf( '<input type="text" class="widefat" id="mks_map_pinColor" name="' . $this->settings_key . '[pinColor]" value="%s" />',  $args['value'] );
    }

    /**
     * Print the cluster Color input
     */
    public function print_clusterColor_field( array $args ) {
        printf('<input type="text" class="widefat" id="mks_map_clusterColor" name="' . $this->settings_key . '[clusterColor]" value="%s" />', $args['value'] );
    }

    public function print_single_map_field( array $args ) {
        $checked = $args['value'];
        printf(
            '<label><input type="radio" id="mks_map_single_map" name="' . $this->settings_key . '[single_map]" value="%s" %s/> %s</label><br>',
            'above',
            checked( $checked, 'above', false ),
            __( 'Location above the content', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_single_map" name="' . $this->settings_key . '[single_map]" value="%s" %s/> %s</label><br>',
            'below',
            checked( $checked, 'below', false ),
            __( 'Location below the content', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_single_map" name="' . $this->settings_key . '[single_map]" value="%s" %s/> %s</label><br>',
            'none',
            checked( $checked, 'none', false ),
            __( 'Nothing', MKS_MAP_TEXTDOMAIN )
        );
    }

    /**
     * Print the show on map input
     */
    public function print_category_map_field( array $args ) {
        $checked = $args['value'];
        printf(
            '<label><input type="radio" id="mks_map_show_on_map" name="' . $this->settings_key . '[category_map]" value="%s" %s/> %s</label><br>',
            'posts',
            checked( $checked, 'posts', false ),
            __( 'All category posts with a location set', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_show_on_map" name="' . $this->settings_key . '[category_map]" value="%s" %s/> %s</label><br>',
            'categories',
            checked( $checked, 'categories', false ),
            __( 'Category location', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_show_on_map" name="' . $this->settings_key . '[category_map]" value="%s" %s/> %s</label>',
            'none',
            checked( $checked, 'none', false ),
            __( 'Nothing', MKS_MAP_TEXTDOMAIN )
        );

        printf( '<p class="description">'.esc_html('Note: Your theme needs to utilize category description support in order to use this option.', MKS_MAP_TEXTDOMAIN).'</p>');
    }

    /**
     * Print the show on map input
     */
    public function print_trawell_note_field( array $args ) {

        echo wp_kses_post( sprintf( '<p class="description">'.__( 'For more styling options and features, please check our <a target="_blank" href="%s">Trawell WordPress theme</a>.', MKS_MAP_TEXTDOMAIN ), esc_url( 'https://mekshq.com/demo/trawell/' ) ) . '</p>' );
    }



    /* ****** TAB 3 ****** */


    /**
     * Register OSM Settings
     */
    public function page_osm_init() {
        
        $osm_fields = array(
            'osm_default_map' => array(
                'id' => 'osm_default_map',
                'title' => 'Defult map style (map images)',
                'sanitize' => 'radio',
                'default' => 'osm'
            ),
            'osm_mapbox_token' => array(
                'id' => 'osm_mapbox_token',
                'title' => 'Mapbox API token key',
                'sanitize' => 'text',
                'default' => ''
            ),
            'osm_mapbox_styles' => array(
                'id' => 'osm_mapbox_styles',
                'title' => 'Mapbox API map styles',
                'sanitize' => 'text',
                'default' => 'streets-v11'
            ),
            'osm_cluster_enable' => array(
                'id' => 'osm_cluster_enable',
                'title' => 'Clustering',
                'sanitize' => 'radio',
                'default' => 'on'
            ),
            'osm_pin_color' => array(
                'id' => 'osm_pin_color',
                'title' => 'Pin Color',
                'sanitize' => 'text',
                'default' => '#098DA3'
            ),
            'osm_cluster_color' => array(
                'id' => 'osm_cluster_color',
                'title' => 'Cluster Color',
                'sanitize' => 'text',
                'default' => '#098DA3'
            ),
            'osm_print_polylines' => array(
                'id' => 'osm_print_polylines',
                'title' => 'Polylines',
                'sanitize' => 'radio',
                'default' => 'off'
            ),
            'osm_polylines_limit' => array(
                'id' => 'osm_polylines_limit',
                'title' => 'Polylines Pin Limit',
                'sanitize' => 'absint',
                'default' => 10
            ),
            'osm_single_map' => array(
                'id' => 'osm_single_map',
                'title' => 'Map on the single post template displays',
                'sanitize' => 'text',
                'default' => 'above'
            ),
            'osm_category_map' => array(
                'id' => 'osm_category_map',
                'title' => 'Map on the category template displays',
                'sanitize' => 'text',
                'default' => 'posts'
            )
        );

        $this->fields = apply_filters( 'mks_map_modify_settings_osm_fields', $osm_fields );
        $this->options = get_option( 'meks-easy-maps-osm' );

        register_setting(
            $this->slug, // Option group
            'meks-easy-maps-osm', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );


        $section_id = 'mks_map_osm_fields_section';

        add_settings_section(
            $section_id, // id
            '',          // title
            '',          // callback
            'meks-easy-maps-osm' // page
        );

        foreach ( $this->fields as $field ) {
            if ( empty( $field['id'] ) ) {
                continue;
            }

            $action = 'print_' . $field['id'] . '_field';
            $callback = method_exists( $this, $action ) ? array( $this, $action ) : '';
            $value = !empty( $this->options[$field['id']] ) ? $this->options[$field['id']] : $field['default'];

            $type = 'osm';

            if ( in_array( $field['id'], array( 'osm_mapbox_token', 'osm_mapbox_styles' ) ) ) {
               $is_mapbox = !empty( $this->options ) && $this->options['osm_default_map'] == 'mapbox' || false;
               if ( $is_mapbox ) {
                   $type = 'mapbox';
               }
            }

            add_settings_field(
                'mks_map_' . $field['id'] . '_id',
                esc_html__( $field['title'], MKS_MAP_TEXTDOMAIN ),
                $callback,
                'meks-easy-maps-osm',
                $section_id,
                array( 'value' => esc_attr( $value ), 'type' => esc_attr( $type ) )
            );
        }

        if( !mks_map_is_trawell_active() ){

            add_settings_field(
                'mks_map_trawell_osm_note_id',
                esc_html__('Need more features?', MKS_MAP_TEXTDOMAIN ),
                array($this, 'print_trawell_note_field'),
                'meks-easy-maps-osm',
                $section_id,
                array( 'value' => esc_attr( $value ) )
            );
        }

    }


    /**
     * Print the map style field
     */
    public function print_osm_default_map_field( array $args ) {
        ?>
        <label class="meks-osm-map-type-osm" for="mks_map_osm_default_map"><input id="mks_map_osm_default_map" type="radio" name="meks-easy-maps-osm[osm_default_map]" value="osm" <?php checked( "osm", $args['value'] ); ?>><?php _e( 'OSM', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label class="meks-osm-map-type-mapbox" for="mks_map_osm_default_map_2"><input id="mks_map_osm_default_map_2" type="radio" name="meks-easy-maps-osm[osm_default_map]" value="mapbox" <?php checked( "mapbox", $args['value'] ); ?>><?php _e( 'Mapbox (require API token key)', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }

    /**
     * Print the Mapbox API token field
     */
    public function print_osm_mapbox_token_field( array $args ) {

        $class = $args['type'] == 'osm' ? 'mapbox-hidden' : 'mapbox-show';

        printf( '<input type="text" class="mks-mapbox-token %s" id="mks_map_osm_mapbox_token" name="meks-easy-maps-osm[osm_mapbox_token]" value="%s" style="width: 50%%" />', $class, $args['value'] );
        echo wp_kses_post( sprintf( '<p class="description mks-mapbox-token">'.__( 'How do I get my <a target="_blank" href="%s">Mapbox API token key</a>?', MKS_MAP_TEXTDOMAIN ), esc_url( 'https://docs.mapbox.com/accounts/guides/tokens/#creating-and-managing-access-tokens' ) ) . '</p>' );
    }

    /**
     * Print the Map Type input
     */
    public function print_osm_mapbox_styles_field( array $args ) {

        $types = array( 
            'streets-v11'           => 'Streets', 
            'light-v10'             => 'Light', 
            'dark-v10'              => 'Dark', 
            'satellite-v9'          => 'Satellite',
            'satellite-streets-v11' => 'Satellite Streets' 
        );
        $class = $args['type'] == 'osm' ? 'mapbox-hidden' : 'mapbox-show';

        ?>
        <select class="<?php echo esc_attr( $class ); ?>" name="meks-easy-maps-osm[osm_mapbox_styles]" id="mks_map_osm_mapbox_styles">
            <?php foreach ( $types as $type => $name ) :?>
                <option value="<?php echo esc_attr( $type ); ?>" <?php selected( $args['value'], $type ) ?>><?php echo esc_html( $name ); ?></option>
            <?php endforeach; ?>
        </select>
        <?php
    }

    /**
     * Print the Clustering radio buttons
     */
    public function print_osm_cluster_enable_field( array $args ) {
        ?>
        <label for="mks_map_osm_cluster_enable"><input id="mks_map_osm_cluster_enable" type="radio" name="meks-easy-maps-osm[osm_cluster_enable]" value="on" <?php checked( "on", $args['value'] ); ?>><?php _e( 'On', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_osm_cluster_enable_2"><input id="mks_map_osm_cluster_enable_2" type="radio" name="meks-easy-maps-osm[osm_cluster_enable]" value="off" <?php checked( "off", $args['value'] ); ?>><?php _e( 'Off', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }


    /**
     * Print the Pin Color input
     */
    public function print_osm_pin_color_field( array $args ) {
        printf( '<input type="text" id="mks_map_osm_pin_color" name="meks-easy-maps-osm[osm_pin_color]" value="%s" />',  $args['value'] );
    }

    /**
     * Print the Cluster Color input
     */
    public function print_osm_cluster_color_field( array $args ) {
        printf('<input type="text" id="mks_map_osm_cluster_color" name="meks-easy-maps-osm[osm_cluster_color]" value="%s" />', $args['value'] );
    }

        /**
     * Print the Polylines radio buttons
     */
    public function print_osm_print_polylines_field( array $args ) {
        ?>
        <label for="mks_map_enable_osm_print_polylines"><input id="mks_map_enable_osm_print_polylines" type="radio" name="meks-easy-maps-osm[osm_print_polylines]" value="on" <?php checked( "on", $args['value'] ); ?>><?php _e( 'On', MKS_MAP_TEXTDOMAIN ) ?></label>
        <br>
        <label for="mks_map_disable_osm_print_polylines"><input id="mks_map_disable_osm_print_polylines_2" type="radio" name="meks-easy-maps-osm[osm_print_polylines]" value="off" <?php checked( "off", $args['value'] ); ?>><?php _e( 'Off', MKS_MAP_TEXTDOMAIN ) ?></label>
        <?php
    }

    /**
     * Print the ZOOM input
     */
    public function print_osm_polylines_limit_field( array $args ) {
        printf(
            '<input type="number" class="small-text" id="mks_map_polylinesLimit" name="meks-easy-maps-osm[osm_polylines_limit]" value="%s" min="1" max="100" step="1" />',
            $args['value']
        );

        printf( '<p class="description">'.esc_html('Display polylines only if the map has less pins than the number specified above.', MKS_MAP_TEXTDOMAIN).'</p>');
    }

    /**
     * Print the single post map display
     */
    public function print_osm_single_map_field( array $args ) {
        $checked = $args['value'];
        printf(
            '<label><input type="radio" id="mks_map_osm_single_map" name="meks-easy-maps-osm[osm_single_map]" value="%s" %s/> %s</label><br>',
            'above',
            checked( $checked, 'above', false ),
            __( 'Location above the content', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_osm_single_map_2" name="meks-easy-maps-osm[osm_single_map]" value="%s" %s/> %s</label><br>',
            'below',
            checked( $checked, 'below', false ),
            __( 'Location below the content', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_osm_single_map_3" name="meks-easy-maps-osm[osm_single_map]" value="%s" %s/> %s</label><br>',
            'none',
            checked( $checked, 'none', false ),
            __( 'Nothing', MKS_MAP_TEXTDOMAIN )
        );
    }

    /**
     * Print the category map display field
     */
    public function print_osm_category_map_field( array $args ) {
        $checked = $args['value'];
        printf(
            '<label><input type="radio" id="mks_map_show_on_map" name="meks-easy-maps-osm[osm_category_map]" value="%s" %s/> %s</label><br>',
            'posts',
            checked( $checked, 'posts', false ),
            __( 'All category posts with a location set', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_show_on_map_2" name="meks-easy-maps-osm[osm_category_map]" value="%s" %s/> %s</label><br>',
            'categories',
            checked( $checked, 'categories', false ),
            __( 'Category location', MKS_MAP_TEXTDOMAIN )
        );
        printf(
            '<label><input type="radio" id="mks_map_show_on_map_3" name="meks-easy-maps-osm[osm_category_map]" value="%s" %s/> %s</label>',
            'none',
            checked( $checked, 'none', false ),
            __( 'Nothing', MKS_MAP_TEXTDOMAIN )
        );

        printf( '<p class="description">'.esc_html('Note: Your theme needs to utilize category description support in order to use this option.', MKS_MAP_TEXTDOMAIN).'</p>');
    }

    


    /**
     * HELPER FUNCTIONS
     */


    /**
     * Sanitize each setting field as needed
     *
     * @param unknown $input array $input Contains all settings fields as array keys
     * @return mixed
     */
    public function sanitize( $input ) {
        
        if ( empty( $this->fields ) || empty( $input ) ) {
            return false;
        }


        $new_input = array();
        foreach ( $this->fields as $field ) {
            if ( isset( $input[$field['id']] ) )
                $new_input[$field['id']] = $this->sanitize_field( $input[$field['id']], $field['sanitize'] );
        }

        return $new_input;
    }

    /**
     * Dynamically sanitize field values
     *
     * @param unknown $value
     * @param unknown $sensitization_type
     * @return int|string
     */
    private function sanitize_field( $value, $sensitization_type ) {
        switch ( $sensitization_type ) {
        case "absint":
            return absint( $value );
            break;
        case "radio":
            if ( empty( $value ) || !in_array( $value, array( 'on', 'off', 'google', 'osm', 'mapbox' ) ) ) {
                $value = 'off';
            }
            return sanitize_text_field( $value );
            break;
        default:
        case "text":
            return sanitize_text_field( $value );
            break;
        }
    }

}
