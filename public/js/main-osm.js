(function($) {

    "use strict";

    var OSM_Maps = {

        settings: {},
        items: {},
        markers: [], // array of all markets on the map
        clusters: L.markerClusterGroup({
            maxClusterRadius: 60,
            iconCreateFunction: function (cluster) {                 
                return L.divIcon({className: 'meks-cluster', html: mks_maps_cluster_icon( cluster._childCount, OSM_Maps.settings.osm_cluster_color ), iconSize: L.point(50, 50) });
            }
        }),
        polylines: [],
        bounds: {},
        pinColor: "#FF0000",
        fittedToContainer: false,
        lastValidCenter: {},
        minZoomLevel: 2,

        init: function() {
            var get_maps = $('.mks-maps');
            get_maps.each(this.initializeMapEachCallback);
        },

        initializeMapEachCallback: function() {
            var $this = $(this);

            OSM_Maps.resetData();
            OSM_Maps.settings = OSM_Maps.getMapSettings($this);
            OSM_Maps.items = $this.data('items');
            OSM_Maps.settings.center = OSM_Maps.getMapCenter();
            //OSM_Maps.settings.styles = (!mks_maps_empty(OSM_Maps.settings.styles)) ? JSON.parse(OSM_Maps.settings.styles) : [];

            if (mks_maps_empty(OSM_Maps.settings) || mks_maps_empty(OSM_Maps.items)) {
                console.error('Maps and items must be passed tru map div like this "data-settings" and "data-maps" in json format');
                return false;
            }
            
            // Map initialization
            var id = $this.attr('id'),
                map = L.map(id, {
                    center: OSM_Maps.settings.center,
                    zoom: OSM_Maps.settings.zoom,
                    scrollWheelZoom: false
                });

            if (mks_maps_empty(map)) {
                console.error('Maps div not found');
                return false;
            }


            if ( OSM_Maps.settings.osm_default_map == 'osm' || mks_maps_empty( OSM_Maps.settings.osm_mapbox_token ) ) {
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
            }

            if ( OSM_Maps.settings.osm_default_map == 'mapbox' && !mks_maps_empty( OSM_Maps.settings.osm_mapbox_token ) ) {
                L.tileLayer('https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token='+OSM_Maps.settings.osm_mapbox_token, {
                    attribution: 'Map data &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors, Imagery © <a href="https://www.mapbox.com/">Mapbox</a>',
                    maxZoom: 18,
                    id: 'mapbox/'+ OSM_Maps.settings.osm_mapbox_styles,
                    tileSize: 512,
                    zoomOffset: -1,
                }).addTo(map);
            }
    
            OSM_Maps.printItems(map, OSM_Maps.items, OSM_Maps.afterMapInitialization);

        },

        printItems: function(map, items, doneCallback) {

            $.each(items, function(i, item) {

                if (mks_maps_empty(item.address) || mks_maps_empty(item.latitude) || mks_maps_empty(item.longitude)) {
                    return true;
                }

                var markerContent = OSM_Maps.getMarkerContent(item);

                var pinColor = !mks_maps_empty(OSM_Maps.settings.osm_pin_color) ? OSM_Maps.settings.osm_pin_color : pinColor;
                if (!mks_maps_empty(item.pinColor)) {
                    pinColor = item.pinColor;
                }
                
                var pinIcon = L.divIcon({className: 'meks-icon-wrapper', html: mks_maps_marker_icon(pinColor) });


                if ( ! mks_maps_empty( OSM_Maps.settings.osm_cluster_enable ) ) {
                    var marker = L.marker([item.latitude, item.longitude ], {
                        icon: pinIcon,
                    });
    
                    OSM_Maps.markers.push(marker);
                    OSM_Maps.clusters.addLayer(marker);
                } else {
                    OSM_Maps.markers.push([item.latitude, item.longitude ]);
                    var marker = L.marker([item.latitude, item.longitude ], {
                        icon: pinIcon,
                    }).addTo(map);
                }


                if ( !mks_maps_empty(OSM_Maps.settings.osm_print_polylines) && OSM_Maps.settings.osm_polylines_limit >= i ) {
                    OSM_Maps.polylines.push([item.latitude, item.longitude ]);
                }

               

                OSM_Maps.printInfoBox(map, marker, markerContent);

            });

            if (typeof doneCallback === 'function') {
                doneCallback(map);
            }
        },

        getMarkerContent: function(item) {
            var html = '<div class="mks-map-info-box">';

            if (!mks_maps_empty(item.thumbnail)) {
                html += '<div class="mks-map-entry-image"><a href="' + item.link + '">' + item.thumbnail + '</a></div>';
            }

            html += '<span class="mks-map-info-box-close"><span class="mks-map-x"></span></span><div class="mks-map-element-pos-abs">';

            if (!mks_maps_empty(OSM_Maps.settings.display.format) && !mks_maps_empty(item.format)) {
                html += '<div class="mks-map-entry-format">';
                html += '<div class="mks-map-format-icon">' + item.format + '</div>';
                html += '</div>';
            }

            if (!mks_maps_empty(OSM_Maps.settings.display.category) && !mks_maps_empty(item.categories)) {
                html += '<div class="mks-map-entry-category">' + item.categories + '</div>';
            }

            html += '<div class="mks-map-entry-header">' +
                '<h6 class="h6 entry-title"><a href="' + item.link + '">' + item.title + '</a></h6>' +
                '</div>';

            if (!mks_maps_empty(OSM_Maps.settings.display.meta) && OSM_Maps.settings.display.meta && !mks_maps_empty(item.meta)) {
                html += '<div class="mks-map-entry-meta">' + item.meta + '</div>';
            }

            if (!mks_maps_empty(OSM_Maps.settings.display.excerpt) && OSM_Maps.settings.display.excerpt && !mks_maps_empty(item.excerpt)) {
                html += '<div class="mks-map-entry-content">' + item.excerpt + '</div>';
            }

            html += '</div>';

            return html;
        },

        printInfoBox: function(map, marker, markerContent) {

            if ( mks_maps_empty(map) || mks_maps_empty(marker)) {
                return false;
            }

            marker.bindPopup(markerContent, {
                keepInView: true,
                closeButton: false,
            });

            $('body').on('click', '.mks-map-info-box-close', function() {
                marker.closePopup();
            })
        },


        afterMapInitialization: function(map) {

            if ( ! mks_maps_empty( OSM_Maps.settings.osm_cluster_enable ) ) {
                map.addLayer(OSM_Maps.clusters);
                map.fitBounds(OSM_Maps.clusters.getBounds(), {maxZoom: OSM_Maps.settings.zoom });
            } else {
                map.fitBounds(OSM_Maps.markers, {maxZoom: OSM_Maps.settings.zoom });
            }

            if ( ! mks_maps_empty( OSM_Maps.settings.osm_print_polylines ) ) { 
                L.polyline( OSM_Maps.polylines, {color: OSM_Maps.settings.osm_cluster_color}).addTo(map);
            }

            OSM_Maps.bringMapBackInView(map)
        },


        bringMapBackInView: function(map) {

            map.on('popupopen', function() {
                //set timeout to wait default map panning
                setTimeout(() => {
                    OSM_Maps.checkMapBounds(map);
                }, 200);
            });
        },

        checkMapBounds: function(map) {
            var bounds = map.getBounds();
            var sLat = bounds.getSouthWest().lat;
            var nLat = bounds.getNorthEast().lat;

            if (sLat < -85 || nLat > 85) {
                //the map has gone beyone the world's max or min latitude - gray areas are visible
                map.setZoom(parseInt(map.getZoom() + 1));
            }
        },


        getMapSettings: function($elem) {
            var settings = $elem.data('settings');

            if (!mks_maps_empty(settings.osm_pin_color)) {
                OSM_Maps.pinColor = settings.osm_pin_color;
                //delete(settings.osm_pin_color);
            }

            return settings;
        },

        getMapCenter: function() {
            if ( !mks_maps_empty(OSM_Maps.items) ) { 
                if (!mks_maps_empty(OSM_Maps.items[0].latitude) && !mks_maps_empty(OSM_Maps.items[0].longitude)) {
                    return [parseFloat(OSM_Maps.items[0].latitude), parseFloat(OSM_Maps.items[0].longitude)];
                } else {
                    return [44.787197, 20.457273];
                }
            }
        },

       
        resetData: function() {
            OSM_Maps.settings = {};
            OSM_Maps.items = {};
            OSM_Maps.markers = [];
            OSM_Maps.clusters = L.markerClusterGroup({
                maxClusterRadius: 60,
                iconCreateFunction: function (cluster) {                 
                    return L.divIcon({className: 'meks-cluster', html: mks_maps_cluster_icon( cluster._childCount, OSM_Maps.settings.osm_cluster_color ), iconSize: L.point(50, 50) }); 
                }
            });
            OSM_Maps.polylines = [];
            OSM_Maps.polylines = [];
            OSM_Maps.bounds = {};
            OSM_Maps.pinColor = "#FF0000";
        }
    };

    window.OSM_Maps = OSM_Maps;

    $(document).ready(function() {
        OSM_Maps.init();
    });


    // ********** Helpers ************

    // custom marker svg icon
    function mks_maps_marker_icon(color) {
        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="27.668999999999997 14.147 64.662 93.145" style="width:34px; position:relative;top:-40px;left:-12px"><path d="M60,14.147c-17.855,0-32.331,14.475-32.331,32.331C27.669,76.314,60,107.292,60,107.292s32.331-34.111,32.331-60.815  C92.331,28.622,77.855,14.147,60,14.147z M60.001,58.015c-7.4,0-13.398-5.999-13.398-13.398c0-7.399,5.999-13.398,13.398-13.398  c7.399,0,13.397,5.999,13.397,13.398C73.398,52.016,67.4,58.015,60.001,58.015z" fill="' + color + '"/>';
    }

    // custom cluster svg icon
    function mks_maps_cluster_icon(count,color) {
        
        var rgba_color = mks_maps_hex_to_rgba(color, 0.2);
        
        var encoded = window.btoa(
            '<svg xmlns="http://www.w3.org/2000/svg" width="53" height="52">' +
            '<circle cx="25" cy="25" r="25" fill="' + rgba_color + '"/>' +
            '<circle cx="25" cy="25" r="18" fill="' + color + '"/>' +
            '</svg>');

        return '<div style="background-image: url(&quot;data:image/svg+xml;base64,'+ encoded +'&quot;); background-position:0px 0px; height:50px; line-height:50px; width:50px; text-align:center; cursor:pointer;  color:'+ OSM_Maps.settings.clusterTextColor +'; position:absolute; font-size:'+ OSM_Maps.settings.clusterTextSize +'px; font-family:Arial, sans-serif; font-weight:bold;">'+ count +'</div>'

    }

    // convert hex color to rgba
    function mks_maps_hex_to_rgba(hex, alpha) {
        var c;
        if (/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)) {
            c = hex.substring(1).split('');
            if (c.length == 3) {
                c = [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c = '0x' + c.join('');
            return 'rgba(' + [(c >> 16) & 255, (c >> 8) & 255, c & 255].join(',') + ',' + alpha + ')';
        }
        return null;
    }

    function mks_maps_empty(variable) {

        if (typeof variable === 'undefined') {
            return true;
        }

        if (variable === 0 || variable === '0') {
            return true;
        }

        if (variable === null) {
            return true;
        }

        if (variable.length === 0) {
            return true;
        }

        if (variable === "") {
            return true;
        }

        if (typeof variable === 'object' && $.isEmptyObject(variable)) {
            return true;
        }

        return false;
    }

})(jQuery);