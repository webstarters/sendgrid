<?php

namespace Webstarters\SendGrid;

use WP_Error;
use SendGrid;
use SendGrid\Mail\Mail;
use SendGrid\Mail\Attachment;

class WS_SendGrid
{
    /**
     * Set up constants.
     *
     * @return void
     */
    public static function init()
    {
        if (! defined('WS_SENDGRID_API_KEY')) {
            add_action('admin_notices', function () {
                $message = sprintf(esc_html__('%1$s requires SendGrid API Key. The plugin will not work without it.', 'webstarters'), 'Webstarters SendGrid');
                $html = sprintf('<div class="error">%s</div>', wpautop($message));
                echo wp_kses_post($html);
            });
        }

        if (! defined('WS_SENDGRID_FROM_NAME')) {
            define('WS_SENDGRID_FROM_NAME', 'WordPress');
        }

        if (! defined('WS_SENDGRID_FROM_EMAIL')) {
            $siteName = strtolower($_SERVER['SERVER_NAME']);
            if (substr($siteName, 0, 4) == 'www.') {
                $siteName = substr($siteName, 4);
            }

            define('WS_SENDGRID_FROM_EMAIL', "wordpress@{$siteName}");
        }

        if (! defined('WS_SENDGRID_CONTENT_TYPE')) {
            define('WS_SENDGRID_CONTENT_TYPE', 'text/html');
        }

        if (! defined('WS_SENDGRID_DEFAULT_TEMPLATE_ID')) {
            define('WS_SENDGRID_DEFAULT_TEMPLATE_ID', '');
        }

        if (! defined('WS_SENDGRID_OVERWRITE_WP_MAIL')) {
            define('WS_SENDGRID_OVERWRITE_WP_MAIL', true);
        }
    }

    protected static function getFromEmail()
    {
        return apply_filters('sendgrid_mail_from', WS_SENDGRID_FROM_EMAIL);
    }

    protected static function getFromName()
    {
        return apply_filters('sendgrid_mail_from_name', WS_SENDGRID_FROM_NAME);
    }

    protected static function getContentType()
    {
        return apply_filters('sendgrid_mail_content_type', WS_SENDGRID_CONTENT_TYPE);
    }

    protected static function getTemplateId()
    {
        return apply_filters('sendgrid_mail_template_id', WS_SENDGRID_DEFAULT_TEMPLATE_ID);
    }

    protected static function getApiKey()
    {
        return WS_SENDGRID_API_KEY;
    }

