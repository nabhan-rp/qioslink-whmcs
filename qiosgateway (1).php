<?php
// =============================================================================
// QIOSGATEWAY QRIS MODULE FOR WHMCS 8.x
// =============================================================================
// INSTRUKSI:
// 1. Rename file ini menjadi: qiosgateway.php
// 2. Upload ke folder: /modules/gateways/
// =============================================================================

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

// Meta Data Modul
function qiosgateway_MetaData()
{
    return array(
        'DisplayName' => 'QiosGateway QRIS (Nobu)',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

// Konfigurasi Modul di Halaman Admin
function qiosgateway_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'QiosGateway QRIS (Nobu)',
        ),
        'apiUrl' => array(
            'FriendlyName' => 'API URL',
            'Type' => 'text',
            'Size' => '60',
            'Default' => 'https://bayar.jajanan.online/api/create_payment.php',
            'Description' => 'URL lengkap ke file create_payment.php',
        ),
        'merchantId' => array(
            'FriendlyName' => 'Merchant ID',
            'Type' => 'text',
            'Size' => '20',
            'Description' => 'ID User dari Dashboard QiosLink',
        ),
        'apiKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '40',
            'Description' => 'App Secret Key dari menu Settings',
        ),
    );
}

// Fungsi untuk Menampilkan Tombol/QR di Invoice
function qiosgateway_link($params)
{
    // 1. Ambil Parameter Konfigurasi
    $apiUrl = $params['apiUrl'];
    $merchantId = $params['merchantId'];
    $apiKey = $params['apiKey'];

    // 2. Ambil Parameter Invoice
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // 3. Buat URL Callback Otomatis
    $systemUrl = rtrim($params['systemurl'], '/');
    $callbackUrl = $systemUrl . '/modules/gateways/callback/qiosgateway.php';

    // 4. Siapkan Data JSON
    $payload = [
        'merchant_id' => $merchantId,
        'api_key' => $apiKey,
        'amount' => $amount,
        'description' => 'Invoice #' . $invoiceId,
        'external_id' => $invoiceId,
        'callback_url' => $callbackUrl,
        'expiry_minutes' => 60 // Expired dalam 60 menit
    ];

    // 5. Cek cURL
    if (!function_exists('curl_init')) {
        return '<div class="alert alert-danger">Error: cURL PHP extension required.</div>';
    }

    // 6. Eksekusi Request ke API QiosLink
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); // Bypass SSL jika perlu
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return '<div class="alert alert-danger">Connection Failed: '.$curlError.'</div>';
    }

    $json = json_decode($response);

    // 7. Tampilkan Hasil
    if (isset($json->success) && $json->success === true) {
        $qrString = $json->qr_string;
        // Gunakan API Public untuk render QR Code
        $imgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=" . urlencode($qrString);
        
        $html = '
        <div style="background-color:#f8f9fa; border:1px solid #e9ecef; border-radius:8px; padding:20px; text-align:center; max-width:400px; margin:0 auto;">
            <h4 style="margin-top:0; color:#333; font-weight:bold;">Scan QRIS Nobu</h4>
            <div style="background:white; padding:10px; border:1px solid #ddd; display:inline-block; border-radius:4px;">
                <img src="'.$imgUrl.'" alt="Scan QRIS" style="width:100%; max-width:200px; height:auto; display:block;">
            </div>
            <div style="margin-top:15px; font-size:18px; font-weight:bold; color:#2c3e50;">
                Rp '.number_format($amount, 0, ',', '.').'
            </div>
            <p style="font-size:13px; color:#666; margin-bottom:0;">Otomatis lunas setelah pembayaran berhasil.</p>
            <div style="margin-top:10px; font-size:12px; color:#999;">Refresh halaman jika status belum berubah.</div>
        </div>
        <script>
            // Auto Reload setiap 15 detik untuk cek status
            setTimeout(function(){ window.location.reload(); }, 15000);
        </script>
        ';
        return $html;
    } else {
        $errMsg = isset($json->message) ? $json->message : 'Unknown API Error';
        return '<div class="alert alert-warning">Gagal Membuat QRIS: '.$errMsg.'<br><small>Cek Merchant ID & Secret Key Anda.</small></div>';
    }
}
?>