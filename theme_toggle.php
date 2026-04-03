<?php
session_start();
if (isset($_POST['theme'])) {
    $_SESSION['theme'] = $_POST['theme'];
    echo json_encode(['status' => 'success', 'theme' => $_SESSION['theme']]);
}
?>
