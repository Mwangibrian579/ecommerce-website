<?php
class MpesaService {
    private $consumerKey;
    private $consumerSecret;
    private $shortcode;
    private $passkey;
    private $callbackUrl;
    private $environment;
    
    public function __construct($environment = 'sandbox') {
        $this->environment = $environment;
        $this->loadCredentials();
    }

    private function loadCredentials() {
        // Load credentials based on environment
        if ($this->environment === 'production') {
            $this->consumerKey = 'I2jT1lXtJO8bGUqRIdevdGAN4DgXkjENS1MM3EeMURAwb9wJ';
            $this->consumerSecret = '5YXGIocBCbvmr1jD71zDmqeWTPgUQyDKnyS6JKOqABl1sdGnWANaMjrSGcWnVmWr';
            $this->shortcode = '174379';
            $this->passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
            $this->callbackUrl = 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials' ;
        } else {
            // Sandbox credentials (default)
            $this->consumerKey = 'I2jT1lXtJO8bGUqRIdevdGAN4DgXkjENS1MM3EeMURAwb9wJ';
            $this->consumerSecret = '5YXGIocBCbvmr1jD71zDmqeWTPgUQyDKnyS6JKOqABl1sdGnWANaMjrSGcWnVmWr';
            $this->shortcode = '174379'; // Sandbox test shortcode
            $this->passkey = 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919';
            $this->callbackUrl ='https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';
        }
    }

    // Get base URL based on environment
    private function getBaseUrl() {
        return $this->environment === 'production' 
            ? 'https://api.safaricom.co.ke'
            : 'https://sandbox.safaricom.co.ke';
    }

    // Get access token
    public function getAccessToken() {
        $url = $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';
        
        $credentials = base64_encode($this->consumerKey . ':' . $this->consumerSecret);
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Authorization: Basic ' . $credentials,
                'Content-Type: application/json'
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code == 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }
        
