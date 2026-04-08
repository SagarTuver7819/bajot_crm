<?php
require_once 'config.php';
require_once 'includes/oceanhub.php';
check_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit();
}

if (!oceanhub_ready()) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'OceanHub credentials not configured']);
    exit();
}

$phone = preg_replace('/[^0-9]/', '', $_POST['phone'] ?? '');
if (strlen($phone) == 10) {
    $phone = "91" . $phone;
}
if (empty($phone)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Invalid phone number']);
    exit();
}

$message = trim($_POST['message'] ?? '');
if ($message === '') {
    $message = 'Invoice PDF';
}

$test_mode = !empty($_POST['test_mode']);
if ($test_mode) {
    // Use a public sample PDF for local testing (no localhost fetch issue)
    $file_url = 'https://www.w3.org/WAI/ER/tests/xhtml/testfiles/resources/pdf/dummy.pdf';
} else {
    if (!isset($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'PDF upload failed']);
        exit();
    }

    $upload_dir = 'assets/invoice_pdfs';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $safe_name = 'invoice_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.pdf';
    $file_path = $upload_dir . '/' . $safe_name;

    if (!move_uploaded_file($_FILES['pdf']['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['ok' => false, 'error' => 'Unable to save PDF']);
        exit();
    }

    $file_url = get_base_url() . $file_path;
}

$sandbox_override = $test_mode ? 'true' : null;
$result = oceanhub_send_message($phone, $message, $file_url, $sandbox_override);

if (!$result['ok']) {
    // Simple log for debugging (no keys)
    $log_dir = 'assets/logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    $log_line = date('Y-m-d H:i:s') .
        " | phone={$phone} | file_url={$file_url} | http=" . ($result['http_code'] ?? 0) .
        " | err=" . ($result['error'] ?? '') .
        " | resp=" . ($result['response'] ?? '') . PHP_EOL;
    @file_put_contents($log_dir . '/oceanhub.log', $log_line, FILE_APPEND);

    $response_text = trim($result['response'] ?? '');
    $error_text = trim($result['error'] ?? '');
    $combined = '';
    if ($response_text !== '') $combined = $response_text;
    if ($error_text !== '') $combined = ($combined !== '' ? ($combined . ' | ') : '') . $error_text;

    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $combined !== '' ? $combined : 'Failed to send message',
        'http_code' => $result['http_code'] ?? 0,
        'response' => $result['response'] ?? '',
        'curl_error' => $result['error'] ?? '',
        'file_url' => $file_url
    ]);
    exit();
}

echo json_encode([
    'ok' => true,
    'file_url' => $file_url,
    'response' => $result['response'] ?? ''
]);
?>
