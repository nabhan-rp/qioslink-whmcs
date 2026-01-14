<?php
// =============================================================================
// QIOSGATEWAY CALLBACK HANDLER
// =============================================================================
// INSTRUKSI:
// 1. Rename file ini menjadi: qiosgateway.php
// 2. Upload ke folder: /modules/gateways/callback/
// =============================================================================

// Load WHMCS Libraries
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Deteksi Nama Modul (Otomatis ambil nama file: qiosgateway)
$gatewayModuleName = basename(__FILE__, '.php');

// Ambil Konfigurasi Gateway
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Pastikan Modul Aktif
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

// --- AMBIL DATA DARI POST REQUEST ---
// QiosLink mengirimkan data dalam format POST standard via cURL
$status = isset($_POST['status']) ? $_POST['status'] : '';
$invoiceId = isset($_POST['external_id']) ? $_POST['external_id'] : '';
$transId = isset($_POST['trx_id']) ? $_POST['trx_id'] : '';
$amount = isset($_POST['amount']) ? $_POST['amount'] : 0;
$fee = 0;

// Validasi Invoice ID
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

// Cek Apakah Transaksi Sudah Pernah Masuk (Mencegah Double Payment)
checkCbTransID($transId); 

// Log Transaksi di Admin WHMCS -> Billing -> Gateway Log
logTransaction($gatewayParams['name'], $_POST, $status);

if ($status == 'paid' || $status == 'success') {
    // Tambahkan Pembayaran ke Invoice
    addInvoicePayment(
        $invoiceId,
        $transId,
        $amount,
        $fee,
        $gatewayModuleName
    );
    echo "OK - Payment Added";
} else {
    echo "Status Not Paid";
}
?>