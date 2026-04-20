<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\QRCodeService;
use Symfony\Component\Dotenv\Dotenv;

$service = new QRCodeService();
try {
    $uri = $service->generateQrCode('https://google.com', 'Test Label');
    echo "SUCCESS: " . substr($uri, 0, 50) . "...\n";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
