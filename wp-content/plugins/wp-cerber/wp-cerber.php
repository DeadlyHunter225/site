<?php
/*
	Plugin Name: WP Cerber Security, Antispam & Malware Scan
	Plugin URI: https://wpcerber.com
	Description: Defends WordPress against hacker attacks, spam, trojans, and viruses. Malware scanner and integrity checker. Hardening WordPress with a set of comprehensive security algorithms. Spam protection with a sophisticated bot detection engine and reCAPTCHA. Tracks user and intruder activity with powerful email, mobile and desktop notifications.
	Author: Gregory
	Author URI: https://wpcerber.com
	Version: 7.8.5
	Text Domain: wp-cerber
	Domain Path: /languages
	Network: true

 	Copyright (C) 2015-18 CERBER TECH INC., http://cerber.tech
    Copyright (C) 2015-18 Gregory Markov, https://wpcerber.com

    Licenced under the GNU GPL.

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

*/

define( 'CERBER_VER', '7.8.5' );

function cerber_plugin_file() {
	return __FILE__;
}

function cerber_plug_in() {
	return plugin_basename( __FILE__ );
}

function cerber_plugin_data() {
	return get_plugin_data( __FILE__ );
}

function cerber_plugin_dir_url() {
	static $ret = null;
	if ( $ret === null ) {
		$ret = plugin_dir_url( __FILE__ );
	}

	return $ret;
}

function cerber_get_plugins_dir() {
	static $dir = null;

	if ( $dir === null ) {
		$dir = cerber_dirname( __FILE__, 2 );
	}

	return $dir;
}

function cerber_get_themes_dir() {
	static $dir = null;

	if ( $dir === null ) {
		$dir = cerber_get_content_dir() . DIRECTORY_SEPARATOR . 'themes';
	}

	return $dir;
}

function cerber_get_content_dir() {
	static $dir = null;

	if ( $dir === null ) {
		$dir = dirname( cerber_get_plugins_dir() );
	}

	return $dir;
}

/**
 * @return null|string Full path. For MU it returns the uploads folder of the main site.
 */
function cerber_get_upload_dir() {
	static $dir = null;
	if ( $dir === null ) {
		if ( is_multisite() ) {
			switch_to_blog( get_main_site_id() );
		}
		$wp_upload_dir = wp_upload_dir();
		if ( is_multisite() ) {
			restore_current_blog();
		}
		$dir = cerber_normal_path( $wp_upload_dir['basedir'] );
	}

	return $dir;
}

/**
 * Return path to root uploads folder for all sites in the MU network
 *
 * @return bool|null|string
 */
function cerber_get_upload_dir_mu() {
	global $blog_id, $wpdb;
	static $dir = null;

	if ( $dir === null ) {
		if ( is_multisite() && ( $id = cerber_db_get_var( 'SELECT MAX(blog_id) FROM ' . $wpdb->blogs ) ) ) {
			if ( $id == get_main_site_id() ) {
				// no child sites in the network
				$dir = cerber_get_upload_dir();
			}
			else {
				$tmp           = $blog_id;
				$blog_id       = $id;
				$wp_upload_dir = wp_upload_dir();
				$blog_id       = $tmp;
				$site_dir = rtrim( $wp_upload_dir['basedir'], '/' ) . '/';
				// A new network created post-3.5
				$end      = '/sites/' . $id.'/';
				if ( $p = mb_strpos( $site_dir, $end ) ) {
					$dir = mb_substr( $site_dir, 0, $p );
				}
				else {
					$id = 1; // workaround for old MU installations
					$end = '/' . $id . '/files/';
					if ( $p = mb_strpos( $site_dir, $end ) ) {
						$dir = mb_substr( $site_dir, 0, $p );
					}
					else {
						// A custom path has been configured by site admin?
						// see also UPLOADS,  BLOGUPLOADDIR, BLOGUPLOADDIR
						$dir = ABSPATH.UPLOADBLOGSDIR;
						if ( ! file_exists( $dir ) ) {
							$dir = false;
						}
					}
				}
				if ( $dir ) {
					$dir = cerber_normal_path( $dir );
				}
			}
		}
		else {
			$dir = cerber_get_upload_dir();
		}
	}

	return $dir;
}

function cerber_get_abspath() {
	static $abspath;
	if ( $abspath === null ) {
		$abspath = cerber_normal_path( ABSPATH );
	}

	return $abspath;
}

require_once( dirname( __FILE__ ) . '/cerber-load.php' );

cerber_init();

