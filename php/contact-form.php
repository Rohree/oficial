<?php
<?php
// Return JSON for the frontend
header('Content-Type: application/json; charset=utf-8');

// Basic POST check
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Invalid request']);
    exit;
}

// Simple sanitization
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? 'Contact form');
$message = trim($_POST['message'] ?? '');
$antispam = trim($_POST['url'] ?? '');

// Anti-spam hidden field must be empty
if ($antispam !== '') {
    echo json_encode(['status'=>'error','message'=>'Spam detected']);
    exit;
}

// Basic validation
if (empty($name) || empty($email) || empty($message) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status'=>'error','message'=>'Please complete the form correctly']);
    exit;
}

/*
 Recommended: use PHPMailer + SMTP.
 Install with composer in project root:
 > composer require phpmailer/phpmailer
*/

require __DIR__ . '/vendor/autoload.php'; // adjust if composer autoload is elsewhere

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

try {
    $mail = new PHPMailer(true);
    // SMTP settings - replace with your provider values
    $mail->isSMTP();
    $mail->Host       = 'smtp.example.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'smtp-user@example.com';
    $mail->Password   = 'smtp-password';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // or PHPMailer::ENCRYPTION_SMTPS
    $mail->Port       = 587; // 465 for SMTPS

    // From should be a verified address on your SMTP server
    $mail->setFrom('no-reply@yourdomain.com', 'Masters Academy');
    $mail->addAddress('recipient@yourdomain.com', 'Site Owner'); // <-- where messages go
    $mail->addReplyTo($email, $name); // so you can reply to the user

    $mail->isHTML(true);
    $mail->Subject = $subject ?: 'New contact form submission';
    $body = "<p><strong>Name:</strong> " . htmlentities($name) . "</p>"
          . "<p><strong>Email:</strong> " . htmlentities($email) . "</p>"
          . "<p><strong>Subject:</strong> " . htmlentities($subject) . "</p>"
          . "<p><strong>Message:</strong><br>" . nl2br(htmlentities($message)) . "</p>";
    $mail->Body = $body;
    $mail->AltBody = strip_tags($name . " - " . $email . "\n\n" . $message);

    $mail->send();
    echo json_encode(['status'=>'ok','message'=>'Message sent']);
} catch (Exception $e) {
    // log $mail->ErrorInfo on server for debugging
    echo json_encode(['status'=>'error','message'=>'Mail error: '.$mail->ErrorInfo]);
}