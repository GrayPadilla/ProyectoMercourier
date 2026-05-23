<?php
if (!defined('ABSPATH')){
    exit; // Exit if accessed directly
}
if ( class_exists('WPCARGO_API') ) :
    class WPCARGO_SHIPMENT_CONTAINER_API extends WPCARGO_API {

        public function container_routes(){
            $namespace = $this->wpcargo_namespace . $this->wpcargo_api_version;
            $base      = 'api';
            register_rest_route( $namespace, '/' . $base.'/(?P<apikey>[a-zA-Z0-9-]+)/containers/', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_containers' ),
                    'permission_callback'   => array( $this, 'wpcargo_api_premission' )
                    )
                ) 
            );
            register_rest_route( $namespace, '/' . $base.'/(?P<apikey>[a-zA-Z0-9-]+)/containers/page/(?P<page>[0-9-%]+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_containers' ),
                    'permission_callback'   => array( $this, 'wpcargo_api_premission' )
                    )
                ) 
            );
            register_rest_route( $namespace, '/' . $base.'/(?P<apikey>[a-zA-Z0-9-]+)/containers/all', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_all_containers' ),
                    'permission_callback'   => array( $this, 'wpcargo_api_premission' )
                    )
                ) 
            );
            register_rest_route( $namespace, '/' . $base.'/(?P<apikey>[a-zA-Z0-9-]+)/unassigned/shipments', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'get_unassigned_shipments' ),
                    'permission_callback'   => array( $this, 'wpcargo_api_premission' )
                    )
                ) 
            );
            register_rest_route( $namespace, '/' . $base.'/(?P<apikey>[a-zA-Z0-9-]+)/container/track/(?P<container>[a-zA-Z0-9-%]+)', array(
                array(
                    'methods'               => WP_REST_Server::READABLE,
                    'callback'              => array( $this, 'track_container' ),
                    'permission_callback'   => array( $this, 'wpcargo_api_premission' )
                    )
                ) 
            );
        }
        // Register our REST Server
        public function container_restful_server(){
            add_action( 'rest_api_init', array( $this, 'container_routes' ) );
        }
        // Route Callbacks
        public function get_containers( WP_REST_Request $request ){
            global $wpdb, $wpcargo;
            $apikey     = $request->get_param( 'apikey' );
            $page       = ( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1 ;
            $userID     = wpcapi_get_apikey_user( $apikey  );
            $containers  = wpcapi_get_user_containers( $userID, $page );
            if( empty( $containers ) ){
                return null;
            }
            return wpcapi_extract_container_data( $containers );
        }
        function get_all_containers( WP_REST_Request $request ){
            global $wpdb, $wpcargo;
            $apikey     = $request->get_param( 'apikey' );
            $page       = ( $request->get_param( 'page' ) ) ? $request->get_param( 'page' ) : 1 ;
            $userID     = wpcapi_get_apikey_user( $apikey  );
            $containers  = wpcapi_get_user_containers( $userID, 0, true );
            if( empty( $containers ) ){
                return null;
            }
            return wpcapi_extract_container_data( $containers );
        }
        function get_unassigned_shipments( WP_REST_Request $request ){
            global $wpdb, $wpcargo;
            $apikey      = $request->get_param( 'apikey' );
            $userID      = wpcapi_get_apikey_user( $apikey  );
            return wpcapi_get_unassigned_shipments( $userID );
        }
        public function track_container( WP_REST_Request $request ){
            global $wpdb, $wpcargo;
            $containerTitle = urldecode( $request->get_param( 'container' ) );
            $container      = $wpdb->get_row( "SELECT `ID`, `post_title` AS container_number, `post_author`, `post_date`, `post_date_gmt`, `post_modified`, `post_modified_gmt` FROM `{$wpdb->prefix}posts` WHERE `post_status` LIKE 'publish' AND `post_type` LIKE 'shipment_container' AND  `post_title` LIKE '".$containerTitle."' LIMIT 1", ARRAY_A );
            if( empty( $container ) ){
                return null;
            }
            $containers = wpcapi_extract_container_data( array( $container ) );
            return array_shift( $containers );
        }

    }
    $wpc_shipment_container = new WPCARGO_SHIPMENT_CONTAINER_API();
    $wpc_shipment_container->container_restful_server();
endif;