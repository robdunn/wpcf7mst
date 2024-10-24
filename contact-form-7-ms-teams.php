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

function wpcf7mst_on_submit( $form, &$abort, $submission) {

    $data = $submission->get_posted_data();

    $options = get_option( 'wpcf7mst_options' );

    $form_id = $form->id();

    if (isset($options["wpcf7mst_field_url_" . $form_id]) && $options["wpcf7mst_field_url_" . $form_id] !== '') {

        $url = $options["wpcf7mst_field_url_" . $form_id];
        error_log("url: " . print_r($url, true), 0);

        $form_data = '';
        foreach ($data as $key => $value) {
			$form_data .= '**' . str_replace('-', ' ', $key) . "**: " . $value . "\n\n\n";
        }
        $data = array( 'type' => 'message', 'attachments' => array(array( 
            "contentType" => "application/vnd.microsoft.card.adaptive",
            "contentUrl" => null,
            "content" => array(
                '$schema' => "http://adaptivecards.io/schemas/adaptive-card.json",
                "type" => "AdaptiveCard",
                "version" => "1.2",
                "body" => array(
                    array(
                        "type" => "TextBlock",
                        "text" => $form->title(),
                        "weight" => "bolder",
                        "size" => "large"
                    ),
                    array(
                        "type" => "TextBlock",
                        "text" => $form_data,
                        "wrap" => true
                    )
                )
            )
        )) );

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
        $response = curl_exec($ch);
        if($response === FALSE){
            error_log("wpcf7mst error: " . print_r(curl_error($ch), true), 0);
            die(curl_error($ch));
        }
        $responseData = json_decode($response, TRUE);
        curl_close($ch);
    }
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


    $forms_args = array('post_type' => 'wpcf7_contact_form', 'posts_per_page' => -1);
	$cf7Forms = get_posts( $forms_args );
    foreach ($cf7Forms as &$cf7Form) {
        add_settings_field(
            'wpcf7mst_field_form_url_' . $cf7Form->ID,
            __( 'Webhook URL for ' . $cf7Form->post_title, 'wpcf7mst' ),
            'wpcf7mst_field_form_url_cb',
            'wpcf7mst',
            'wpcf7mst_section_developers',
            array(
                'label_for'         => 'wpcf7mst_field_url_' . $cf7Form->ID,
                'class'             => 'wpcf7mst_row',
                'wpcf7mst_custom_data' => 'custom',
            )
        );
    }
}

/**
 * Register our wpcf7mst_settings_init to the admin_init action hook.
 */
add_action( 'admin_init', 'wpcf7mst_settings_init' );

/**
 *
 * @param array $args  The settings array, defining title, id, callback.
 */
function wpcf7mst_section_developers_callback( $args ) {
	?>
	<p id="<?php echo esc_attr( $args['id'] ); ?>"><?php esc_html_e( 'To connect Contact Form 7 to MS Teams:', 'wpcf7mst' ); ?></p>
    <ol>
        <li>Right click on the channel you wish to post form data to, and select "Workflows".</li>
        <li>Type "Post to a channel when a webhook request is received" in the "Find workflows" box.</li>
		<li>Click the template.</li>
        <li>Configure the template by providing a webhook name and selecting a connection. Click next</li>
		<li>Choose a Team and Channel to post the hook to and click "Add workflow".</li>
        <li>Copy and paste the generated URL in the "Webhook URL" field below</li>
    </ol>
	<?php
}

/**
 *
 * @param array $args
 */
function wpcf7mst_field_form_url_cb( $args ) {
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