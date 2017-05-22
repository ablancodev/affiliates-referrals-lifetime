<?php
/**
 * affiliates-referrals-lifetime.php
 *
 * Copyright (c) 2015 www.eggemplo.com
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 * This header and all notices must be kept intact.
 *
 * @author eggemplo
 * @package affiliates-referrals-lifetime
 * @since 1.0.0
 *
 * Plugin Name: Affiliates Referrals lifetime
 * Plugin URI: http://www.eggemplo.com
 * Description: Closes the referrals when a certain time has passed.
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com/
 * Version: 1.0.0
 */

if ( !defined('ABSPATH' ) ) {
	exit;
}

define( 'AFFILIATES_REFERRALS_LIFETIME_PLUGIN_URL', WP_PLUGIN_URL . '/affiliates-referrals-lifetime' );
define( 'AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN', 'affiliates-referrals-lifetime' );


class Affiliates_Referrals_Lifetime {

	const NEW_STATUS = AFFILIATES_REFERRAL_STATUS_REJECTED;

	const PLUGIN_OPTIONS    = 'affiliates_ref_lifetime';
	const NONCE             = 'aff_ref_lifetime_admin_nonce';
	const SET_ADMIN_OPTIONS = 'set_ref_lifetime_options';

	const LIFETIME          = 'ref_lifetime';
	const LASTDAY           = 'ref_lastday';
	const LIFETIME_DEFAULT  = '0';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'wp_init' ) );
	}

	public static function wp_init() {
		global $wpdb;

		load_plugin_textdomain( AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN, null, 'affiliates-referrals-lifetime/languages' );
		if ( defined( 'AFFILIATES_PLUGIN_DOMAIN' ) ) {
			$options = self::get_options();
			add_action( 'affiliates_admin_menu', array( __CLASS__, 'affiliates_admin_menu' ) );

			$options = self::get_options();
			$last_day = isset( $options[self::LASTDAY] ) ? $options[self::LASTDAY] : null;
			$lifetime = isset( $options[self::LIFETIME] ) ? $options[self::LIFETIME] : null;
			if ( !$lifetime || $lifetime == '0' | $lifetime == '' ) {
				$lifetime = null;
			}
			if ( $lifetime !== null ) {
				// If first time or the first time today ....
				if ( !$last_day || ( date( 'Y-m-d' ) !== date( 'Y-m-d', $last_day ) ) ) {
					$last_day = time();
					$options[self::LASTDAY] = $last_day;
					self::set_options( $options );
	
					// Update the referrals
					$referrals_table = _affiliates_get_tablename( 'referrals' );
					$wpdb->query( $wpdb->prepare( "UPDATE $referrals_table SET status = %s WHERE ( datetime < (CURDATE() - INTERVAL %d DAY) ) AND ( status = %s OR status = %s )", self::NEW_STATUS, $lifetime, AFFILIATES_REFERRAL_STATUS_ACCEPTED, AFFILIATES_REFERRAL_STATUS_PENDING ) );
				}
			}
		}
	}

	/**
	 * Adds a submenu item to the Affiliates menu.
	 */
	public static function affiliates_admin_menu() {
		$page = add_submenu_page(
			'affiliates-admin',
			__( 'Referrals Lifetime', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN ),
			__( 'Referrals Lifetime', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN ),
			AFFILIATES_ADMINISTER_OPTIONS,
			'affiliates-admin-referrals-lifetime',
			array( __CLASS__, 'affiliates_admin_referrals_lifetime' )
		);
		$pages[] = $page;
		add_action( 'admin_print_styles-' . $page, 'affiliates_admin_print_styles' );
		add_action( 'admin_print_scripts-' . $page, 'affiliates_admin_print_scripts' );
	}

	private static function get_options() {
		$options = get_option( self::PLUGIN_OPTIONS , null );
		if ( $options === null ) {
			add_option(self::PLUGIN_OPTIONS, array(), '', 'no' );
			$options = array();
		}
		return $options;
	}

	private static function set_options( $options ) {
		update_option( self::PLUGIN_OPTIONS, $options );
	}

	public static function affiliates_admin_referrals_lifetime() {

		$output = '';

		if ( !current_user_can( AFFILIATES_ADMINISTER_OPTIONS ) ) {
			wp_die( __( 'Access denied.', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN ) );
		}

		$options = self::get_options();
		if ( isset( $_POST['submit'] ) ) {
			if ( wp_verify_nonce( $_POST[self::NONCE], self::SET_ADMIN_OPTIONS ) ) {
				$options[self::LIFETIME]  = $_POST[self::LIFETIME];
			}
			self::set_options( $options );
		}

		$lifetime = isset( $options[self::LIFETIME] ) ? $options[self::LIFETIME] : self::LIFETIME_DEFAULT;
		
		$output .= '<h1>';
		$output .= __( 'Referrals Lifetime', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN );
		$output .= '</h1>';

		$output .= '<div class="manage" style="padding:2em;margin-right:1em;">';

		$output .= '<form action="" name="options" method="post">';
		$output .= '<div>';

		$amount_input = sprintf( '<input type="text" value="%s" name="%s" />', esc_attr( $lifetime ), self::LIFETIME );

		$output .= '<div class="field amount">';
		$output .= '<label>';
		$output .= '<span class="label">';
		$output .= __( 'Lifetime (days)', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN );
		$output .= '</span>';
		$output .= ' ';
		$output .= $amount_input;
		$output .= '</label>';
		$output .= '</div>';

		$output .= '<p>';
		$output .= wp_nonce_field( self::SET_ADMIN_OPTIONS, self::NONCE, true, false );
		$output .= '<input class="button-primary" type="submit" name="submit" value="' . __( 'Save', AFFILIATES_REFERRALS_LIFETIME_PLUGIN_DOMAIN ) . '"/>';
		$output .= '</p>';

		$output .= '</div>';
		$output .= '</form>';
		$output .= '</div>';

		echo $output;

		affiliates_footer();
	}

	/*
	public static function affiliates_hit( $hit ) {

		global $wpdb;

		$options   = self::get_options();
		$enabled   = isset( $options[self::ENABLED] ) ? $options[self::ENABLED] : 'no';
		$type      = isset( $options[self::TYPE] ) ? $options[self::TYPE] : self::TYPE_DEFAULT;
	*/
}
Affiliates_Referrals_Lifetime::init();
