<?php
// M-Pesa Callback Handler
require_once "config/database.php";
require_once "includes/MpesaService.php";
require_once "includes/Order.php";

// Log the callback for debugging
file_put_contents('mpesa_callback.log', date('Y-m-d H:i:s') . " - " . file_get_contents('php://input') . "\n", FILE_APPEND);

// Get the callback data
$callbackData = json_decode(file_get_contents('php://input'), true);

if (!$callbackData) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback data']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$mpesaService = new MpesaService();
$validationResult = $mpesaService->validateCallback($callbackData);

if (!$validationResult['valid']) {
    http_response_code(400);
    echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Invalid callback format']);
    exit;
}

try {
    if ($validationResult['success']) {
        // Payment was successful
        $receiptNumber = $validationResult['mpesa_receipt_number'];
        $amount = $validationResult['amount'];
        $phone = $validationResult['phone_number'];
        
        // Find order by checkout request ID
        $checkoutRequestID = $validationResult['checkout_request_id'];
        
        $query = "SELECT order_id FROM payments WHERE checkout_request_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $checkoutRequestID);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            $order_id = $payment['order_id'];
            
            // Update order status
            $order = new Order($db);
            $order->id = $order_id;
            if ($order->updateMpesaReceipt($receiptNumber)) {
                // Log successful transaction
                $logData = [
                    'order_id' => $order_id,
                    'phone_number' => $phone,
                    'amount' => $amount,
                    'merchant_request_id' => $validationResult['merchant_request_id'],
                    'checkout_request_id' => $checkoutRequestID,
                    'mpesa_receipt_number' => $receiptNumber,
                    'transaction_date' => $validationResult['transaction_date'],
                    'result_code' => $validationResult['result_code'],
                    'result_desc' => $validationResult['result_desc']
                ];
                $mpesaService->logTransaction($db, $logData);
                
                http_response_code(200);
                echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Success']);
                exit;
            }
        }
        
        http_response_code(200);
        echo json_encode(['ResultCode' => 1, 'ResultDesc' => 'Order not found']);
        
    } else {
        // Payment failed
        $checkoutRequestID = $validationResult['checkout_request_id'];
        
        // Find and update the payment record
        $query = "SELECT order_id FROM payments WHERE checkout_request_id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $checkoutRequestID);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Log failed transaction
            $logData = [
                'order_id' => $payment['order_id'],
                'phone_number' => '',
                'amount' => 0,
                'merchant_request_id' => $validationResult['merchant_request_id'],
                'checkout_request_id' => $checkoutRequestID,
                'mpesa_receipt_number' => '',
                'transaction_date' => date('YmdHis'),
                'result_code' => $validationResult['result_code'],
                'result_desc' => $validationResult['result_desc']
            ];
            $mpesaService->logTransaction($db, $logData);
        }
        
        http_response_code(200);
        echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Failed payment logged']);
    }
} catch (Exception $e) {
    error_log("M-Pesa Callback Error: " . $e->getMessage());
    http_response_code(200);
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Error processed']);
}
?>