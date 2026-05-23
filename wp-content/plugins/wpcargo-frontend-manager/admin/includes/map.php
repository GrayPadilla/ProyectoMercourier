<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_autocomplete_address_fields(){
    $fields = array(
        'location', 'wpcargo_shipper_address', 'wpcargo_receiver_address'
    );
    return apply_filters( 'wpcfe_autocomplete_address_fields', $fields );
}
function wpcfe_shipment_origin_address( $shipment_id ){
    $address = get_post_meta( $shipment_id, 'wpcargo_shipper_address', true );
    return apply_filters( 'wpcfe_shipment_origin_address', $address, $shipment_id );
}
function wpcfe_shipment_destination_address( $shipment_id ){
    $address = get_post_meta( $shipment_id, 'wpcargo_receiver_address', true );
    return apply_filters( 'wpcfe_shipment_destination_address', $address, $shipment_id );
}
function wpcfe_autocomplete_address_field_template( $html_field, $field_key, $post_id, $class, $id, $field ){
    if( $field['field_type'] != 'text' ){
        return $html_field;
    }
    ob_start();
    $value 		= (int)$post_id ? maybe_unserialize( get_post_meta( $post_id, $field['field_key'], TRUE ) ) : '';
    $required 	= ( $field['required'] ) ? 'required' : '' ;
    $wrap_class = isset($field['classes']) ? $field['classes'] : '';
    $wrap_class = strpos( $wrap_class, 'col-') === false ? $wrap_class.' col-md-12' : $wrap_class;
    $form_label = apply_filters( 'wpccf_field_form_label_'.$field['field_key'], stripslashes( $field['label'] ) );
    ?>
    <section class="<?php echo $wrap_class; ?>">
        <div id="form-<?php echo $id.$field['id']; ?>" class="form-group <?php echo $class; ?> ">
            <label for="<?php echo $id.$field['field_key']; ?>" ><?php echo $form_label; ?></label>
            <input id="<?php echo $id.$field['field_key']; ?>" type="text" class="form-control <?php echo $field['field_key']; ?> wpcfe_autocomplete_address" name="<?php echo $field['field_key']; ?>" value="<?php echo $value; ?>" <?php echo $required; ?> autocomplete="off">
        </div>
    </section>
    <?php
    return ob_get_clean();
}

