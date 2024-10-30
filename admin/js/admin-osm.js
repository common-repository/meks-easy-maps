(function ($) {



    $(document).ready(function () {
        
        var Mks_Maps_Admin = {

            lat: $("#mks-map-geolocation-latitude").val(),
            long: $("#mks-map-geolocation-longitude").val(),
            options: {
                zoom: 10,
                center: ['44.787197', '20.457273'],
                scrollWheelZoom: false
            },
            pinIcon: L.divIcon({
                className: 'meks-icon-wrapper', 
                html: mks_maps_marker_icon('#f04236') 
            }),

            init: function () {
                this.setMapLocation();
                this.printMap();

                //$("#mks-map-geolocation-address").keypress(this.preventUpdateOnEnter);
            },

            setMapLocation: function () {
                if (!mks_maps_admin_empty(Mks_Maps_Admin.lat) && !mks_maps_admin_empty(Mks_Maps_Admin.long)) {
                    Mks_Maps_Admin.options.center = [Mks_Maps_Admin.lat, Mks_Maps_Admin.long];
                    return true;
                }
            },

            printMap: function () {

                var mapId = document.getElementById('mks-map-geolocation-map');
                var map = L.map( mapId, Mks_Maps_Admin.options );

                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                  attribution: '&copy; <a href="https://osm.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);

                // fix for late map container loading
                setTimeout(() => {
                    map.invalidateSize();
                }, 500);

                this.afterMapPrint(map);

            },

            afterMapPrint: function (map) {

                var searchControl = L.esri.BootstrapGeocoder.search({
                    inputTag: 'mks-map-geolocation-address',
                    placeholder: 'Searh for places or address'
                }).addTo(map);

                var mapLayer = L.layerGroup().addTo(map);
                
                this.setMapMarker(mapLayer, map);
                this.setGeolocationOnSearch(searchControl, mapLayer);

            },

            setMapMarker: function (mapLayer, map) {
                
                mapLayer.addLayer(L.marker([Mks_Maps_Admin.lat, Mks_Maps_Admin.long ], {
                    icon: Mks_Maps_Admin.pinIcon,
                }));

                $('body').on( 'click', '.meks-map-button-geocode', function(e) {
                    e.preventDefault;

                    var lat = $("#mks-map-geolocation-latitude").val();
                    var lng = $("#mks-map-geolocation-longitude").val();

                    mapLayer.clearLayers();

                    mapLayer.addLayer(L.marker([lat, lng ], {
                        icon: Mks_Maps_Admin.pinIcon,
                    }));

                    map.setView([lat, lng ]);

                })
        
            },

            setGeolocationOnSearch: function (searchControl, mapLayer) {
                
                searchControl.on('results', function (data) {
                    
                    mapLayer.clearLayers();

                    for (var i = data.results.length - 1; i >= 0; i--) {

                        var lat = data.results[i].latlng.lat,
                            lng = data.results[i].latlng.lng;
                          
                        Mks_Maps_Admin.lat = lat;
                        Mks_Maps_Admin.long = lng;
                        $("#mks-map-geolocation-latitude").val(lat);
                        $("#mks-map-geolocation-longitude").val(lng);
                        $("#mks-map-geolocation-address").val(data.results[i].text);

                        mapLayer.addLayer(L.marker([Mks_Maps_Admin.lat, Mks_Maps_Admin.long ], {
                            icon: Mks_Maps_Admin.pinIcon,
                        }));
                    }
                });
            },

            setLatLngManually : function() {


              
            }


        };


        Mks_Maps_Admin.init();
      

        // custom marker svg icon
        function mks_maps_marker_icon(color) {
            var icon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="27.668999999999997 14.147 64.662 93.145" style="width:34px; position:relative;top:-40px;left:-12px"><path d="M60,14.147c-17.855,0-32.331,14.475-32.331,32.331C27.669,76.314,60,107.292,60,107.292s32.331-34.111,32.331-60.815  C92.331,28.622,77.855,14.147,60,14.147z M60.001,58.015c-7.4,0-13.398-5.999-13.398-13.398c0-7.399,5.999-13.398,13.398-13.398  c7.399,0,13.397,5.999,13.397,13.398C73.398,52.016,67.4,58.015,60.001,58.015z" fill="' + color + '"/>';
            return icon;
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