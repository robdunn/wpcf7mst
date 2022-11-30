# Contact Form 7 to MS Teams

This project contains source code and supporting files for a Wordpress plugin that posts Contact Form 7 data to MS Teams channels.

## Installation

To upload the Contact Form 7 to MS Teams .ZIP file:

1. Upload the WordPress Contact Form 7 to MS Teams Plugin to the /wp-contents/plugins/ folder.
2. Activate the plugin from the "Plugins" menu in WordPress.
3. Within MS Teams Right click on the channel you wish to post form data to, and select "Connectors".
4. Add "Incoming Webhook"
5. Configure the new Incoming Webhook, by providing a webhook name. Click on Create
6. Copy the created Webhook URL 
7. Back in Wordpress, Navigate to "Contact to Teams Options"
8. Paste the generated URL in the "Webhook URL" field below