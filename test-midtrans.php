<?php
require __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$serverKey = $_ENV['MIDTRANS_SERVER_KEY'];
$isProduction = $_ENV['MIDTRANS_IS_PRODUCTION'] === 'true';

\Midtrans\Config::$serverKey = $serverKey;
\Midtrans\Config::$isProduction = $isProduction;
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

$params = [
    'transaction_details' => [
        'order_id' => 'TEST-' . time(),
        'gross_amount' => 10000,
    ],
    'customer_details' => [
        'first_name' => 'Budi',
        'email' => 'budi@example.com',
    ],
    'callbacks' => [
        'finish' => 'http://localhost:8000/tiket/TESTCODE'
    ]
];

try {
    $snapToken = \Midtrans\Snap::createTransaction($params)->redirect_url;
    echo "SUCCESS: " . $snapToken . "\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
