<?php
// OceanHub WhatsApp API helper
// Keep credentials here to avoid editing config.php

if (!defined('OCEANHUB_API_URL')) {
    define('OCEANHUB_API_URL', 'https://oc-web.oceanhub.co.in/api/create-message');
}
if (!defined('OCEANHUB_APPKEY')) {
    define('OCEANHUB_APPKEY', 'e4f8e984-9b3e-4a5c-94a8-fc57c0c75489');
}
if (!defined('OCEANHUB_AUTHKEY')) {
    define('OCEANHUB_AUTHKEY', 'oituCXmBGqh1tnEG1IMfjWw7rpGKdeXDBvUX6QcgA3EzO79ywn');
}
if (!defined('OCEANHUB_SANDBOX')) {
    // Set to 'true' only if OceanHub requires sandbox mode
    define('OCEANHUB_SANDBOX', 'false');
}

if (!function_exists('oceanhub_ready')) {
    function oceanhub_ready() {
        return !empty(OCEANHUB_APPKEY) && !empty(OCEANHUB_AUTHKEY);
    }
}

if (!function_exists('get_base_url')) {
    function get_base_url() {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https" : "http";
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $path = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
        return $protocol . "://" . $host . $path . "/";
    }
}

if (!function_exists('oceanhub_send_message')) {
    function oceanhub_send_message($to, $message, $file_url = null, $sandbox = null) {
        $payload = [
            'appkey' => OCEANHUB_APPKEY,
            'authkey' => OCEANHUB_AUTHKEY,
            'to' => $to,
            'message' => $message,
        ];
        if ($sandbox !== null) {
            $payload['sandbox'] = $sandbox;
        } elseif (defined('OCEANHUB_SANDBOX')) {
            $payload['sandbox'] = OCEANHUB_SANDBOX;
        }
        if (!empty($file_url)) {
            $payload['file'] = $file_url;
        }

        $ch = curl_init(OCEANHUB_API_URL);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $err = curl_error($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($err) {
            return ['ok' => false, 'http_code' => $http_code, 'error' => $err, 'response' => $response];
        }
        $ok = $http_code >= 200 && $http_code < 300;
        return ['ok' => $ok, 'http_code' => $http_code, 'response' => $response];
    }
}
?>
