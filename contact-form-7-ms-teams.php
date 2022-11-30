<?php
/**
Plugin Name: Contact Form 7 to MS Teams
description: Post Contact Form 7 data to MS Teams channels
Version: 1.0
Author: Rob Dunn
License: MIT
Text Domain: contact-form-7-ms-teams
*/
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
require_once 'vendor/autoload.php';

function wpcf7mst_on_submit( $form, &$abort, $submission) {

    $data = $submission->get_posted_data();
    error_log("name: " . print_r($data, true), 0);

    $options = get_option( 'wpcf7mst_options' );

    $url = $options['wpcf7mst_field_url'];
    $data = array( 'text' => '<strong>From:</strong> ' . $data['your-name'] . '<br /><strong>Email:</strong> <a href="mailto:' . $data['your-email'] . '">' . $data['your-email'] . '</a><br /><strong>Subject:</strong> ' . $data['your-subject'] . '<br /><strong>Message:</strong> ' . $data['your-message'] );

    // Setup cURL
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_POST => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_HTTPHEADER => array(
            'Content-Type: application/json'
        ),
        CURLOPT_POSTFIELDS => json_encode($data)
    ));

    // Send the request
    $response = curl_exec($ch);

    // Check for errors
    if($response === FALSE){
        die(curl_error($ch));
    }

    // Decode the response
    $responseData = json_decode($response, TRUE);

    // Close the cURL handler
    curl_close($ch);

    error_log("name: " . print_r($responseData, true), 0);

}

add_action('wpcf7_before_send_mail', 'wpcf7mst_on_submit', 10, 3);


/**
 * custom option and settings
 */
function wpcf7mst_settings_init() {
	// Register a new setting for "wpcf7mst" page.
	register_setting( 'wpcf7mst', 'wpcf7mst_options' );

	// Register a new section in the "wpcf7mst" page.
	add_settings_section(
		'wpcf7mst_section_developers',
		__( 'Webhook connector', 'wpcf7mst' ), 'wpcf7mst_section_developers_callback',
		'wpcf7mst'
	);

	// Register a new field in the "wpcf7mst_section_developers" section, inside the "wpcf7mst" page.
	add_settings_field(
		'wpcf7mst_field_url', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'Webhook URL', 'wpcf7mst' ),
		'wpcf7mst_field_url_cb',
		'wpcf7mst',
		'wpcf7mst_section_developers',
		array(
			'label_for'         => 'wpcf7mst_field_url',
			'class'             => 'wpcf7mst_row',
			'wpcf7mst_custom_data' => 'custom',
		)
	);
}

/**
 * Register our wpcf7mst_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'wpcf7mst_settings_init' );


/**
 * Custom option and settings:
 *  - callback functions
 */


/**
 * Developers section callback function.
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function wpcf7mst_section_developers_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'To connect Contact Form 7 to MS Teams:', 'wpcf7mst' ); ?></p>
    <ol>
        <li>Right click on the channel you wish to post form data to, and select "Connectors".</li>
        <li>Add "Incoming Webhook"</li>
        <li>Configure Incoming Webhook, by providing a webhook name. Click on Create</li>
        <li>Copy and paste the generated URL in the "Webhook URL" field below</li>
    </ol>
	<?php
}

/**
 * Pill field callbakc function.
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function wpcf7mst_field_url_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'wpcf7mst_options' );
	?>
	<input
            type="url"
            class="regular-text code"
			id="<?php echo esc_attr( $args['label_for'] ); ?>"
			data-custom="<?php echo esc_attr( $args['wpcf7mst_custom_data'] ); ?>"
			name="wpcf7mst_options[<?php echo esc_attr( $args['label_for'] ); ?>]"
            value="<?php echo $options[ $args['label_for'] ]; ?>" />
	<p class="description">
		<?php esc_html_e( 'Enter your MS Teams Webhook connector url here', 'wpcf7mst' ); ?>
	</p>
	<?php
}

/**
 * Add the top level menu page.
 */
function wpcf7mst_options_page() {
	add_menu_page(
		'Contact Form 7 to MS Teams',
		'Contact to Teams Options',
		'manage_options',
		'wpcf7mst',
		'wpcf7mst_options_page_html'
	);
}


/**
 * Register our wpcf7mst_options_page to the admin_menu action hook.
 */
add_action( 'admin_menu', 'wpcf7mst_options_page' );


/**
 * Top level menu callback function
 */
function wpcf7mst_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'wpcf7mst_messages', 'wpcf7mst_message', __( 'Settings Saved', 'wpcf7mst' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'wpcf7mst_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "wpcf7mst"
			settings_fields( 'wpcf7mst' );
			// output setting sections and their fields
			// (sections are registered for "wpcf7mst", each field is registered to a specific section)
			do_settings_sections( 'wpcf7mst' );
			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}