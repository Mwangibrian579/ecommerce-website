<?php
session_start();
require_once "config/database.php";
require_once "includes/MpesaService.php";

echo "<h1>Testing M-Pesa Integration</h1>";

try {
    // Initialize M-Pesa service
    $mpesa = new MpesaService();
    
    echo "<h2>1. Testing Access Token</h2>";
    $accessToken = $mpesa->getAccessToken();
    
    if ($accessToken) {
        echo "<p style='color: green;'>✅ Access Token Obtained Successfully!</p>";
        echo "<p><strong>Token:</strong> " . substr($accessToken, 0, 50) . "...</p>";
    } else {
        echo "<p style='color: red;'>❌ Failed to get Access Token</p>";
        exit;
    }
    
    echo "<h2>2. Testing Phone Number Formatting</h2>";
    $testNumbers = ['0712345678', '254712345678', '0123456789'];
    foreach ($testNumbers as $number) {
        $formatted = $mpesa->formatPhoneNumber($number);
        echo "<p><strong>$number</strong> → " . ($formatted ? $formatted : 'Invalid') . "</p>";
    }
    
    echo "<h2>3. Testing STK Push (Simulation)</h2>";
    $result = $mpesa->simulateSTKPush('254708374149', 1, 'TEST123');
    
    if ($result['success']) {
        echo "<p style='color: green;'>✅ STK Push Simulation Successful!</p>";
        echo "<pre>" . print_r($result, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ STK Push Simulation Failed: " . $result['message'] . "</p>";
    }
    
    echo "<h2>4. Configuration Summary</h2>";
    echo "<ul>";
    echo "<li><strong>Environment:</strong> sandbox</li>";
    echo "<li><strong>Consumer Key:</strong> " . substr(MPESA_CONSUMER_KEY, 0, 20) . "...</li>";
    echo "<li><strong>Shortcode:</strong> " . MPESA_SHORTCODE . "</li>";
    echo "<li><strong>Callback URL:</strong> " . MPESA_CALLBACK_URL . "</li>";
    echo "</ul>";
    
    echo "<h2>5. Next Steps</h2>";
    echo "<p><a href='checkout.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Test Complete Checkout</a></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>