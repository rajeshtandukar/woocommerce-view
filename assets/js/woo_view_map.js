var agmap_geocoder;
var agmap_map;
var agmap_marker;
var infowindow;
var contentinfo = "Welcome TO Google MAP API PLugin DEMO!";

var agmap = {};
agmap.glat=-34.397;
agmap.glng= 150.644;

jQuery(document).ready(function (e) {
    var t = {init: function () {
        google.maps.event.addDomListener(window, "load", this.initialize())
    }, initialize: function () {
        agmap_geocoder = new google.maps.Geocoder;
        infowindow = new google.maps.InfoWindow;
        var n = new google.maps.LatLng(woo_admin_js.glat, woo_admin_js.glng);
        var r = {center: n, zoom: 13, mapTypeId: google.maps.MapTypeId.ROADMAP};
        agmap_map = new google.maps.Map(document.getElementById("woo_view_map_canvas"), r);
        agmap_marker = new google.maps.Marker({position: n, map: agmap_map, draggable: true, animation: google.maps.Animation.DROP});
        google.maps.event.addListener(agmap_marker, "dragend", function (e) {
            t.geocodePosition(e.latLng.lat(), e.latLng.lng());
            t.formattedAddress(agmap_marker.getPosition())
        });
        google.maps.event.addListener(agmap_marker, "click", function (e) {
            infowindow.setContent(contentinfo);
            infowindow.open(agmap_map, agmap_marker)
        });
        google.maps.event.addListener(agmap_marker, "drag", function (e) {
            infowindow.close(agmap_map, agmap_marker)
        });
        e("#agmap_geoloc").click(function () {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(function (e) {
                    var n = new google.maps.LatLng(e.coords.latitude, e.coords.longitude);
                    agmap_map.setCenter(n);
                    agmap_marker.setMap(null);
                    agmap_marker = new google.maps.Marker({position: n, map: agmap_map, draggable: true});
                    t.geocodePosition(e.coords.latitude, e.coords.longitude);
                    t.formattedAddress(agmap_marker.getPosition());
                    google.maps.event.addListener(agmap_marker, "dragend", function (e) {
                        t.geocodePosition(e.latLng.lat(), e.latLng.lng());
                    });
                    google.maps.event.addListener(agmap_marker, "click", function (e) {
                        infowindow.setContent(contentinfo);
                        infowindow.open(agmap_map, agmap_marker)
                    });
                    google.maps.event.addListener(agmap_marker, "drag", function (e) {
                        infowindow.close(agmap_map, agmap_marker)
                    })
                }, function () {
                    this.handleNoGeolocation(true)
                })
            } else {
                this.handleNoGeolocation(false)
            }
        })
    }, geocodePosition: function (t, n) {
        e("#woo_view_lat").attr("value", t);
        e("#woo_view_lng").attr("value",n);
    }, formattedAddress: function (t) {
        agmap_geocoder.geocode({latLng: t}, function (t) {
            if (t && t.length > 0) {
             e("#woo_view_location").attr('value',t[0].formatted_address)

                contentinfo = t[0].formatted_address
            } else {
                e("#woo_view_location").attr('value',"Cannot determine address at this location.")
            }
        })
    }, handleNoGeolocation: function (e) {
        if (e) {
            var t = "Error: The Geolocation service failed."
        } else {
            var t = "Error: Your browser doesn't support geolocation."
        }
        var n = {map: agmap_map, position: new google.maps.LatLng(60, 105), content: t};
        agmap_infowindow = new google.maps.InfoWindow(n);
        agmap_map.setCenter(n.position)
    }};
    t.init();
    if (typeof e("#agmap_add_button") != "undefined") {
        e("#agmap_add_button").click(function () {
            var n = e("#woo_view_location").attr("value");

            if(n.length==0) {
                e("#woo_view_location").addClass('woo_view_error');
                return false;
            }else{
                e("#woo_view_location").removeClass('woo_view_error');
            }
            agmap_geocoder.geocode({address: n}, function (e, n) {
                if (n == google.maps.GeocoderStatus.OK) {
                    //console.log(e[0].formatted_address);
                    agmap_map.setCenter(e[0].geometry.location);
                    agmap_marker.setMap(null);
                    agmap_marker = new google.maps.Marker({map: agmap_map, position: e[0].geometry.location, draggable: true});
                    t.geocodePosition(e[0].geometry.location.lat(), e[0].geometry.location.lng());
                    t.formattedAddress(agmap_marker.getPosition());
                    google.maps.event.addListener(agmap_marker, "dragend", function (e) {
                        t.geocodePosition(e.latLng.lat(), e.latLng.lng());
                        t.formattedAddress(agmap_marker.getPosition())
                    });
                    google.maps.event.addListener(agmap_marker, "click", function (e) {
                        infowindow.setContent(contentinfo);
                        infowindow.open(agmap_map, agmap_marker)
                    });
                    google.maps.event.addListener(agmap_marker, "drag", function (e) {
                        infowindow.close(agmap_map, agmap_marker)
                    })
                } else {
                    alert("Geocode was not successful for the following reason: " + n)
                }
            })
        })
    }
})