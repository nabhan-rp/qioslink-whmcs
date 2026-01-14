<?php
// =============================================================================
// QIOSGATEWAY WHMCS 8.x MODULE (VERSI KODE UNIK)
// =============================================================================
// INSTRUKSI:
// 1. Rename file ini menjadi: qiosgateway.php
// 2. Upload ke hosting WHMCS di folder: /modules/gateways/ (Timpa file lama)
// =============================================================================

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function qiosgateway_MetaData()
{
    return array(
        'DisplayName' => 'QiosGateway QRIS (Nobu)',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

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

function qiosgateway_link($params)
{
    // Parameter
    $apiUrl = $params['apiUrl'];
    $merchantId = $params['merchantId'];
    $apiKey = $params['apiKey'];
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount']; // Nominal Invoice Asli (Misal 50000)

    // URL Callback
    $systemUrl = rtrim($params['systemurl'], '/');
    $callbackUrl = $systemUrl . '/modules/gateways/callback/qiosgateway.php';

    // Payload
    $payload = [
        'merchant_id' => $merchantId,
        'api_key' => $apiKey,
        'amount' => $amount,
        'description' => 'Invoice #' . $invoiceId,
        'external_id' => $invoiceId,
        'callback_url' => $callbackUrl,
        'expiry_minutes' => 60
    ];

    // Request ke API
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        return '<div class="alert alert-danger">Connection Failed: '.$curlError.'</div>';
    }

    $json = json_decode($response);

    if (isset($json->success) && $json->success === true) {
        $qrString = $json->qr_string;
        // PENTING: Ambil 'amount' dari respon JSON API, bukan dari $params WHMCS
        // Karena API sudah menambahkan Kode Unik (Misal: 50123)
        $finalAmount = isset($json->amount) ? $json->amount : $amount; 
        
        $imgUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&data=" . urlencode($qrString);
        
        $html = '
        <div style="background-color:#fff; border:1px solid #ddd; border-radius:8px; padding:20px; text-align:center; max-width:400px; margin:10px auto; box-shadow:0 2px 4px rgba(0,0,0,0.05);">
            <h4 style="margin-top:0; color:#333; font-weight:bold;">Scan QRIS Nobu</h4>
            
            <div style="background:white; padding:5px; border:1px solid #eee; display:inline-block; border-radius:4px; margin-bottom:10px;">
                <img src="'.$imgUrl.'" alt="Scan QRIS" style="width:100%; max-width:220px; height:auto; display:block;">
            </div>
            
            <div style="background-color:#e0f2fe; color:#0369a1; padding:10px; border-radius:6px; margin-bottom:10px;">
                <span style="display:block; font-size:12px;">Total Bayar (Termasuk Kode Unik):</span>
                <span style="display:block; font-size:24px; font-weight:bold; letter-spacing:1px;">Rp '.number_format($finalAmount, 0, ',', '.').'</span>
                <span style="display:block; font-size:11px; color:#ef4444; margin-top:4px;">*MOHON TRANSFER TEPAT SAMPAI 3 DIGIT TERAKHIR</span>
            </div>
            
            <div style="font-size:12px; color:#666;">
                Status pembayaran akan terdeteksi otomatis dalam 1-2 menit.
            </div>
            <div style="margin-top:15px;">
                <button onclick="window.location.reload();" class="btn btn-sm btn-default"><i class="fas fa-sync"></i> Cek Status Pembayaran</button>
            </div>
        </div>
        <script>
            setTimeout(function(){ window.location.reload(); }, 15000);
        </script>
        ';
        return $html;
    } else {
        $errMsg = isset($json->message) ? $json->message : 'Unknown API Error';
        return '<div class="alert alert-warning">Gagal Membuat QRIS: '.$errMsg.'</div>';
    }
}
?>