function wpcfe_shipment_history_map_callback( $shipment_id  ){
    global $post, $wpcargo;
    $shmap_api          = get_option('shmap_api');
    $shmap_longitude    = !empty(get_option('shmap_longitude') ) ? get_option('shmap_longitude') : -87.65;
    $shmap_latitude     = !empty(get_option('shmap_latitude') )  ? get_option('shmap_latitude') : 41.85;
    $shmap_country_restrict      = get_option('shmap_country_restrict');
    $shmap_active       = get_option('shmap_active');
    $shmap_type         = get_option('shmap_type') ? get_option('shmap_type') : 'terrain' ;
    $shmap_zoom         = get_option('shmap_zoom') ? get_option('shmap_zoom') : 15 ;
    $maplabels          = apply_filters('wpcargo_map_labels', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890' );
    $history            = wpcargo_history_order( $wpcargo->history( $shipment_id ) );
    $shipment_addresses = array();
    $shipment_info      = array();
    if( !empty( $history ) ){
        foreach ( $history as $value ) {
            if( empty( $value['location'] ) ){
                continue;
            }
            $shipment_addresses[]   = $value['location'];
            $shipment_info[]        = $value;
        }
    }
    $addressLocations   = array_reverse( $shipment_addresses );
    $shipment_info      = array_reverse( $shipment_info );

    ?>
    <script>
        /*
        ** Google map Script Auto Complete address
        */
        var placeSearch, autocomplete, map, geocoder;
        var componentForm = {
            street_number: 'short_name',
            route: 'long_name',
            locality: 'long_name',
            administrative_area_level_1: 'short_name',
            country: 'long_name',
            postal_code: 'short_name'
        };
        var labels = '<?php echo $maplabels; ?>';
        var labelIndex = 0;
        function wpcSHinitMap() {
            geocoder = new google.maps.Geocoder();
            getPlace_dynamic();
            var map = new google.maps.Map( document.getElementById('wpcargo-shmap'), {
                zoom: <?php echo $shmap_zoom; ?>,
                center: {lat: <?php echo $shmap_latitude; ?>, lng: <?php echo $shmap_longitude; ?>},
                mapTypeId: '<?php echo $shmap_type; ?>',
            });
            /*  Map script
            **  Initialize Shipment Locations
            */
            var shipmentAddress = <?php echo json_encode( $addressLocations ); ?>;
            var shipmentData    = <?php echo json_encode( $shipment_info ); ?>;

            var flightPlanCoordinates = [];
            var lastAddress           = false;
            var shipmentlength        = shipmentData.length - 1;
            for (var i = 0; i < shipmentAddress.length; i++ ) {
                if( i == shipmentlength ){
                    lastAddress = true;
                }
                codeAddress( geocoder, map, shipmentAddress[i], flightPlanCoordinates, i, shipmentData, lastAddress );
            }
            <?php do_action( 'wpc_after_init_map' ); ?>
        }
        function getPlace_dynamic() {
            var defaultBounds = new google.maps.LatLngBounds(
                new google.maps.LatLng(-33.8902, 151.1759),
                new google.maps.LatLng(-33.8474, 151.2631)
            );
            var input   = document.getElementsByClassName('wpcfe_autocomplete_address');
            var options = { bounds: defaultBounds };
            <?php if( !empty( $shmap_country_restrict ) ): ?>
                options.componentRestrictions = {country: "<?php echo $shmap_country_restrict; ?>"}
            <?php endif; ?>
            for (i = 0; i < input.length; i++) {
                autocomplete = new google.maps.places.Autocomplete(input[i], options);
            }
            <?php do_action( 'wpc_after_get_dynamic_place' ); ?>
        }
        function codeAddress( geocoder, map, address, flightPlanCoordinates, index, shipmentData, lastAddress ) {
            var wpclabelColor   = '<?php echo ( get_option('shmap_label_color') ) ? get_option('shmap_label_color') : '#fff' ;  ?>';
            var wpclabelSize    = '<?php echo ( get_option('shmap_label_size') ) ? get_option('shmap_label_size').'px' : '18px' ;  ?>';
            var wpcMapMarker    = '<?php echo ( get_option('shmap_marker') ) ? get_option('shmap_marker') : WPCARGO_PLUGIN_URL.'/admin/assets/images/wpcmarker.png' ;  ?>';
            var wpcCurrMarker   = '<?php echo apply_filters( 'shmap_current_marker_url', WPCARGO_PLUGIN_URL.'/admin/assets/images/current-map.png' );  ?>';
            geocoder.geocode({'address': address}, function(results, status) {
                if (status === 'OK') {
                    var geolatlng = { lat: results[0].geometry.location.lat(),  lng: results[0].geometry.location.lng() };
                    var mapLabel  = {text: labels[index % labels.length], color: wpclabelColor, fontSize: wpclabelSize };
                    flightPlanCoordinates[index] = geolatlng;
                    if( lastAddress === true ){
                        map.setCenter( geolatlng );
                        wpcMapMarker  = wpcCurrMarker;
                        mapLabel      = '';
                    }
                    var marker = new google.maps.Marker({
                        map: map,
                        label: mapLabel,
                        position: results[0].geometry.location,
                        icon: wpcMapMarker
                    });

                    /*
                    ** Marker Information window
                    */
                    // shipmentData
                    var sAddressDate = shipmentData[index].date;
                    var sAddresstime = shipmentData[index].time;
                    var sAddresslocation = shipmentData[index].location;
                    var sAddressstatus = shipmentData[index].status;
                    var shipemtnInfo = '<strong><?php esc_html_e('Date', 'wpcargo'); ?>:</strong> '+sAddressDate+' '+sAddresstime+'</br>'+
                                        '<strong><?php esc_html_e('Location', 'wpcargo'); ?>:</strong> '+sAddresslocation+'</br>'+
                                        '<strong><?php esc_html_e('Status', 'wpcargo'); ?>:</strong> '+sAddressstatus;
                    var infowindow = new google.maps.InfoWindow({
                        content: shipemtnInfo
                    });
                    marker.addListener('click', function() {
                        infowindow.open(map, marker);
                    });
                }
            });
        }
    </script>
    <?php
    echo ($shmap_active) ? wpcargo_map_script( 'wpcSHinitMap' ) : wpcargo_map_script( '' );
}
// With - Route lines - issue map marker NOT using the customize icon
function wpcfe_shipment_history_map_callback_beta( $shipment_id  ){
    global $post, $wpcargo;
    $shmap_api          = get_option('shmap_api');
    $shmap_longitude    = !empty(get_option('shmap_longitude') ) ? get_option('shmap_longitude') : -87.65;
    $shmap_latitude     = !empty(get_option('shmap_latitude') )  ? get_option('shmap_latitude') : 41.85;
    $shmap_country_restrict      = get_option('shmap_country_restrict');
    $shmap_active       = get_option('shmap_active');
    $shmap_type         = get_option('shmap_type') ? get_option('shmap_type') : 'terrain' ;
    $shmap_zoom         = get_option('shmap_zoom') ? get_option('shmap_zoom') : 15 ;
    $maplabels          = apply_filters('wpcargo_map_labels', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890' );
    $history            = wpcargo_history_order( $wpcargo->history( $shipment_id ) );
    $shipment_addresses = array();
    $shipment_info      = array();
    if( !empty( $history ) ){
        foreach ( $history as $value ) {
            if( empty( $value['location'] ) ){
                continue;
            }
            $shipment_addresses[]   = $value['location'];
            $shipment_info[]        = $value;
        }
    }
    $addressLocations   = array_reverse( $shipment_addresses );
    $shipment_info      = array_reverse( $shipment_info );

    ?>
    <div id="directions-panel"></div>
    <script>
        /*
        ** Google map Script Auto Complete address
        */
        var placeSearch, autocomplete, map, geocoder;
        var labels          = '<?php echo $maplabels; ?>';
        var labelIndex      = 0;
        var wpcMapMarker    = '<?php echo ( get_option('shmap_marker') ) ? get_option('shmap_marker') : WPCARGO_PLUGIN_URL.'/admin/assets/images/wpcmarker.png' ;  ?>';
        var wpcCurrMarker   = '<?php echo apply_filters( 'shmap_current_marker_url', WPCARGO_PLUGIN_URL.'/admin/assets/images/current-map.png' );  ?>';
        function autocomplete_address() {
            var input   = document.getElementsByClassName('wpcfe_autocomplete_address');
            var options = { bounds: false };
            <?php if( !empty( $shmap_country_restrict ) ): ?>
                options.componentRestrictions = {country: "<?php echo $shmap_country_restrict; ?>"}
            <?php endif; ?>
            for (i = 0; i < input.length; i++) {
                autocomplete = new google.maps.places.Autocomplete(input[i], options);
            }
            <?php do_action( 'wpc_after_get_dynamic_place' ); ?>
        }
        function makeMarker(position, icon, title, map) {
            new google.maps.Marker({
                position: position,
                map: map,
                icon: icon,
                title: title
            });
        }
        function wpcSHinitMap() {
            const directionsService     = new google.maps.DirectionsService();
            const directionsRenderer    = new google.maps.DirectionsRenderer();
            // geocoder                    = new google.maps.Geocoder();
            autocomplete_address();
            var map = new google.maps.Map( document.getElementById('wpcargo-shmap'), {
                zoom: <?php echo $shmap_zoom; ?>,
                center: {lat: <?php echo $shmap_latitude; ?>, lng: <?php echo $shmap_longitude; ?>},
                mapTypeId: '<?php echo $shmap_type; ?>',
            });         
            directionsRenderer.setMap(map);
            calculateAndDisplayRoute(directionsService, directionsRenderer);
            <?php do_action( 'wpc_after_init_map' ); ?>
        }
        function calculateAndDisplayRoute(directionsService, directionsRenderer) {
            var shipmentAddress = <?php echo json_encode( $addressLocations ); ?>;
            var shipmentData    = <?php echo json_encode( $shipment_info ); ?>;
            const waypts = [];
            for (let i = 0; i < shipmentAddress.length; i++) {
                waypts.push({
                    location: shipmentAddress[i],
                    stopover: true,
                });
            }
            directionsService
                .route({
                    origin: "<?php echo wpcfe_shipment_origin_address($shipment_id); ?>",
                    destination: "<?php echo wpcfe_shipment_destination_address($shipment_id); ?>",
                    waypoints: waypts,
                    optimizeWaypoints: true,
                    travelMode: google.maps.TravelMode.DRIVING,
                })
                .then((response) => {
                    const route = response.routes[0];
                    directionsRenderer.setDirections(response);
                    
                    new google.maps.DirectionsRenderer({
                        map: map,
                        directions: response,
                        suppressMarkers: true
                    });
                    var leg = response.routes[0].legs[0];
                    makeMarker(leg.start_location, wpcCurrMarker, "title", map);
                    makeMarker(leg.end_location, wpcCurrMarker, 'title', map);

                    // for (let i = 0; i < route.legs.length; i++) {

                    //     var geolatlng = { lat: route.legs[i].start_location.lat(),  lng: route.legs[i].start_location.lng() };

                    //     console.log( 'geolatlng: ', geolatlng );

                    //     const marker = new google.maps.Marker({
                    //         position: geolatlng,
                    //         icon: wpcCurrMarker,
                    //         map: map
                    //     });
                    // }

                })
                .catch((e) => {
                    window.alert("Directions request failed due to " + e);
                    // console.log( 'ERROR: ', e);
                });
        }
    </script>
    <?php
    
    echo ($shmap_active) ? wpcargo_map_script( 'wpcSHinitMap' ) : wpcargo_map_script( '' );
}