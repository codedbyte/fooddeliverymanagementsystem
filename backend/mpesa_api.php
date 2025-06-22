<?php
// backend/mpesa_api.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

require_once 'config.php';
require_once 'database.php';
require_once 'error_handler.php';

$action = $_GET['action'] ?? $_POST['action'] ?? '';
$data = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'stk_push':
        // This action initiates the STK Push to the customer's phone
        $phoneNumber = $data['phone_number'] ?? '';
        $amount = $data['amount'] ?? 0;
        $orderId = $data['order_id'] ?? null; // Link payment to an order
        initiateStkPush($phoneNumber, $amount, $orderId);
        break;
    case 'mpesa_callback':
        // This action is called by Safaricom Daraja API after a payment (success/fail)
        handleMpesaCallback();
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action.']);
        break;
}

function generateAccessToken() {
    $credentials = base64_encode(MPESA_CONSUMER_KEY . ":" . MPESA_CONSUMER_SECRET);
    $url = "https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials"; // Use sandbox for testing

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Basic ' . $credentials]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);
    return $result['access_token'] ?? null;
}

function initiateStkPush($phoneNumber, $amount, $orderId) {
    if (empty($phoneNumber) || $amount <= 0 || empty($orderId)) {
        echo json_encode(['success' => false, 'message' => 'Phone number, amount, and order ID are required.']);
        return;
    }

    $accessToken = generateAccessToken();
    if (!$accessToken) {
        echo json_encode(['success' => false, 'message' => 'Failed to generate M-Pesa access token.']);
        return;
    }

    $timestamp = date('YmdHis');
    $password = base64_encode(MPESA_SHORTCODE . MPESA_PASSKEY . $timestamp);

    $stkPushUrl = "https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest";

    $curl_post_data = [
        'BusinessShortCode' => MPESA_SHORTCODE,
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline', // Or CustomerBuyGoodsOnline for Till
        'Amount' => $amount,
        'PartyA' => $phoneNumber,
        'PartyB' => MPESA_SHORTCODE,
        'PhoneNumber' => $phoneNumber,
        'CallBackURL' => MPESA_CALLBACK_URL,
        'AccountReference' => 'Order-' . $orderId, // Unique reference for your order
        'TransactionDesc' => 'Food Delivery Payment'
    ];

    $data_string = json_encode($curl_post_data);

    $ch = curl_init($stkPushUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($ch);
    curl_close($ch);

    $response_data = json_decode($curl_response, true);

    if (isset($response_data['ResponseCode']) && $response_data['ResponseCode'] == '0') {
        // Payment initiated successfully
        // Store CheckoutRequestID in your database linked to the order for later validation
        $checkoutRequestId = $response_data['CheckoutRequestID'];
        // You would typically update the order status to 'payment_pending' and store checkoutRequestId
        // For simplicity, just sending success here.
        echo json_encode(['success' => true, 'message' => 'STK Push initiated successfully. Please complete the payment on your phone.', 'checkoutRequestID' => $checkoutRequestId]);
    } else {
        error_log("M-Pesa STK Push Error: " . ($response_data['errorMessage'] ?? $curl_response));
        echo json_encode(['success' => false, 'message' => 'Failed to initiate M-Pesa payment: ' . ($response_data['CustomerMessage'] ?? $response_data['errorMessage'] ?? 'Unknown error.')]);
    }
}

function handleMpesaCallback() {
    // This function processes the callback from Safaricom Daraja.
    // It is called by M-Pesa, not your frontend.
    $callbackData = file_get_contents('php://input');
    $decodedCallbackData = json_decode($callbackData, true);

    // Log the callback data for debugging
    error_log("M-Pesa Callback Received: " . json_encode($decodedCallbackData));

    // Process the callback data
    // Example: Check ResultCode, MerchantRequestID, CheckoutRequestID, and retrieve MpesaReceiptNumber, Amount, PhoneNumber
    if (isset($decodedCallbackData['Body']['stkCallback']['ResultCode'])) {
        $resultCode = $decodedCallbackData['Body']['stkCallback']['ResultCode'];
        $checkoutRequestId = $decodedCallbackData['Body']['stkCallback']['CheckoutRequestID'];
        $merchantRequestId = $decodedCallbackData['Body']['stkCallback']['MerchantRequestID'];

        $conn = getDbConnection();

        if ($resultCode == '0') {
            // Payment was successful
            $amount = $decodedCallbackData['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'];
            $mpesaReceiptNumber = $decodedCallbackData['Body']['stkCallback']['CallbackMetadata']['Item'][1]['Value'];
            $transactionDate = $decodedCallbackData['Body']['stkCallback']['CallbackMetadata']['Item'][3]['Value']; // YYYYMMDDHHmmss
            $phoneNumber = $decodedCallbackData['Body']['stkCallback']['CallbackMetadata']['Item'][4]['Value'];

            // IMPORTANT: Update your database:
            // 1. Find the order linked by AccountReference or CheckoutRequestID
            // 2. Update order status to 'paid'/'confirmed'
            // 3. Store MpesaReceiptNumber, Amount, and other relevant payment details in a 'payments' table.
            // 4. Potentially trigger driver assignment logic
            $sql = "UPDATE orders SET status = 'confirmed', payment_status = 'paid' WHERE checkout_request_id = ?";
            // Execute this update using prepared statements

            // Example: Insert into payments table
            $sql_payment = "INSERT INTO payments (order_id, mpesa_receipt_number, amount, phone_number, transaction_date, checkout_request_id, status) VALUES (?, ?, ?, ?, ?, ?, ?)";
            // Execute this insert

            error_log("M-Pesa Payment CONFIRMED for CheckoutRequestID: $checkoutRequestId");
        } else {
            // Payment failed or was cancelled
            $resultDesc = $decodedCallbackData['Body']['stkCallback']['ResultDesc'] ?? 'Payment cancelled/failed.';
            // Update order status to 'payment_failed' or 'cancelled' in your database
            error_log("M-Pesa Payment FAILED for CheckoutRequestID: $checkoutRequestId. Reason: $resultDesc");
        }
        $conn->close();
    }

    // Always respond with a success status to M-Pesa API to confirm receipt of callback
    echo json_encode(['ResultCode' => 0, 'ResultDesc' => 'Callback received successfully']);
    exit(); // Terminate to prevent further script execution for callback
}
?>