        error_log("M-Pesa Access Token Error: HTTP $http_code - $response");
        return null;
    }

    // Generate password for STK Push
    private function generatePassword() {
        $timestamp = date('YmdHis');
        $password = base64_encode($this->shortcode . $this->passkey . $timestamp);
        return [
            'password' => $password,
            'timestamp' => $timestamp
        ];
    }

    // Format phone number to 2547XXXXXXXX
    public function formatPhoneNumber($phone) {
        // Remove any non-digit characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Convert to 254 format
        if (strlen($phone) == 9 && substr($phone, 0, 1) == '7') {
            return '254' . $phone;
        } elseif (strlen($phone) == 10 && substr($phone, 0, 1) == '0') {
            return '254' . substr($phone, 1);
        } elseif (strlen($phone) == 12 && substr($phone, 0, 3) == '254') {
            return $phone;
        }
        
        return null; // Invalid format
    }

    // Initiate STK Push
    public function stkPush($phone, $amount, $accountReference, $transactionDesc) {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return [
                'success' => false,
                'message' => 'Failed to get access token from M-Pesa'
            ];
        }

        // Format phone number
        $formattedPhone = $this->formatPhoneNumber($phone);
        if (!$formattedPhone) {
            return [
                'success' => false,
                'message' => 'Invalid phone number format. Use 07... or 2547...'
            ];
        }

        // Validate amount
        if ($amount <= 0) {
            return [
                'success' => false,
                'message' => 'Amount must be greater than 0'
            ];
        }

        $passwordData = $this->generatePassword();
        
        $request_data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $passwordData['password'],
            'Timestamp' => $passwordData['timestamp'],
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $formattedPhone,
            'PartyB' => $this->shortcode,
            'PhoneNumber' => $formattedPhone,
            'CallBackURL' => $this->callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $transactionDesc
        ];

        $url = $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($curl);
        curl_close($curl);
        
        if ($curl_error) {
            return [
                'success' => false,
                'message' => 'CURL Error: ' . $curl_error
            ];
        }
        
        $response_data = json_decode($response, true);
        
        if ($http_code == 200 && isset($response_data['ResponseCode'])) {
            if ($response_data['ResponseCode'] == '0') {
                return [
                    'success' => true,
                    'message' => 'STK Push initiated successfully',
                    'CheckoutRequestID' => $response_data['CheckoutRequestID'],
                    'MerchantRequestID' => $response_data['MerchantRequestID'],
                    'ResponseCode' => $response_data['ResponseCode'],
                    'ResponseDescription' => $response_data['ResponseDescription'],
                    'CustomerMessage' => $response_data['CustomerMessage'] ?? ''
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'M-Pesa Error: ' . ($response_data['ResponseDescription'] ?? 'Unknown error'),
                    'ResponseCode' => $response_data['ResponseCode']
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => 'HTTP Error: ' . $http_code . ' - ' . $response,
            'http_code' => $http_code
        ];
    }

    // Check transaction status
    public function checkTransactionStatus($checkoutRequestID) {
        $accessToken = $this->getAccessToken();
        
        if (!$accessToken) {
            return ['success' => false, 'message' => 'Failed to get access token'];
        }

        $passwordData = $this->generatePassword();
        
        $request_data = [
            'BusinessShortCode' => $this->shortcode,
            'Password' => $passwordData['password'],
            'Timestamp' => $passwordData['timestamp'],
            'CheckoutRequestID' => $checkoutRequestID
        ];

        $url = $this->getBaseUrl() . '/mpesa/stkpushquery/v1/query';
        
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $accessToken
            ),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($request_data),
            CURLOPT_HEADER => false,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_TIMEOUT => 30
        ));
        
        $response = curl_exec($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        
        if ($http_code == 200) {
            return json_decode($response, true);
        }
        
        return ['success' => false, 'message' => 'HTTP Error: ' . $http_code];
    }

    // For testing/development - Simulate M-Pesa response
    public function simulateSTKPush($phone, $amount, $orderNumber) {
        // This simulates a successful M-Pesa response for development
        // Remove this in production and use the actual stkPush method
        
        $formattedPhone = $this->formatPhoneNumber($phone);
        
        return [
            'success' => true,
            'message' => 'STK Push simulated successfully (Development Mode)',
            'CheckoutRequestID' => 'ws_CO_' . date('YmdHis') . '_' . uniqid(),
            'MerchantRequestID' => 'SIM-' . date('YmdHis') . rand(1000, 9999),
            'ResponseCode' => '0',
            'ResponseDescription' => 'Success. Request accepted for processing',
            'CustomerMessage' => 'Success. Request accepted for processing',
            'simulated' => true,
            'phone' => $formattedPhone,
            'amount' => $amount,
            'order_number' => $orderNumber
        ];
    }

    // Validate M-Pesa callback
    public function validateCallback($callbackData) {
        if (!isset($callbackData['Body']['stkCallback'])) {
            return ['valid' => false, 'message' => 'Invalid callback format'];
        }

        $stkCallback = $callbackData['Body']['stkCallback'];
        $resultCode = $stkCallback['ResultCode'];
        $resultDesc = $stkCallback['ResultDesc'];
        $merchantRequestID = $stkCallback['MerchantRequestID'];
        $checkoutRequestID = $stkCallback['CheckoutRequestID'];

        if ($resultCode == 0) {
            // Successful payment
            if (isset($stkCallback['CallbackMetadata']['Item'])) {
                $items = $stkCallback['CallbackMetadata']['Item'];
                $callbackMetadata = [];
                
                foreach ($items as $item) {
                    $callbackMetadata[$item['Name']] = $item['Value'] ?? '';
                }
                
                return [
                    'valid' => true,
                    'success' => true,
                    'result_code' => $resultCode,
                    'result_desc' => $resultDesc,
                    'merchant_request_id' => $merchantRequestID,
                    'checkout_request_id' => $checkoutRequestID,
                    'amount' => $callbackMetadata['Amount'] ?? null,
                    'mpesa_receipt_number' => $callbackMetadata['MpesaReceiptNumber'] ?? null,
                    'transaction_date' => $callbackMetadata['TransactionDate'] ?? null,
                    'phone_number' => $callbackMetadata['PhoneNumber'] ?? null
                ];
            }
        }

        return [
            'valid' => true,
            'success' => false,
            'result_code' => $resultCode,
            'result_desc' => $resultDesc,
            'merchant_request_id' => $merchantRequestID,
            'checkout_request_id' => $checkoutRequestID
        ];
    }

    // Log M-Pesa transaction
    public function logTransaction($db, $data) {
        $query = "INSERT INTO payments 
                 SET order_id=:order_id, phone_number=:phone_number, 
                     amount=:amount, merchant_request_id=:merchant_request_id,
                     checkout_request_id=:checkout_request_id, 
                     mpesa_receipt_number=:mpesa_receipt_number,
                     transaction_date=:transaction_date,
                     result_code=:result_code, result_desc=:result_desc,
                     status=:status, created_at=NOW()";

        $stmt = $db->prepare($query);
        
        $status = ($data['result_code'] == 0) ? 'success' : 'failed';
        
        $stmt->bindParam(":order_id", $data['order_id']);
        $stmt->bindParam(":phone_number", $data['phone_number']);
        $stmt->bindParam(":amount", $data['amount']);
        $stmt->bindParam(":merchant_request_id", $data['merchant_request_id']);
        $stmt->bindParam(":checkout_request_id", $data['checkout_request_id']);
        $stmt->bindParam(":mpesa_receipt_number", $data['mpesa_receipt_number']);
        $stmt->bindParam(":transaction_date", $data['transaction_date']);
        $stmt->bindParam(":result_code", $data['result_code']);
        $stmt->bindParam(":result_desc", $data['result_desc']);
        $stmt->bindParam(":status", $status);

        return $stmt->execute();
    }
}

// Helper function to get M-Pesa instance
function getMpesaService($environment = 'sandbox') {
    return new MpesaService($environment);
}
?>