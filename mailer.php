<?php
function sendDualApprovalEmail($toEmail, $toName, $activityName, $date, $hospitalName)
{
    $url = 'https://script.google.com/macros/s/AKfycbx7NChMox05dsyh0AjlHyYjiLKg6kSHWKLLhHbo64Uj5Cmh-LZbmMdbK_aBLS7a8OA1Cw/exec';
    $subject = 'Official Notification: Educational Activity Registration Approved';

    $htmlBody = "
    <!DOCTYPE html>
    <html lang='en' dir='ltr'>
    <head><meta charset='UTF-8'></head>
    <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
        
        <!-- Arabic Section -->
        <div dir='rtl' style='text-align: right; margin-bottom: 20px;'>
            <h3 style='color: #185289;'>تجمع الأحساء الصحي | Alahsa Health Cluster</h3>
            <p>مرحباً <strong>{$toName}</strong>،</p>
            <p>يسعدنا تأكيد تسجيلك وحجز مقعدك في النشاط التعليمي بنجاح. يمكنك الاطلاع على تفاصيل النشاط أدناه.</p>
            <p>نتطلع لرؤيتك!</p>
        </div>

        <hr style='border: none; border-top: 1px solid #ddd; margin: 30px 0;'>

        <!-- English Section -->
        <div dir='ltr' style='text-align: left; margin-bottom: 30px;'>
            <p>Dear <strong>{$toName}</strong>,</p>
            <p>We are pleased to confirm your registration and secure your seat for the educational activity. Please find the details below.</p>
            <p>We look forward to seeing you!</p>
        </div>

        <!-- Event Details -->
        <div dir='ltr' style='background: #f9f9f9; padding: 15px; border-radius: 8px; border-left: 4px solid #185289; text-align: left;'>
            <h4 style='margin-top: 0; color: #185289;'>Educational Activity Details:</h4>
            <p style='margin: 5px 0;'><strong>Educational Activity:</strong> {$activityName}</p>
            <p style='margin: 5px 0;'><strong>Date:</strong> {$date}</p>
            <p style='margin: 5px 0;'><strong>Location:</strong> {$hospitalName}</p>
        </div>
        
    </body>
    </html>
    ";

    $payload = json_encode([
        'toEmail' => $toEmail,
        'subject' => $subject,
        'htmlBody' => $htmlBody
    ]);

    $debug = [];
    $sent = false;

    // Method 1: Try cURL
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: text/plain']);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 25);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $result = curl_exec($ch);
        $debug['curl_result'] = $result;
        $debug['curl_error'] = curl_error($ch);
        $debug['curl_errno'] = curl_errno($ch);
        $debug['curl_status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $debug['method'] = 'curl';

        if ($result && strpos($result, 'success') !== false) {
            $sent = true;
        }
    }

    // Method 2: Fallback to file_get_contents if cURL failed or returned empty
    if (!$sent) {
        $options = [
            'http' => [
                'header'  => "Content-type: text/plain\r\nUser-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n",
                'method'  => 'POST',
                'content' => $payload,
                'follow_location' => 1,
                'max_redirects' => 5,
                'timeout' => 25,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        $debug['stream_result'] = $result;
        $debug['stream_error'] = error_get_last();
        $debug['method'] = $debug['method'] ? $debug['method'] . ' + fallback_stream' : 'stream';

        if ($result && strpos($result, 'success') !== false) {
            $sent = true;
        }
    }

    // Method 3: Fallback to PHP native mail() if outbound connections are blocked by hosting firewall
    if (!$sent) {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: NurSync System <health.nursync@gmail.com>\r\n";
        $headers .= "Reply-To: health.nursync@gmail.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $nativeMailSent = @mail($toEmail, $subject, $htmlBody, $headers);
        $debug['php_mail_sent'] = $nativeMailSent;
        $debug['method'] = $debug['method'] ? $debug['method'] . ' + php_native_mail' : 'php_native_mail';
    }

    $debug['payload_array'] = [
        'toEmail' => $toEmail,
        'subject' => $subject,
        'htmlBody' => $htmlBody
    ];
    $debug['url'] = $url;
    $debug['sent'] = $sent || (!empty($debug['php_mail_sent']));

    return $debug;
}
