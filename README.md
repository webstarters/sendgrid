# SendGrid

A WordPress-plugin from Webstarters.

## Requirements

- WordPress 5.2
- PHP 7.1

In addition, you must have the necessary credentials to access the SendGrid API.

## Installation

You can install the package via Composer:

```bash
composer require webstarters/sendgrid
```

...or download the plugin as a ZIP and upload it via WP-Admin.

### Configuration

You must set `WS_SENDGRID_API_KEY` in your `wp-config.php` with your SendGrid API Key.

You may set a default SendGrid Template ID for all mails via `WS_SENDGRID_DEFAULT_TEMPLATE_ID`.

## Usage

This plugin overwrites ```wp_mail``` to send via SendGrid.

You can also send mail via the new ```sendgrid_mail``` method, which extends wp_mail, but adds the possibility of specifying template data and ID.

### Debugging

To begin, enable ```WP_DEBUG``` and ```WP_DEBUG_LOG``` in the ```wp_config.php``` file.

Remember to disable ```WP_DEBUG_DISPLAY``` if the website is in a production environment.

## Security

For any security related issues, send a mail to [pj+security@webstarters.dk](mailto:pj+security@webstarters.dk) instead of using the issue tracker.

## Credits

- [Peter Jørgensen](https://github.com/peterchrjoergensen)
- [All Contributors](../../contributors)

## About

We are a Digital agency focusing on efficient sparring, efficient process and efficient digital solutions. Just that, pure and precise effect.

When we say Webstarters, that's exactly what we are. And what we do.

Learn more at [Webstarters.dk digital marketing bureau](https://webstarters.dk).

## License

[MIT License](LICENSE)