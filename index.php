<?php
/**
 * Plugin Name:   Revue
 * Description:   The Revue plugin allows you to quickly add a signup form for your Revue list.
 * Author:        Revue
 * Author URI:    https://www.getrevue.co
 * Version:       1.2.0-alpha
 * Text Domain:   revue
 * Domain Path:   /languages/
 * License:       GPLv2 (or later)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
include_once 'widget.php';

$_revue_printed_forms = 0;


add_action( 'plugins_loaded', 'revue_load_textdomain' );
/**
 * Load textdomain
 *
 * @since 1.2
 */
function revue_load_textdomain() {
	$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
	load_plugin_textdomain( 'revue', false, $lang_dir );
}


function revue_ajaxurl() {
	?>
	<script type="text/javascript">
		var revue_ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
	</script>
	<?php
}

add_action( 'wp_head', 'revue_ajaxurl' );

function revue_admin_settings() {
	?>
	<div class="wrap wrap-revue">
		<img src="<?php echo plugins_url( 'images/logo.png', __FILE__ ); ?>" style="margin-top: 20px; width: 100px;"/>

		<p style="font-size: 18px; line-height: 28px;margin-bottom: 40px;">
			<?php echo sprintf( __( 'In order to connect to Revue, we need you to
			enter your API key. You can find<br/>
			this key on the bottom of the <a target="_blank" style="color: #E15718;"  href="%s">Integrations page.</a>', 'revue' ), 'https://www.getrevue.co/app/integrations' ); ?>
		</p>

		<form method="post" action="options.php">
			<?php
			// This prints out all hidden setting fields
			settings_fields( 'revue_general' );
			do_settings_sections( 'revue-settings' );
			submit_button();
			?>
		</form>

		<p style="color: #999;font-size: 12px;margin-top: 20px;">
			<?php echo sprintf( __( 'Need help? Just <a style="color: #999;" href="%s">shoot us an email</a>.', 'revue' ), 'mailto:support@getrevue.co' ) ?>
		</p>
	</div>
	<?php
}

function revue_admin_menu() {
	add_options_page(
		'Revue',
		'Revue',
		'manage_options',
		'revue-settings',
		'revue_admin_settings'
	);
}

add_action( 'admin_menu', 'revue_admin_menu' );

function revue_api_key_callback() {
	$options = get_option( 'revue_general' );
	printf(
		'<input type="text" id="api_key" name="revue_general[api_key]" value="%s" />',
		isset( $options['api_key'] ) ? esc_attr( $options['api_key'] ) : ''
	);
}

function revue_page_init() {

	register_setting(
		'revue_general',
		'revue_general'
	);

	add_settings_section(
		'revue_api_settings', // ID
		__( 'API Settings', 'revue' ), // Title
		null,
		'revue-settings' // Page
	);

	add_settings_field(
		'api_key', // ID
		__( 'Fill out your API key:', 'revue'),
		'revue_api_key_callback',
		'revue-settings', // Page
		'revue_api_settings' // Section
	);
}

add_action( 'admin_init', 'revue_page_init' );

function revue_enqueue_scripts() {
	wp_enqueue_script( 'revue', plugin_dir_url( __FILE__ ) . 'revue.js', array( 'jquery' ), '1.1.0', true );
}

add_action( 'wp_enqueue_scripts', 'revue_enqueue_scripts' );

function revue_subscribe_callback() {
	revue_subscribe( $_POST['email'], $_POST['first_name'], $_POST['last_name'] );

	header( 'Content-Type: application/json' );

	echo json_encode( array(
		'thank_you' => sprintf(
			__('Thanks for subscribing to my email digest. You can find older issues <a href="%s">here</a> via <a href="%s">Revue</a>.','revue'),
			revue_get_profile_url(),
			'https://www.getrevue.co/?utm_campaign=Wordpress+plugin&utm_content=Confirmation&utm_medium=web'
		)
	) );

	wp_die();
}

add_action( 'wp_ajax_revue_subscribe', 'revue_subscribe_callback' );
add_action( 'wp_ajax_nopriv_revue_subscribe', 'revue_subscribe_callback' );

function revue_subscribe_form() {
	global $_revue_printed_forms;
	$_revue_printed_forms ++;

	if ( ! _revue_key_provided() ) {
		return __( 'Please provide an API key in the Revue settings.', 'revue' );
	}

	$res = '';

	$res .= '<div class="revue-subscribe">';
	$res .= _revue_print_field( __( 'E-mail', 'revue' ), 'revue_email', 'email' );
	$res .= _revue_print_field( __( 'Firstname', 'revue' ), 'revue_first_name', 'text' );
	$res .= _revue_print_field( __( 'Lastname', 'revue' ), 'revue_last_name', 'text' );
	$res .= '<button type="submit">' . __( 'Subscribe', 'revue' ) . '</button>';
	$res .= '</div>';

	return $res;
}

add_shortcode( 'revue_subscribe', 'revue_subscribe_form' );

function _revue_print_field( $label, $name, $type ) {
	global $_revue_printed_forms;

	$id = $name . '_' . $_revue_printed_forms;

	$res = '';

	$res .= '<p>';
	$res .= '<label for="' . $id . '">' .$label. '</label><br>';
	$res .= '<input type="' . $type . '" name="' . $name . '" id="' . $id . '" />';
	$res .= '</p>';

	return $res;
}

function _revue_key_provided() {
	$options = get_option( 'revue_general' );

	return ! empty( $options['api_key'] );
}

function revue_subscribe( $email, $first_name = null, $last_name = null ) {
	$options = get_option( 'revue_general' );

	$body = array(
		'email' => $email,
	);

	if ( ! empty( $first_name ) ) {
		$body['first_name'] = $first_name;
	}

	if ( ! empty( $last_name ) ) {
		$body['last_name'] = $last_name;
	}

	wp_remote_post( 'https://www.getrevue.co/api/v2/subscribers', array(
		'headers' => array(
			'Authorization' => 'Token token="' . $options['api_key'] . '"',
		),
		'body'    => $body,
	) );
}


function revue_admin_styles() {
	echo '<style>
		.wrap-revue #submit {
			background: transparent;
			border: 0;
			text-shadow: none;
			box-shadow: none;
			-webkit-box-shadow: 0;
			background-color: #E15718;
			border-radius: 18px;
			padding: 0 40px;
			height: 36px;
		}

		.wrap-revue h2 {
			display: none;
		}

		.wrap-revue th, .wrap-revue td {
			width: 100%;
			display: block;
			padding-top: 0;
			padding-left: 0;
			padding-bottom: 10px;
		}

		.wrap-revue td {
			padding-bottom: 0;
		}

		.wrap-revue #api_key {
			width: 50%;
			padding: 10px;
		}

		.wrap-revue p.submit {
			padding-top: 20px !important;
			margin-top: 0;
		}
	</style>';
}

add_action( 'admin_head', 'revue_admin_styles' );

function revue_admin_placeholder() {
	echo '<script type="text/javascript">';
	echo 'jQuery(function($) { $(".wrap-revue #api_key").attr("placeholder", ' . __( "Your API key", 'revue') . '); });';
	echo '</script>';
}

add_action( 'admin_footer', 'revue_admin_placeholder' );

function revue_get_profile_url() {
	if ( false === ( $profileUrl = get_transient( 'revue_profile_url' ) ) ) {
		$options = get_option( 'revue_general' );
		$resp    = wp_remote_get( 'https://www.getrevue.co/api/v2/accounts/me', array(
			'headers' => array(
				'Authorization' => 'Token token="' . $options['api_key'] . '"',
			),
		) );

		$data = json_decode( $resp['body'], true );

		if ( ! empty( $data['profile_url'] ) ) {
			$profileUrl = $data['profile_url'];
			set_transient( 'revue_profile_url', $profileUrl, 24 * HOUR_IN_SECONDS );
		} else {
			$profileUrl = '';
		}
	}

	return $profileUrl;
}