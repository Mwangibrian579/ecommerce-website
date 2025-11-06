<?php
// M-Pesa Configuration
define('MPESA_ENVIRONMENT', 'sandbox'); // 'sandbox' or 'production'

// Your Sandbox Credentials from the image
define('MPESA_CONSUMER_KEY', 'SFZxbdiaqW3djNRBTMzmRyk5NtHILDhKzAfh7CRdWNLadhEe');
define('MPESA_CONSUMER_SECRET', 'SLOCd8REwqCsjZaWoVArpKaAlUe4WohnMYWDqL2sJF14wGWPlGlzdyaDFYzAodfH');
define('MPESA_SHORTCODE', '174379'); // Sandbox test shortcode
define('MPESA_PASSKEY', 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'); // Sandbox test passkey
define('MPESA_CALLBACK_URL', 'http://localhost/ecommerce-website/mpesa_callback.php');

// Production Credentials (When ready for production)
/*
define('MPESA_CONSUMER_KEY', 'your_production_consumer_key');
define('MPESA_CONSUMER_SECRET', 'your_production_consumer_secret');
define('MPESA_SHORTCODE', 'your_business_shortcode');
define('MPESA_PASSKEY', 'your_production_passkey');
define('MPESA_CALLBACK_URL', 'https://yourdomain.com/ecommerce-website/mpesa_callback.php');
*/
?>