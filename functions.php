<?php
/* Autoriser les fichiers SVG */
function wpc_mime_types($mimes) {
	$mimes['svg'] = 'image/svg+xml';
	return $mimes;
}
add_filter('upload_mimes', 'wpc_mime_types');

add_filter('rest_url', function($url) {
    $url = str_replace(home_url(), site_url(), $url);
    return $url;
});
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 'Direct script access denied.' );
}
if(!defined('REHUB_NAME_ACTIVE_THEME')){
	define('REHUB_NAME_ACTIVE_THEME', 'REVENDOR');
}
if ( defined( 'RH_GRANDCHILD_DIR' ) ) {
	include( RH_GRANDCHILD_DIR . 'rh-grandchild-func.php' );
}

add_action( 'wp_enqueue_scripts', 'enqueue_parent_theme_style',11 );
function enqueue_parent_theme_style() {
	if ( !defined( 'RH_MAIN_THEME_VERSION' ) ) {
		define('RH_MAIN_THEME_VERSION', '9.0');
	}
    wp_enqueue_style( 'parent-style', get_template_directory_uri().'/style.css', array(), RH_MAIN_THEME_VERSION );
	if (is_rtl()) {
		 wp_enqueue_style( 'parent-rtl', get_template_directory_uri().'/rtl.css', array(), RH_MAIN_THEME_VERSION );
	}    
}

//////////////////////////////////////////////////////////////////
// Translation
//////////////////////////////////////////////////////////////////
add_action('after_setup_theme', 'rehubchild_lang_setup');
function rehubchild_lang_setup(){
    //load_child_theme_textdomain('rehubchild', get_stylesheet_directory() . '/lang');
}

// Ceci est le filtre de message d'erreur lors de l'inscription d'un nouveau vendeur
add_filter( 'wcv_vendor_store_description', 'wcv_vendor_store_description' );
function wcv_vendor_store_description( $field ){
	$field[ 'custom_attributes' ] = array( 'required' => "", 'data-parsley-error-message' => 'Vous devez donner plus de détails sur votre boutique' );
	return $field;
}
add_filter( 'wcv_vendor_store_phone', 'wcv_vendor_store_phone' );
function wcv_vendor_store_phone( $field ){
	$field[ 'custom_attributes' ] = array( 'required' => "", 'data-parsley-error-message' => 'Vous devez renseigner un numéro de téléphone' );
	return $field;
}
add_filter( 'wcv_vendor_seller_info', 'wcv_vendor_seller_info' );
function wcv_vendor_seller_info( $field ){
	$field[ 'custom_attributes' ] = array( 'required' => "", 'data-parsley-error-message' => 'Vous devez renseigner les produits que vous pouvez être amené à vendre sur Byfrenchyz' );
	return $field;
}

wp_enqueue_script( 'signup', get_stylesheet_directory_uri().'/script-signup/signup.js', array('jquery'), '1.0', true );