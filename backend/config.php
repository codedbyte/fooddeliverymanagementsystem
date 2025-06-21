<?php
$host = 'localhost';
$user = 'root';   
$password = '12345';  
$food_delivery_db = 'food_delivey_db'; 


$ch = curl_init('https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer 2zEX5GC91avBEfEpeAftKxN4FI3C',
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,'{
    "BusinessShortCode": 174379,
    "Password": "MTc0Mzc5YmZiMjc5ZjlhYTliZGJjZjE1OGU5N2RkNzFhNDY3Y2QyZTBjODkzMDU5YjEwZjc4ZTZiNzJhZGExZWQyYzkxOTIwMjUwNjE3MTkxMzQ2",
    "Timestamp": "20250617191346",
    "TransactionType": "CustomerPayBillOnline",
    "Amount": 1,
    "PartyA": 254725633352,
    "PartyB": 174379,
    "PhoneNumber": 254725633352,
    "CallBackURL": "https://mydomain.com/path",
    "AccountReference": "CompanyXLTD",
    "TransactionDesc": "Payment of X" }');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$response     = curl_exec($ch);
curl_close($ch);
echo $response;

define('Maps_API_KEY', 'YOUR_Maps_API_KEY');
define('BASE_URL', 'http://localhost/food-delivery-management-system/');
define('ENVIRONMENT', 'development');
date_default_timezone_set('Africa/Nairobi');
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} elseif (ENVIRONMENT === 'production') {
    error_reporting(0);
    ini_set('display_errors', 0);
}

?>