    /**
     * Send a mail.
     *
     * @param string       $to              Array or comma-separated list of email addresses to send message.
     * @param string       $subject         Email subject.
     * @param string       $message         Message contents.
     * @param string|array $headers         Optional. Additional headers.
     * @param string|array $attachments     Optional. Files to attach.
     * @param array        $templateData    Optional. Dynamic template data.
     * @param int          $templateId      Optional. Specify mail template.
     *
     * @return bool Whether the email contents were sent successfully.
     */
    public static function mail($to, $subject, $message, $headers = '', $attachments = [], $templateData = [], $templateId = null)
    {
        $email = new Mail();

        // Headers.
        $cc = $bcc = $replyTo = [];

        if (empty($headers)) {
            $headers = [];
        } else {
            if (! is_array($headers)) {
                // Explode the headers out, so this function can take both string headers and an array of headers.
                $tempHeaders = explode("\n", str_replace("\r\n", "\n", $headers));
            } else {
                $tempHeaders = $headers;
            }

            $headers = [];

            // If it's actually got contents.
            if (! empty($tempHeaders)) {

                // Iterate through the raw headers.
                foreach ((array) $tempHeaders as $header) {
                    if (strpos($header, ':') === false) {
                        if (false !== stripos($header, 'boundary=')) {
                            $parts    = preg_split('/boundary=/i', trim($header));
                            $boundary = trim(str_replace(["'", '"'], '', $parts[1]));
                        }
                        continue;
                    }

                    // Explode them out.
                    list($headerName, $headerContent) = explode(':', trim($header), 2);

                    // Cleanup crew.
                    $headerName    = trim($headerName);
                    $headerContent = trim($headerContent);

                    switch (strtolower($headerName)) {
                        // Mainly for legacy -- process a From: header if it's there
                        case 'from':
                            $bracketPosition = strpos($headerContent, '<');
                            if ($bracketPosition !== false) {
                                // Text before the bracketed email is the "From" name.
                                if ( $bracketPosition > 0 ) {
                                    $fromName = substr($headerContent, 0, $bracketPosition - 1);
                                    $fromName = str_replace('"', '', $fromName);
                                    $fromName = trim($fromName);
                                }

                                $fromEmail = substr($headerContent, $bracketPosition + 1);
                                $fromEmail = str_replace('>', '', $fromEmail);
                                $fromEmail = trim($fromEmail);

                            // Avoid setting an empty $fromEmail.
                            } elseif ('' !== trim($headerContent) ) {
                                $fromEmail = trim($headerContent);
                            }
                            break;

                        case 'content-type':
                            if (strpos($headerContent, ';') !== false) {
                                list($type, $charsetContent) = explode(';', $headerContent);
                                $contentType = trim($type);

                                if (false !== stripos($charsetContent, 'charset=')) {
                                    $charset = trim(str_replace(['charset=', '"'], '', $charsetContent));
                                } elseif (false !== stripos($charsetContent, 'boundary=')) {
                                    $boundary = trim(str_replace(['BOUNDARY=', 'boundary=', '"'], '', $charsetContent));
                                    $charset = '';
                                }

                            // Avoid setting an empty $contentType.
                            } elseif ('' !== trim($headerContent)) {
                                $contentType = trim($headerContent);
                            }
                            break;
                        case 'cc':
                            $cc = array_merge((array) $cc, explode(',', $headerContent));
                            break;
                        case 'bcc':
                            $bcc = array_merge((array) $bcc, explode(',', $headerContent));
                            break;
                        case 'reply-to':
                            $replyTo = array_merge((array) $replyTo, explode(',', $headerContent));
                            break;
                        default:
                            // Add it to our grand headers array.
                            $headers[trim($headerName)] = trim($headerContent);
                            break;
                    }
                }
            }
        }

        // Subject.
        $email->setSubject((string) $subject);

        // From.
        if (empty($fromEmail)) {
            $fromEmail = self::getFromEmail();
        }

        $fromEmail = apply_filters('wp_mail_from', $fromEmail);

        if (empty($fromName)) {
            $fromName = self::getFromName();
        }

        $fromName = apply_filters('wp_mail_from_name', $fromName);

        $email->setFrom($fromEmail, $fromName);

        // Recipients.
        if (! is_array($to)) {
            $to = explode(',', $to);
        }

        // Set destination addresses, using appropriate methods for handling addresses.
        $recipientTypes = compact('to', 'cc', 'bcc', 'replyTo');

        foreach ($recipientTypes as $recipientType => $recipients) {
            if (empty($recipients)) {
                continue;
            }

            foreach ((array) $recipients as $recipient) {
                // Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
                $recipientName = '';

                if (preg_match( '/(.*)<(.+)>/', $recipient, $matches)) {
                    if (count($matches) == 3) {
                        $recipientName = $matches[1];
                        $recipient = $matches[2];
                    }
                }

                switch ($recipientType) {
                    case 'to':
                        $email->addTo($recipient, $recipientName);
                        break;
                    case 'cc':
                        $email->addCc($recipient, $recipientName);
                        break;
                    case 'bcc':
                        $email->addBcc($recipient, $recipientName);
                        break;
                    case 'replyTo':
                        $email->setReplyTo($recipient, $recipientName);
                        break;
                }
            }
        }

        // Attachments.
        if (! is_array($attachments)) {
            $attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
        }

        // FIXME This might not work. Needs more testing.
        if (! empty($attachments)  && count($attachments)) {
            foreach ($attachments as $attachment) {
                $sendGridAttachment = new Attachment();
                $sendGridAttachment->setType("application/text");
                $sendGridAttachment->setContent(base64_encode(file_get_contents($attachment)));
                $sendGridAttachment->setDisposition("attachment");
                $sendGridAttachment->setFilename(basename($attachment));

                $email->addAttachment($sendGridAttachment);
            }
        }

        // Content.
        if (! isset($contentType)) {
            $contentType = self::getContentType();
        }

        $contentType = apply_filters('wp_mail_content_type', $contentType);

        $email->addContent($contentType, $message);

        // Template ID.
        if (empty($templateId)) {
            $templateId = self::getTemplateId();
        }

        if (! empty($templateId)) {
            $email->setTemplateId($templateId);
        }

        // Dynamic Template Data.
        $email->addDynamicTemplateDatas(array_merge(
            [
                'subject' => $subject,
                'body' => $message,
            ],
            $templateData
        ));

        // Set categories.
        $email->addCategories([
            'Sent via Webstarters SendGrid WordPress-plugin',
        ]);

        // Send mail and retrieve response from SendGrid.
        $sendgrid = new SendGrid(self::getApiKey());

        try {
            $response = $sendgrid->send($email);

            if (WP_DEBUG && WP_DEBUG_DISPLAY) {
                print $response->statusCode() . "\n";
                print_r($response->headers());
                print $response->body() . "\n";
            }
        } catch (Exception $e) {
            $errorData = compact('to', 'subject', 'message', 'headers', 'attachments');
            $errorData['sendgrid_exception_code'] = $e->getCode();

            do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $e->getMessage(), $errorData));

            if (WP_DEBUG && WP_DEBUG_DISPLAY) {
                echo 'Caught SendGrid exception: '. $e->getMessage() ."\n";
            }

            return false;
        }

        // Anything but 202 Accepted is a fail.
        if ($response->statusCode() !== 202) {
            $errorData = compact('to', 'subject', 'message', 'headers', 'attachments');
            do_action('wp_mail_failed', new WP_Error('wp_mail_failed', '', $errorData));

            return false;
        }

        return true;
    }
}
