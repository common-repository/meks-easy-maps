(function($) {

    "use strict";

    var Mks_Maps = {

        settings: {},
        items: {},
        markers: [], // array of all markets on the map used for clustering
        polylines: [],
        bounds: {},
        multiple: false,
        pinColor: "#FF0000",
        infoBox: new InfoBox(),
        infoBoxSettings: {},
        fittedToContainer: false,
        lastValidCenter: {},
        minZoomLevel: 2,

        init: function() {
            var get_maps = $('.mks-maps');
            get_maps.each(this.initializeMapEachCallback);
        },

        initializeMapEachCallback: function() {
            var $this = $(this);

            Mks_Maps.resetData();

            Mks_Maps.settings = Mks_Maps.getMapSettings($this);
            Mks_Maps.items = $this.data('items');

            if (mks_maps_empty(Mks_Maps.settings) || mks_maps_empty(Mks_Maps.items)) {
                console.error('Maps and items must be passed tru map div like this "data-settings" and "data-maps" in json format');
                return false;
            }


            var id = $this.attr('id'),
                map = document.getElementById(id),
                streetView = Mks_Maps.settings.streetView;

            delete(Mks_Maps.settings.streetView);

            Mks_Maps.multiple = Object.keys(Mks_Maps.items).length > 1;

            if (mks_maps_empty(map)) {
                console.error('Maps div not found');
                return false;
            }

            Mks_Maps.settings.center = Mks_Maps.getMapCenter();
            Mks_Maps.settings.styles = (!mks_maps_empty(Mks_Maps.settings.styles)) ? JSON.parse(Mks_Maps.settings.styles) : [];

            // Map initialization
            map = new google.maps.Map(map, Mks_Maps.settings);
            map.mks_maps_data = {
                center: Mks_Maps.settings.center
            };

            if (Mks_Maps.setStreetView(streetView, id, map)) {
                return true; // Street view added no need for going down to pins and infoboxes
            }

            if (mks_maps_empty(Mks_Maps.settings.center)) {
                Mks_Maps.bounds = new google.maps.LatLngBounds();
                map.mks_maps_data.bounds = Mks_Maps.bounds;
            }
            // Infobox prepare
            Mks_Maps.prepareInfoBoxSettings();

            Mks_Maps.printItems(map, Mks_Maps.items, Mks_Maps.afterMapInitialization);
        },

        setStreetView: function(streetView, id, map) {

            if (mks_maps_empty(streetView) || !streetView || Mks_Maps.multiple) {
                return false;
            }

            var panorama = new google.maps.StreetViewPanorama(
                document.getElementById(id), {
                    position: Mks_Maps.settings.center,
                    zoomControl: false,
                    scrollwheel: false,
                    streetViewControl: false,
                    disableDefaultUI: true,
                    pov: {
                        heading: 34,
                        pitch: 10
                    }
                });

            map.setStreetView(panorama);
            return true;
        },

        getMapSettings: function($elem) {
            var settings = $elem.data('settings');

            if (!mks_maps_empty(settings.pinColor)) {
                Mks_Maps.pinColor = settings.pinColor;
                delete(settings.pinColor);
            }

            return settings;
        },

        getMapCenter: function() {
            if (!Mks_Maps.multiple) {
                if (!mks_maps_empty(Mks_Maps.items[0].latitude) && !mks_maps_empty(Mks_Maps.items[0].longitude)) {
                    return new google.maps.LatLng(parseFloat(Mks_Maps.items[0].latitude), parseFloat(Mks_Maps.items[0].longitude));
                } else {
                    return new google.maps.LatLng(44.787197, 20.457273);
                }
            }

        },

        bringMapBackInView: function(map) {
            map.centered = false;

            google.maps.event.addListener(map, 'zoom_changed', function() {

                if (map.centered) {
                    return;
                }

                map.centered = true;

                Mks_Maps.checkMapBounds(map);
            });
        },

        checkMapBounds: function(map) {
            var bounds = map.getBounds();
            var sLat = bounds.getSouthWest().lat();
            var nLat = bounds.getNorthEast().lat();

            if (bounds.getSouthWest().lng() === -180 && bounds.getNorthEast().lng() === 180 && map.getCenter().lng() > 10) {
                map.setCenter(new google.maps.LatLng(map.getCenter().lat(), 0));
            }

            if (sLat < -85 || nLat > 85) {
                //the map has gone beyone the world's max or min latitude - gray areas are visible
                map.setZoom(parseInt(map.getZoom() + 1));
            }
        },

        printItems: function(map, items, doneCallback) {
            $.each(items, function(i, item) {
                if (mks_maps_empty(item.address) || mks_maps_empty(item.latitude) || mks_maps_empty(item.longitude)) {
                    return true;
                }

                var position = new google.maps.LatLng(item.latitude, item.longitude),
                    markerContent = Mks_Maps.printMarker(item);


                if (mks_maps_empty(Mks_Maps.settings.center)) {
                    Mks_Maps.bounds.extend(position);
                }

                var pinColor = Mks_Maps.pinColor;
                if (!mks_maps_empty(item.pinColor)) {
                    pinColor = item.pinColor;
                }

                var marker = new google.maps.Marker({
                    position: position,
                    content: markerContent,
                    link: item.link,
                    map: map,
                    icon: mks_maps_marker_icon(pinColor),
                    id: item
                });

                Mks_Maps.markers.push(marker);

                if (!mks_maps_empty(Mks_Maps.settings.printPolylines)) {
                    var polyline = {
                        lat: Number(item.latitude),
                        lng: Number(item.longitude)
                    };

                    Mks_Maps.polylines.push(polyline);
                }

                if (Mks_Maps.settings.infoBox) {
                    Mks_Maps.printInfoBox(map, marker);
                }
            });

            if (typeof doneCallback === 'function') {
                doneCallback(map);
            }
        },

        printMarker: function(item) {
            var html = '<div class="mks-map-info-box">';

            if (!mks_maps_empty(item.thumbnail)) {
                html += '<div class="mks-map-entry-image"><a href="' + item.link + '">' + item.thumbnail + '</a></div>';
            }

            html += '<span class="mks-map-info-box-close"><span class="mks-map-x"></span></span><div class="mks-map-element-pos-abs">';

            if (!mks_maps_empty(Mks_Maps.settings.display.format) && !mks_maps_empty(item.format)) {
                html += '<div class="mks-map-entry-format">';
                html += '<div class="mks-map-format-icon">' + item.format + '</div>';
                html += '</div>';
            }

            if (!mks_maps_empty(Mks_Maps.settings.display.category) && !mks_maps_empty(item.categories)) {
                html += '<div class="mks-map-entry-category">' + item.categories + '</div>';
            }

            html += '<div class="mks-map-entry-header">' +
                '<h6 class="h6 entry-title"><a href="' + item.link + '">' + item.title + '</a></h6>' +
                '</div>';

            if (!mks_maps_empty(Mks_Maps.settings.display.meta) && Mks_Maps.settings.display.meta && !mks_maps_empty(item.meta)) {
                html += '<div class="mks-map-entry-meta">' + item.meta + '</div>';
            }

            if (!mks_maps_empty(Mks_Maps.settings.display.excerpt) && Mks_Maps.settings.display.excerpt && !mks_maps_empty(item.excerpt)) {
                html += '<div class="mks-map-entry-content">' + item.excerpt + '</div>';
            }

            html += '</div>';

            return html;
        },

        prepareInfoBoxSettings: function() {
            var infoBoxWrapper = document.createElement("div");
            infoBoxWrapper.className = 'mks-map-wrapper';

            Mks_Maps.infoBoxSettings = {
                content: infoBoxWrapper,
                disableAutoPan: false,
                alignBottom: true,
                maxWidth: 0,
                pixelOffset: new google.maps.Size(-60, -40),
                zIndex: null,
                boxStyle: {
                    width: "280px"
                },
                closeBoxMargin: "0px",
                closeBoxURL: "",
                enableEventPropagation: false
            };
        },

        printInfoBox: function(map, marker) {
            if (mks_maps_empty(Mks_Maps.infoBoxSettings) || mks_maps_empty(map) || mks_maps_empty(marker)) {
                return false;
            }

            google.maps.event.addListener(marker, 'click', function() {

                Mks_Maps.infoBox.close();

                Mks_Maps.infoBox.setOptions(Mks_Maps.infoBoxSettings);
                Mks_Maps.infoBox.setContent(this.content);
                Mks_Maps.infoBox.open(map, this);

                setTimeout(function() {
                    Mks_Maps.checkMapBounds(map);
                }, 100);

                google.maps.event.addListener(Mks_Maps.infoBox, 'domready', function() {
                    $('.mks-map-info-box-close').click(function() {
                        Mks_Maps.infoBox.close();
                    });
                });
            });
        },

        afterMapInitialization: function(map) {
            if (mks_maps_empty(Mks_Maps.settings.center)) {
                map.fitBounds(Mks_Maps.bounds);

                Mks_Maps.printClusters(map);

                Mks_Maps.printPolylines(map);
            }

            Mks_Maps.bringMapBackInView(map);
        },

        printPolylines: function(map) {
            var itemsCount = Object.keys(Mks_Maps.items).length;

            if (mks_maps_empty(Mks_Maps.polylines) || parseInt(Mks_Maps.settings.polylinesLimit) <= itemsCount) {
                return false;
            }

            // Add polylines to map
            var polylines_path = new google.maps.Polyline({
                path: Mks_Maps.polylines,
                geodesic: true,
                strokeColor: Mks_Maps.settings.clusterColor,
                strokeOpacity: 1,
                strokeWeight: 2
            });

            polylines_path.setMap(map);
        },

        printClusters: function(map) {

            if(!Mks_Maps.settings.clusterEnable){
                return;
            }

            var marker_clusters_style = [{
                    width: 50,
                    height: 50,
                    url: mks_maps_cluster_icon(Mks_Maps.settings.clusterColor),
                    textColor: Mks_Maps.settings.clusterTextColor,
                    textSize: Mks_Maps.settings.clusterTextSize
                }],
                marker_clusters_options = {
                    styles: marker_clusters_style,
                    imagePath: mks_maps_cluster_icon(Mks_Maps.settings.clusterColor),
                    minClusterSize: 2,
                    maxZoom: 17
                };

            return new MarkerClusterer(map, Mks_Maps.markers, marker_clusters_options);
        },

        resetData: function() {
            Mks_Maps.markers = [];
            Mks_Maps.polylines = [];
            Mks_Maps.settings = {};
            Mks_Maps.items = {};
            Mks_Maps.markers = [];
            Mks_Maps.polylines = [];
            Mks_Maps.bounds = {};
            Mks_Maps.multiple = false;
            Mks_Maps.pinColor = "#FF0000";
            Mks_Maps.infoBox = new InfoBox();
            Mks_Maps.infoBoxSettings = {};
        }
    };

    window.Mks_Maps = Mks_Maps;

    $(document).ready(function() {
        Mks_Maps.init();
    });


    // ********** Helpers ************

    // custom marker svg icon
    function mks_maps_marker_icon(color) {
        return {
            path: 'M60,14.147c-17.855,0-32.331,14.475-32.331,32.331C27.669,76.314,60,107.292,60,107.292s32.331-34.111,32.331-60.815  C92.331,28.622,77.855,14.147,60,14.147z M60.001,58.015c-7.4,0-13.398-5.999-13.398-13.398c0-7.399,5.999-13.398,13.398-13.398  c7.399,0,13.397,5.999,13.397,13.398C73.398,52.016,67.4,58.015,60.001,58.015z',
            fillColor: color,
            fillOpacity: 1,
            strokeOpacity: 0,
            strokeWeight: 1,
            scale: 0.5,
            anchor: new google.maps.Point(60, 102)
        };
    }

    // custom cluster svg icon
    function mks_maps_cluster_icon(color) {
        var rgba_color = mks_maps_hex_to_rgba(color, 0.2);
        var encoded = window.btoa(
            '<svg xmlns="http://www.w3.org/2000/svg" width="53" height="52">' +
            '<circle cx="25" cy="25" r="25" fill="' + rgba_color + '"/>' +
            '<circle cx="25" cy="25" r="18" fill="' + color + '"/>' +
            '</svg>');
        return ('data:image/svg+xml;base64,' + encoded);
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