/*
var infowindow = new google.maps.InfoWindow();

jQuery(document).ready(function (e) {

    var container = gb.jQuery('.products');
    var locations[i] = new Array(3);

    var t = {init: function () {
        google.maps.event.addDomListener(window, "load", this.initialize())
    },

        initialize: function () {

            var Options = {
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                zoom: 13
            }

            map = new google.maps.Map(document.getElementById("map-canvas"),Options);

            var geocoder = new google.maps.Geocoder();

            for(var x=0;x<=addresses.length;x++){

                if(typeof(addresses[x])!=='undefined'){
                    var n = addresses[x];

                    geocoder.geocode( { 'address': n}, function(results, status) {

                        if (status == google.maps.GeocoderStatus.OK) {
                            wooview_Cords[x][0]= results[0].geometry.location.lat();
                            wooview_Cords[x][1]= results[0].geometry.location.lng();
                        }
                    });
                }
            }
            var oms = new OverlappingMarkerSpiderfier(map);

            geoXml = new geoXML3.parser({
                        map: map,
                        zoom: kmlZoom,
                        createMarker: function(){
                        var lat = wooview_Cords[0][0];
                        var lng = wooview_Cords[0][1];
                        marker = new google.maps.Marker({
                            position: new google.maps.LatLng(lat,lng),
                            map: map
                        });
                        oms.addMarker(marker);
                        locations[0][3] = marker;
                        google.maps.event.addListener(marker, 'click', (function(marker, i) {
                            return function() {
                                infowindow.setContent(locations[0][0]);
                                infowindow.open(map, marker);
                                height = gb.jQuery('#woomap0').height();
                                container.animate({
                                    scrollTop: 0
                            });
                        }
                    })(marker, 0));
                }
            });
            geoXml.parse(filename);

        }
    };
    t.init();

})
*/
