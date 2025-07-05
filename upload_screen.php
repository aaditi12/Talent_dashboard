<?php
require 'authentication.php';
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

$to   = 'aaditimishra9595@gmail.com';
$from_name = $_SESSION['name'] ?? 'Proctor';
$from_email = 'no-reply@yourdomain.com';

if (!empty($_FILES['screen']['tmp_name'])) {
    $file = $_FILES['screen'];
    $subject = 'Screen Recording from ' . $from_name;
    $boundary = md5(uniqid(time(), true));

    // Headers
    $headers  = "MIME-Version: 1.0" . "
";
    $headers .= "From: {$from_name} <{$from_email}>" . "
";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"" . "
";

    // Plain text body
    $body  = "--{$boundary}
";
    $body .= "Content-Type: text/plain; charset=ISO-8859-1
";
    $body .= "Content-Transfer-Encoding: 7bit

";
    $body .= "Please find the attached screen recording." . "
";

    // Attachment part
    $file_data = file_get_contents($file['tmp_name']);
    $base64    = chunk_split(base64_encode($file_data));
    $body     .= "--{$boundary}
";
    $body     .= "Content-Type: video/webm; name=\"{$file['name']}\"
";
    $body     .= "Content-Disposition: attachment; filename=\"{$file['name']}\"
";
    $body     .= "Content-Transfer-Encoding: base64

";
    $body     .= $base64 . "
";
    $body     .= "--{$boundary}--";

    // Send email
    $sent = mail($to, $subject, $body, $headers);

    if ($sent) {
        http_response_code(200);
        echo json_encode(['status' => 'sent']);
    } else {
        http_response_code(500);
        echo json_encode(['status' => 'failed']);
        error_log("Email send failed to {$to}");
    }
} else {
    http_response_code(400);
    echo json_encode(['status' => 'no_file']);
}