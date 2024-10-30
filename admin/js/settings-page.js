
(function($) {
    'use strict';

    $(document).ready(function() {

        $("#mks_map_clusterColor, #mks_map_pinColor, #mks_map_osm_cluster_color, #mks_map_osm_pin_color").wpColorPicker();

        $('body').on('click', '.meks-notice .notice-dismiss', function(){

            $.ajax( {
                url: ajaxurl,
                method: "POST",
                data: {
                    action: 'meks_remove_notification'
                }
            });

        });


        if ( $('input[name="meks-easy-maps-osm[osm_default_map]"]:checked').val() == 'osm' ) {
            $('.mapbox-hidden').addClass('mapbox-show').closest('tr').css({display: 'none'});
        }

        if ( $('input[name="meks-easy-maps-osm[osm_default_map]"]:checked').val() == 'mapbox' ) {
            $('.mapbox-show').addClass('mapbox-hidden').closest('tr').css({display: 'table-row'});
        }

        $('body').on( 'click', '.meks-osm-map-type-osm, .meks-osm-map-type-mapbox', function(e) {
            if( $(this).find('input').val() == 'osm' ) {
                $('.mapbox-hidden').removeClass('mapbox-hidden').addClass('mapbox-show').closest('tr').css({display: 'none'});
            }
            if( $(this).find('input').val() == 'mapbox' ) {
                $('.mapbox-show').removeClass('mapbox-show').addClass('mapbox-hidden').closest('tr').css({display: 'table-row'});
            }
        });

    })
}(jQuery));