(function ($) {

    "use strict";

    $(document).ready(function () {
        
        var Mks_Maps_Admin = {

            lat: $("#mks-map-geolocation-latitude").val(),
            long: $("#mks-map-geolocation-longitude").val(),
            options: {
                zoom: 10,
                center: new google.maps.LatLng(44.787197, 20.457273),
                mapTypeId: google.maps.MapTypeId.ROADMAP
            },

            init: function () {
                this.setMapLocation();
                this.printMap(this.afterMapPrint);

                $("#mks-map-geolocation-address").keypress(this.preventUpdateOnEnter);
            },

            afterMapPrint: function (map) {
                var marker = Mks_Maps_Admin.setMarker(map);
                Mks_Maps_Admin.setAutoComplete(map, marker);
                Mks_Maps_Admin.draggableMarker(map, marker);

            },

            setMapLocation: function () {
                if (!mks_maps_admin_empty(Mks_Maps_Admin.lat) && !mks_maps_admin_empty(Mks_Maps_Admin.long)) {
                    Mks_Maps_Admin.options.center = new google.maps.LatLng(Mks_Maps_Admin.lat, Mks_Maps_Admin.long);
                    return true;
                }
            },

            printMap: function (callback) {
                var map = new google.maps.Map(document.getElementById('mks-map-geolocation-map'), Mks_Maps_Admin.options);
                callback(map);
            },

            setAutoComplete: function (map, marker) {
                var input = document.getElementById('mks-map-geolocation-address'),
                    autoComplete = new google.maps.places.Autocomplete(input);

                google.maps.event.addListener(autoComplete, 'place_changed', function () {
                    var place = autoComplete.getPlace();
                    if (!place.geometry) {
                        // User entered the name of a Place that was not suggested and
                        // pressed the Enter key, or the Place Details request failed.
                        window.alert("No details available for input: '" + place.name + "'");
                        return false;
                    }

                    if (place.geometry.viewport) {
                        map.fitBounds(place.geometry.viewport);
                    } else {
                        map.setCenter(place.geometry.location);
                        map.setZoom(17);
                    }
                    marker.setPosition(place.geometry.location);
                    marker.setVisible(true);

                    $("#mks-map-geolocation-latitude").val(place.geometry.location.lat());
                    $("#mks-map-geolocation-longitude").val(place.geometry.location.lng());
                });
            },

            setMarker: function (map) {
                return new google.maps.Marker({
                    position: Mks_Maps_Admin.options.center,
                    draggable: true,
                    map: map,
                    title: 'Post Location: ' + $("#mks-map-geolocation-longitude").val(),
                    icon: mks_maps_marker_icon('#f04236'),

                });
            },

            draggableMarker: function (map, marker) {
                google.maps.event.addListener(marker, 'dragend', function (e) {
                    mks_maps_place_marker(e.latLng, map, marker);
                    mks_maps_reverse_geocode(e.latLng);
                });
            },

            preventUpdateOnEnter: function (e) {
                if (e.keyCode !== 13) {
                    return true;
                }

                e.preventDefault();
            }
        };
        Mks_Maps_Admin.init();

        function mks_maps_place_marker(location, map, marker) {
            marker.setPosition(location);
            map.setCenter(location);
            if (!mks_maps_admin_empty(location.lat()) && !mks_maps_admin_empty(location.lng())) {
                $("#mks-map-geolocation-latitude").val(location.lat());
                $("#mks-map-geolocation-longitude").val(location.lng());
            }

        }

        function mks_maps_reverse_geocode(location) {
            var geocoder = new google.maps.Geocoder();
            if (geocoder) {
                geocoder.geocode({
                    latLng: location
                }, function (results, status) {
                    if (status === google.maps.GeocoderStatus.OK) {
                        $('#mks-map-geolocation-address').attr('value' ,results[0].formatted_address);
                    }else{
                        window.alert("No details available for that address");
                    }
                });
            }
        }

        // function for custom marker symbol
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


        function mks_maps_admin_empty(variable) {

            if (typeof variable === 'undefined') {
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
    });
})(jQuery);