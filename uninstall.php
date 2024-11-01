<?php

// If uninstall is not called by WordPress, exit.
if( !defined('WP_UNINSTALL_PLUGIN') )
    exit();

// Clean th WP Database from this plugin.
$slug 		= 'stonehenge_em_slider';
$version 	= $slug .'_version';
$license 	= $slug .'_license';
$saved  	= get_option($slug);

if( $saved['general']['delete'] && $saved['general']['delete'] != 'no' ) {
	delete_option( $slug );
	delete_option( $version );
	delete_site_option( $license );
}