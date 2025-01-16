<?php
require __DIR__ . 'vendor/autoload.php';
use Dotenv\Dotenv;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Logging function
function log_message($file, $message) {
    $log = "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL;
    file_put_contents(__DIR__ . "logs/$file", $log, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);
    $recaptchaResponse = $_POST['g-recaptcha-response'];
    $secretKey = $_ENV['RECAPTCHA_SECRET_KEY'];

    // Verify reCAPTCHA
    $recaptchaUrl = "https://www.google.com/recaptcha/api/siteverify";
    $recaptchaData = [
        'secret' => $secretKey,
        'response' => $recaptchaResponse,
    ];
    $recaptchaOptions = [
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query($recaptchaData),
        ],
    ];
    $recaptchaContext = stream_context_create($recaptchaOptions);
    $recaptchaResult = file_get_contents($recaptchaUrl, false, $recaptchaContext);
    $recaptchaVerification = json_decode($recaptchaResult);
/*
    if (!$recaptchaVerification->success) {
        log_message("error.log", "reCAPTCHA validation failed.");
        header("Location: /coming_soon/error_page.php");
        exit;
    }
*/
    // Send Email
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'];
        $mail->Port = $_ENV['MAIL_PORT'];

        $mail->setFrom($_ENV['MAIL_FROM'], $_ENV['MAIL_FROM_NAME']);
        $mail->addAddress($_ENV['MAIL_TO']);
        $mail->addReplyTo($email, $name);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = "You have received a new message from $name.\n\nEmail: $email\n\nMessage:\n$message\n";

        $mail->send();
        log_message("success.log", "Email sent successfully from $email.");
        header("Location: thank_you.php");
        exit;
    } catch (Exception $e) {
        log_message("error.log", "Mailer error: " . $mail->ErrorInfo);
        header("Location: error_page.php");
        exit;
    }
} else {
    log_message("error.log", "Invalid request method.");
    header("Location: error_page.php");
    exit;
}
?>
