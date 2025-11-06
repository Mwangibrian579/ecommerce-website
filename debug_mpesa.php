<?php
session_start();
require_once "config/database.php";
require_once "includes/MpesaService.php";

echo "<h1>M-Pesa Detailed Debug</h1>";

// Test 1: Check if config file is loaded
echo "<h2>1. Checking Configuration</h2>";
if (defined('MPESA_CONSUMER_KEY')) {
    echo "<p style='color: green;'>✅ Config file loaded</p>";
    echo "<p><strong>Consumer Key:</strong> " . substr(MPESA_CONSUMER_KEY, 0, 20) . "...</p>";
    echo "<p><strong>Consumer Secret:</strong> " . substr(MPESA_CONSUMER_SECRET, 0, 20) . "...</p>";
    echo "<p><strong>Shortcode:</strong> " . MPESA_SHORTCODE . "</p>";
} else {
    echo "<p style='color: red;'>❌ Config file NOT loaded</p>";
    exit;
}

// Test 2: Manual CURL test
echo "<h2>2. Manual CURL Test to M-Pesa</h2>";
$url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
$credentials = base64_encode(MPESA_CONSUMER_KEY . ':' . MPESA_CONSUMER_SECRET);

echo "<p><strong>URL:</strong> $url</p>";
echo "<p><strong>Credentials:</strong> " . substr($credentials, 0, 30) . "...</p>";

$curl = curl_init();
curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_HTTPHEADER => array(
        'Authorization: Basic ' . $credentials,
        'Content-Type: application/json'
    ),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HEADER => true, // Include headers in response
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_VERBOSE => true // Get detailed info
));

$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);
$curl_info = curl_getinfo($curl);

curl_close($curl);

echo "<p><strong>HTTP Code:</strong> $http_code</p>";
echo "<p><strong>CURL Error:</strong> " . ($curl_error ? $curl_error : 'None') . "</p>";

if ($response) {
    echo "<h3>Raw Response:</h3>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

echo "<h3>CURL Info:</h3>";
echo "<pre>" . print_r($curl_info, true) . "</pre>";

// Test 3: Check if credentials are valid
echo "<h2>3. Credential Validation</h2>";
$test_url = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $test_url,
    CURLOPT_HTTPHEADER => [
        'Authorization: Basic ' . $credentials
    ],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT => 10
]);

$result = curl_exec($ch);
$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Test Request Status:</strong> $status</p>";
if ($result) {
    echo "<p><strong>Response:</strong> " . htmlspecialchars($result) . "</p>";
    
    $data = json_decode($result, true);
    if (isset($data['errorMessage'])) {
        echo "<p style='color: red;'><strong>Error:</strong> " . $data['errorMessage'] . "</p>";
    }
    if (isset($data['access_token'])) {
        echo "<p style='color: green;'>✅ SUCCESS! Access token obtained</p>";
    }
}

// Test 4: Check file permissions and paths
echo "<h2>4. File System Check</h2>";
$config_file = __DIR__ . '/config/mpesa_config.php';
echo "<p><strong>Config file path:</strong> $config_file</p>";
echo "<p><strong>File exists:</strong> " . (file_exists($config_file) ? 'Yes' : 'No') . "</p>";
echo "<p><strong>File readable:</strong> " . (is_readable($config_file) ? 'Yes' : 'No') . "</p>";

// Test 5: Check if CURL is enabled
echo "<h2>5. PHP Configuration</h2>";
echo "<p><strong>CURL enabled:</strong> " . (function_exists('curl_version') ? 'Yes' : 'No') . "</p>";
echo "<p><strong>OpenSSL:</strong> " . (extension_loaded('openssl') ? 'Yes' : 'No') . "</p>";

$curl_version = curl_version();
echo "<p><strong>CURL Version:</strong> " . ($curl_version['version'] ?? 'Unknown') . "</p>";
echo "<p><strong>SSL Version:</strong> " . ($curl_version['ssl_version'] ?? 'Unknown') . "</p>";
?>