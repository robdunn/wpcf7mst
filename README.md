# Contact Form 7 to MS Teams

This project contains source code and supporting files for a Wordpress plugin that posts Contact Form 7 data to MS Teams channels.

## Installation

To upload the Contact Form 7 to MS Teams .ZIP file:

1. Upload the WordPress Contact Form 7 to MS Teams Plugin to the /wp-contents/plugins/ folder.
2. Activate the plugin from the "Plugins" menu in WordPress.
3. Within MS Teams Right click on the channel you wish to post form data to, and select "Workflows".
4. Type "Post to a channel when a webhook request is received" in the "Find workflows" box. Once found, click the template.
5. Configure the template by providing a webhook name and selecting a user for the connection. Click next.
6. Choose a Team and Channel to post the hook to and click "Add workflow".
7. Copy the generated URL.
7. Back in Wordpress, Navigate to "Contact to Teams Options".
8. Paste the URL in the "Webhook URL" field for the form you'd like to post to teams.