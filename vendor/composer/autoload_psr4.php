<?php

// autoload_psr4.php @generated by Composer

$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
    'Webstarters\\SendGrid\\' => array($baseDir . '/src'),
    'SendGrid\\Stats\\' => array($vendorDir . '/sendgrid/sendgrid/lib/stats'),
    'SendGrid\\Mail\\' => array($vendorDir . '/sendgrid/sendgrid/lib/mail'),
    'SendGrid\\Contacts\\' => array($vendorDir . '/sendgrid/sendgrid/lib/contacts'),
    'SendGrid\\' => array($vendorDir . '/sendgrid/php-http-client/lib', $vendorDir . '/sendgrid/sendgrid/lib'),
);
