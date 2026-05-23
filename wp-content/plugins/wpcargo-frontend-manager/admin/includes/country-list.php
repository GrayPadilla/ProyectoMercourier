<?php
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
function wpcfe_country_list(){
	return apply_filters( 'wpcfe_country_list', array(
		'AF' => __( 'Afghanistan', 'wpcargo-frontend-manager' ),
		'AX' => __( '&#197;land Islands', 'wpcargo-frontend-manager' ),
		'AL' => __( 'Albania', 'wpcargo-frontend-manager' ),
		'DZ' => __( 'Algeria', 'wpcargo-frontend-manager' ),
		'AS' => __( 'American Samoa', 'wpcargo-frontend-manager' ),
		'AD' => __( 'Andorra', 'wpcargo-frontend-manager' ),
		'AO' => __( 'Angola', 'wpcargo-frontend-manager' ),
		'AI' => __( 'Anguilla', 'wpcargo-frontend-manager' ),
		'AQ' => __( 'Antarctica', 'wpcargo-frontend-manager' ),
		'AG' => __( 'Antigua and Barbuda', 'wpcargo-frontend-manager' ),
		'AR' => __( 'Argentina', 'wpcargo-frontend-manager' ),
		'AM' => __( 'Armenia', 'wpcargo-frontend-manager' ),
		'AW' => __( 'Aruba', 'wpcargo-frontend-manager' ),
		'AU' => __( 'Australia', 'wpcargo-frontend-manager' ),
		'AT' => __( 'Austria', 'wpcargo-frontend-manager' ),
		'AZ' => __( 'Azerbaijan', 'wpcargo-frontend-manager' ),
		'BS' => __( 'Bahamas', 'wpcargo-frontend-manager' ),
		'BH' => __( 'Bahrain', 'wpcargo-frontend-manager' ),
		'BD' => __( 'Bangladesh', 'wpcargo-frontend-manager' ),
		'BB' => __( 'Barbados', 'wpcargo-frontend-manager' ),
		'BY' => __( 'Belarus', 'wpcargo-frontend-manager' ),
		'BE' => __( 'Belgium', 'wpcargo-frontend-manager' ),
		'PW' => __( 'Belau', 'wpcargo-frontend-manager' ),
		'BZ' => __( 'Belize', 'wpcargo-frontend-manager' ),
		'BJ' => __( 'Benin', 'wpcargo-frontend-manager' ),
		'BM' => __( 'Bermuda', 'wpcargo-frontend-manager' ),
		'BT' => __( 'Bhutan', 'wpcargo-frontend-manager' ),
		'BO' => __( 'Bolivia', 'wpcargo-frontend-manager' ),
		'BQ' => __( 'Bonaire, Saint Eustatius and Saba', 'wpcargo-frontend-manager' ),
		'BA' => __( 'Bosnia and Herzegovina', 'wpcargo-frontend-manager' ),
		'BW' => __( 'Botswana', 'wpcargo-frontend-manager' ),
		'BV' => __( 'Bouvet Island', 'wpcargo-frontend-manager' ),
		'BR' => __( 'Brazil', 'wpcargo-frontend-manager' ),
		'IO' => __( 'British Indian Ocean Territory', 'wpcargo-frontend-manager' ),
		'VG' => __( 'British Virgin Islands', 'wpcargo-frontend-manager' ),
		'BN' => __( 'Brunei', 'wpcargo-frontend-manager' ),
		'BG' => __( 'Bulgaria', 'wpcargo-frontend-manager' ),
		'BF' => __( 'Burkina Faso', 'wpcargo-frontend-manager' ),
		'BI' => __( 'Burundi', 'wpcargo-frontend-manager' ),
		'KH' => __( 'Cambodia', 'wpcargo-frontend-manager' ),
		'CM' => __( 'Cameroon', 'wpcargo-frontend-manager' ),
		'CA' => __( 'Canada', 'wpcargo-frontend-manager' ),
		'CV' => __( 'Cape Verde', 'wpcargo-frontend-manager' ),
		'KY' => __( 'Cayman Islands', 'wpcargo-frontend-manager' ),
		'CF' => __( 'Central African Republic', 'wpcargo-frontend-manager' ),
		'TD' => __( 'Chad', 'wpcargo-frontend-manager' ),
		'CL' => __( 'Chile', 'wpcargo-frontend-manager' ),
		'CN' => __( 'China', 'wpcargo-frontend-manager' ),
		'CX' => __( 'Christmas Island', 'wpcargo-frontend-manager' ),
		'CC' => __( 'Cocos (Keeling) Islands', 'wpcargo-frontend-manager' ),
		'CO' => __( 'Colombia', 'wpcargo-frontend-manager' ),
		'KM' => __( 'Comoros', 'wpcargo-frontend-manager' ),
		'CG' => __( 'Congo (Brazzaville)', 'wpcargo-frontend-manager' ),
		'CD' => __( 'Congo (Kinshasa)', 'wpcargo-frontend-manager' ),
		'CK' => __( 'Cook Islands', 'wpcargo-frontend-manager' ),
		'CR' => __( 'Costa Rica', 'wpcargo-frontend-manager' ),
		'HR' => __( 'Croatia', 'wpcargo-frontend-manager' ),
		'CU' => __( 'Cuba', 'wpcargo-frontend-manager' ),
		'CW' => __( 'Cura&ccedil;ao', 'wpcargo-frontend-manager' ),
		'CY' => __( 'Cyprus', 'wpcargo-frontend-manager' ),
		'CZ' => __( 'Czech Republic', 'wpcargo-frontend-manager' ),
		'DK' => __( 'Denmark', 'wpcargo-frontend-manager' ),
		'DJ' => __( 'Djibouti', 'wpcargo-frontend-manager' ),
		'DM' => __( 'Dominica', 'wpcargo-frontend-manager' ),
		'DO' => __( 'Dominican Republic', 'wpcargo-frontend-manager' ),
		'EC' => __( 'Ecuador', 'wpcargo-frontend-manager' ),
		'EG' => __( 'Egypt', 'wpcargo-frontend-manager' ),
		'SV' => __( 'El Salvador', 'wpcargo-frontend-manager' ),
		'GQ' => __( 'Equatorial Guinea', 'wpcargo-frontend-manager' ),
		'ER' => __( 'Eritrea', 'wpcargo-frontend-manager' ),
		'EE' => __( 'Estonia', 'wpcargo-frontend-manager' ),
		'ET' => __( 'Ethiopia', 'wpcargo-frontend-manager' ),
		'FK' => __( 'Falkland Islands', 'wpcargo-frontend-manager' ),
		'FO' => __( 'Faroe Islands', 'wpcargo-frontend-manager' ),
		'FJ' => __( 'Fiji', 'wpcargo-frontend-manager' ),
		'FI' => __( 'Finland', 'wpcargo-frontend-manager' ),
		'FR' => __( 'France', 'wpcargo-frontend-manager' ),
		'GF' => __( 'French Guiana', 'wpcargo-frontend-manager' ),
		'PF' => __( 'French Polynesia', 'wpcargo-frontend-manager' ),
		'TF' => __( 'French Southern Territories', 'wpcargo-frontend-manager' ),
		'GA' => __( 'Gabon', 'wpcargo-frontend-manager' ),
		'GM' => __( 'Gambia', 'wpcargo-frontend-manager' ),
		'GE' => __( 'Georgia', 'wpcargo-frontend-manager' ),
		'DE' => __( 'Germany', 'wpcargo-frontend-manager' ),
		'GH' => __( 'Ghana', 'wpcargo-frontend-manager' ),
		'GI' => __( 'Gibraltar', 'wpcargo-frontend-manager' ),
		'GR' => __( 'Greece', 'wpcargo-frontend-manager' ),
		'GL' => __( 'Greenland', 'wpcargo-frontend-manager' ),
		'GD' => __( 'Grenada', 'wpcargo-frontend-manager' ),
		'GP' => __( 'Guadeloupe', 'wpcargo-frontend-manager' ),
		'GU' => __( 'Guam', 'wpcargo-frontend-manager' ),
		'GT' => __( 'Guatemala', 'wpcargo-frontend-manager' ),
		'GG' => __( 'Guernsey', 'wpcargo-frontend-manager' ),
		'GN' => __( 'Guinea', 'wpcargo-frontend-manager' ),
		'GW' => __( 'Guinea-Bissau', 'wpcargo-frontend-manager' ),
		'GY' => __( 'Guyana', 'wpcargo-frontend-manager' ),
		'HT' => __( 'Haiti', 'wpcargo-frontend-manager' ),
		'HM' => __( 'Heard Island and McDonald Islands', 'wpcargo-frontend-manager' ),
		'HN' => __( 'Honduras', 'wpcargo-frontend-manager' ),
		'HK' => __( 'Hong Kong', 'wpcargo-frontend-manager' ),
		'HU' => __( 'Hungary', 'wpcargo-frontend-manager' ),
		'IS' => __( 'Iceland', 'wpcargo-frontend-manager' ),
		'IN' => __( 'India', 'wpcargo-frontend-manager' ),
		'ID' => __( 'Indonesia', 'wpcargo-frontend-manager' ),
		'IR' => __( 'Iran', 'wpcargo-frontend-manager' ),
		'IQ' => __( 'Iraq', 'wpcargo-frontend-manager' ),
		'IE' => __( 'Ireland', 'wpcargo-frontend-manager' ),
		'IM' => __( 'Isle of Man', 'wpcargo-frontend-manager' ),
		'IL' => __( 'Israel', 'wpcargo-frontend-manager' ),
		'IT' => __( 'Italy', 'wpcargo-frontend-manager' ),
		'CI' => __( 'Ivory Coast', 'wpcargo-frontend-manager' ),
		'JM' => __( 'Jamaica', 'wpcargo-frontend-manager' ),
		'JP' => __( 'Japan', 'wpcargo-frontend-manager' ),
		'JE' => __( 'Jersey', 'wpcargo-frontend-manager' ),
		'JO' => __( 'Jordan', 'wpcargo-frontend-manager' ),
		'KZ' => __( 'Kazakhstan', 'wpcargo-frontend-manager' ),
		'KE' => __( 'Kenya', 'wpcargo-frontend-manager' ),
		'KI' => __( 'Kiribati', 'wpcargo-frontend-manager' ),
		'KW' => __( 'Kuwait', 'wpcargo-frontend-manager' ),
		'KG' => __( 'Kyrgyzstan', 'wpcargo-frontend-manager' ),
		'LA' => __( 'Laos', 'wpcargo-frontend-manager' ),
		'LV' => __( 'Latvia', 'wpcargo-frontend-manager' ),
		'LB' => __( 'Lebanon', 'wpcargo-frontend-manager' ),
		'LS' => __( 'Lesotho', 'wpcargo-frontend-manager' ),
		'LR' => __( 'Liberia', 'wpcargo-frontend-manager' ),
		'LY' => __( 'Libya', 'wpcargo-frontend-manager' ),
		'LI' => __( 'Liechtenstein', 'wpcargo-frontend-manager' ),
		'LT' => __( 'Lithuania', 'wpcargo-frontend-manager' ),
		'LU' => __( 'Luxembourg', 'wpcargo-frontend-manager' ),
		'MO' => __( 'Macao S.A.R., China', 'wpcargo-frontend-manager' ),
		'MK' => __( 'Macedonia', 'wpcargo-frontend-manager' ),
		'MG' => __( 'Madagascar', 'wpcargo-frontend-manager' ),
		'MW' => __( 'Malawi', 'wpcargo-frontend-manager' ),
		'MY' => __( 'Malaysia', 'wpcargo-frontend-manager' ),
		'MV' => __( 'Maldives', 'wpcargo-frontend-manager' ),
		'ML' => __( 'Mali', 'wpcargo-frontend-manager' ),
		'MT' => __( 'Malta', 'wpcargo-frontend-manager' ),
		'MH' => __( 'Marshall Islands', 'wpcargo-frontend-manager' ),
		'MQ' => __( 'Martinique', 'wpcargo-frontend-manager' ),
		'MR' => __( 'Mauritania', 'wpcargo-frontend-manager' ),
		'MU' => __( 'Mauritius', 'wpcargo-frontend-manager' ),
		'YT' => __( 'Mayotte', 'wpcargo-frontend-manager' ),
		'MX' => __( 'Mexico', 'wpcargo-frontend-manager' ),
		'FM' => __( 'Micronesia', 'wpcargo-frontend-manager' ),
		'MD' => __( 'Moldova', 'wpcargo-frontend-manager' ),
		'MC' => __( 'Monaco', 'wpcargo-frontend-manager' ),
		'MN' => __( 'Mongolia', 'wpcargo-frontend-manager' ),
		'ME' => __( 'Montenegro', 'wpcargo-frontend-manager' ),
		'MS' => __( 'Montserrat', 'wpcargo-frontend-manager' ),
		'MA' => __( 'Morocco', 'wpcargo-frontend-manager' ),
		'MZ' => __( 'Mozambique', 'wpcargo-frontend-manager' ),
		'MM' => __( 'Myanmar', 'wpcargo-frontend-manager' ),
		'NA' => __( 'Namibia', 'wpcargo-frontend-manager' ),
		'NR' => __( 'Nauru', 'wpcargo-frontend-manager' ),
		'NP' => __( 'Nepal', 'wpcargo-frontend-manager' ),
		'NL' => __( 'Netherlands', 'wpcargo-frontend-manager' ),
		'NC' => __( 'New Caledonia', 'wpcargo-frontend-manager' ),
		'NZ' => __( 'New Zealand', 'wpcargo-frontend-manager' ),
		'NI' => __( 'Nicaragua', 'wpcargo-frontend-manager' ),
		'NE' => __( 'Niger', 'wpcargo-frontend-manager' ),
		'NG' => __( 'Nigeria', 'wpcargo-frontend-manager' ),
		'NU' => __( 'Niue', 'wpcargo-frontend-manager' ),
		'NF' => __( 'Norfolk Island', 'wpcargo-frontend-manager' ),
		'MP' => __( 'Northern Mariana Islands', 'wpcargo-frontend-manager' ),
		'KP' => __( 'North Korea', 'wpcargo-frontend-manager' ),
		'NO' => __( 'Norway', 'wpcargo-frontend-manager' ),
		'OM' => __( 'Oman', 'wpcargo-frontend-manager' ),
		'PK' => __( 'Pakistan', 'wpcargo-frontend-manager' ),
		'PS' => __( 'Palestinian Territory', 'wpcargo-frontend-manager' ),
		'PA' => __( 'Panama', 'wpcargo-frontend-manager' ),
		'PG' => __( 'Papua New Guinea', 'wpcargo-frontend-manager' ),
		'PY' => __( 'Paraguay', 'wpcargo-frontend-manager' ),
		'PE' => __( 'Peru', 'wpcargo-frontend-manager' ),
		'PH' => __( 'Philippines', 'wpcargo-frontend-manager' ),
		'PN' => __( 'Pitcairn', 'wpcargo-frontend-manager' ),
		'PL' => __( 'Poland', 'wpcargo-frontend-manager' ),
		'PT' => __( 'Portugal', 'wpcargo-frontend-manager' ),
		'PR' => __( 'Puerto Rico', 'wpcargo-frontend-manager' ),
		'QA' => __( 'Qatar', 'wpcargo-frontend-manager' ),
		'RE' => __( 'Reunion', 'wpcargo-frontend-manager' ),
		'RO' => __( 'Romania', 'wpcargo-frontend-manager' ),
		'RU' => __( 'Russia', 'wpcargo-frontend-manager' ),
		'RW' => __( 'Rwanda', 'wpcargo-frontend-manager' ),
		'BL' => __( 'Saint Barth&eacute;lemy', 'wpcargo-frontend-manager' ),
		'SH' => __( 'Saint Helena', 'wpcargo-frontend-manager' ),
		'KN' => __( 'Saint Kitts and Nevis', 'wpcargo-frontend-manager' ),
		'LC' => __( 'Saint Lucia', 'wpcargo-frontend-manager' ),
		'MF' => __( 'Saint Martin (French part)', 'wpcargo-frontend-manager' ),
		'SX' => __( 'Saint Martin (Dutch part)', 'wpcargo-frontend-manager' ),
		'PM' => __( 'Saint Pierre and Miquelon', 'wpcargo-frontend-manager' ),
		'VC' => __( 'Saint Vincent and the Grenadines', 'wpcargo-frontend-manager' ),
		'SM' => __( 'San Marino', 'wpcargo-frontend-manager' ),
		'ST' => __( 'S&atilde;o Tom&eacute; and Pr&iacute;ncipe', 'wpcargo-frontend-manager' ),
		'SA' => __( 'Saudi Arabia', 'wpcargo-frontend-manager' ),
		'SN' => __( 'Senegal', 'wpcargo-frontend-manager' ),
		'RS' => __( 'Serbia', 'wpcargo-frontend-manager' ),
		'SC' => __( 'Seychelles', 'wpcargo-frontend-manager' ),
		'SL' => __( 'Sierra Leone', 'wpcargo-frontend-manager' ),
		'SG' => __( 'Singapore', 'wpcargo-frontend-manager' ),
		'SK' => __( 'Slovakia', 'wpcargo-frontend-manager' ),
		'SI' => __( 'Slovenia', 'wpcargo-frontend-manager' ),
		'SB' => __( 'Solomon Islands', 'wpcargo-frontend-manager' ),
		'SO' => __( 'Somalia', 'wpcargo-frontend-manager' ),
		'ZA' => __( 'South Africa', 'wpcargo-frontend-manager' ),
		'GS' => __( 'South Georgia/Sandwich Islands', 'wpcargo-frontend-manager' ),
		'KR' => __( 'South Korea', 'wpcargo-frontend-manager' ),
		'SS' => __( 'South Sudan', 'wpcargo-frontend-manager' ),
		'ES' => __( 'Spain', 'wpcargo-frontend-manager' ),
		'LK' => __( 'Sri Lanka', 'wpcargo-frontend-manager' ),
		'SD' => __( 'Sudan', 'wpcargo-frontend-manager' ),
		'SR' => __( 'Suriname', 'wpcargo-frontend-manager' ),
		'SJ' => __( 'Svalbard and Jan Mayen', 'wpcargo-frontend-manager' ),
		'SZ' => __( 'Swaziland', 'wpcargo-frontend-manager' ),
		'SE' => __( 'Sweden', 'wpcargo-frontend-manager' ),
		'CH' => __( 'Switzerland', 'wpcargo-frontend-manager' ),
		'SY' => __( 'Syria', 'wpcargo-frontend-manager' ),
		'TW' => __( 'Taiwan', 'wpcargo-frontend-manager' ),
		'TJ' => __( 'Tajikistan', 'wpcargo-frontend-manager' ),
		'TZ' => __( 'Tanzania', 'wpcargo-frontend-manager' ),
		'TH' => __( 'Thailand', 'wpcargo-frontend-manager' ),
		'TL' => __( 'Timor-Leste', 'wpcargo-frontend-manager' ),
		'TG' => __( 'Togo', 'wpcargo-frontend-manager' ),
		'TK' => __( 'Tokelau', 'wpcargo-frontend-manager' ),
		'TO' => __( 'Tonga', 'wpcargo-frontend-manager' ),
		'TT' => __( 'Trinidad and Tobago', 'wpcargo-frontend-manager' ),
		'TN' => __( 'Tunisia', 'wpcargo-frontend-manager' ),
		'TR' => __( 'Turkey', 'wpcargo-frontend-manager' ),
		'TM' => __( 'Turkmenistan', 'wpcargo-frontend-manager' ),
		'TC' => __( 'Turks and Caicos Islands', 'wpcargo-frontend-manager' ),
		'TV' => __( 'Tuvalu', 'wpcargo-frontend-manager' ),
		'UG' => __( 'Uganda', 'wpcargo-frontend-manager' ),
		'UA' => __( 'Ukraine', 'wpcargo-frontend-manager' ),
		'AE' => __( 'United Arab Emirates', 'wpcargo-frontend-manager' ),
		'GB' => __( 'United Kingdom (UK)', 'wpcargo-frontend-manager' ),
		'US' => __( 'United States (US)', 'wpcargo-frontend-manager' ),
		'UM' => __( 'United States (US) Minor Outlying Islands', 'wpcargo-frontend-manager' ),
		'VI' => __( 'United States (US) Virgin Islands', 'wpcargo-frontend-manager' ),
		'UY' => __( 'Uruguay', 'wpcargo-frontend-manager' ),
		'UZ' => __( 'Uzbekistan', 'wpcargo-frontend-manager' ),
		'VU' => __( 'Vanuatu', 'wpcargo-frontend-manager' ),
		'VA' => __( 'Vatican', 'wpcargo-frontend-manager' ),
		'VE' => __( 'Venezuela', 'wpcargo-frontend-manager' ),
		'VN' => __( 'Vietnam', 'wpcargo-frontend-manager' ),
		'WF' => __( 'Wallis and Futuna', 'wpcargo-frontend-manager' ),
		'EH' => __( 'Western Sahara', 'wpcargo-frontend-manager' ),
		'WS' => __( 'Samoa', 'wpcargo-frontend-manager' ),
		'YE' => __( 'Yemen', 'wpcargo-frontend-manager' ),
		'ZM' => __( 'Zambia', 'wpcargo-frontend-manager' ),
		'ZW' => __( 'Zimbabwe', 'wpcargo-frontend-manager' ),
	) );
}
function wpcfe_state_list(){
	return apply_filters( 'wpcfe_state_list', array());
}
function wpcfe_get_country_name( $country_code = '' ){
	$country_name = '';
	if( !empty( $country_code ) && array_key_exists( $country_code, wpcfe_country_list() ) ){
		$country_name = wpcfe_country_list()[$country_code];
	}
	return $country_name;
}
function wpcfe_country_list_filter_callback( $countries ){
	if ( !class_exists( 'WooCommerce' ) ) {
		return $countries;
	}
	global $woocommerce;
	$countries_obj   = new WC_Countries();
	return $countries_obj->__get('countries');
}
function wpcfe_state_list_filter_callback( $states ){
	if ( !class_exists( 'WooCommerce' ) ) {
		return $states;
	}
	global $woocommerce;
	$countries_obj   		= new WC_Countries();
	$default_country 		= $countries_obj->get_base_country();
	return $countries_obj->get_states( $default_country );
	
}
function wpcfe_billing_address_fields_state_callback( $fields ){
	if ( !class_exists( 'WooCommerce' ) ) {
		return $fields;
	}
	$fields['billing_state']['field'] 		= 'select';
	$fields['billing_state']['field_type'] 	= 'select';
	$fields['billing_state']['options'] 	= wpcfe_state_list();
	$fields['billing_state']['field_data'] 	= wpcfe_state_list();
	return $fields;
}
function wpcfe_get_base_country(){
	if ( !class_exists( 'WooCommerce' ) ) {
		return false;
	}
	global $woocommerce;
	$countries_obj   	= new WC_Countries();
	$default_country 	= $countries_obj->get_base_country();
	$country_list 		= wpcfe_country_list();
	return array_key_exists( $default_country, $country_list ) ? $country_list[$default_country] : false ;
}
function wpcfe_get_option_states_callback(){
	global $woocommerce;
	$countries_obj 	= new WC_Countries();
	$country 		= trim($_POST['selCountry']);
	$countries 		= wpcfe_country_list();
	$countries 		= array_map( 'trim', $countries );
	$country_code 	= array_search( $country, $countries);
	$states 		= array();
	if( $country_code ){
		$states = $countries_obj->get_states( $country_code );
	}
	wp_send_json( $states );
	wp_die( );
}
add_action( 'wp_ajax_wpcfe_get_option_states', 'wpcfe_get_option_states_callback' );
add_action( 'wp_ajax_nopriv_wpcfe_get_option_states', 'wpcfe_get_option_states_callback' );
function wpcfe_plugins_loaded_country_callback(){
	add_filter( 'wpcfe_country_list', 'wpcfe_country_list_filter_callback' );
	add_filter( 'wpcfe_state_list', 'wpcfe_state_list_filter_callback' );
	add_filter( 'wpcfe_billing_address_fields', 'wpcfe_billing_address_fields_state_callback' );
}
add_action( 'plugins_loaded', 'wpcfe_plugins_loaded_country_callback' );