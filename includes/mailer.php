<?php
// includes/mailer.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Ensure autoload is required (Adjust path if needed based on where this is called)
$autoload_path = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}else{
    require_once 'phpmailer/src/phpmailer.php';
    require_once 'phpmailer/src/smtp.php';
    require_once 'phpmailer/src/exception.php';
    require_once 'phpmailer/src/pop3.php';
    //require_once 'phpmailer/src/imap.php';
    //require_once __DIR__ . 'PHPMailer/POP3.php';
    //require_once __DIR__ . 'PHPMailer/IMAP.php';
    //require_once __DIR__ . 'PHPMailer/Exception.php';
    //require_once __DIR__ . 'PHPMailer/PHPMailer.php';
}

/**
 * Sends a branded HTML email using PHPMailer
 */
function send_hajj_mail($to_email, $to_name, $subject, $message_body) {
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return false; // Silently fail if PHPMailer isn't installed to prevent breaking the app
    }

    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.hostinger.com'; // Replace with your SMTP Host (e.g., mail.yourdomain.com)
        $mail->SMTPAuth   = true;
        $mail->Username   = 'info@abdullateefhajjumrah.com'; // Replace with SMTP Username
        $mail->Password   = 'Science!';    // Replace with SMTP Password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 465;

        // Recipients
        $mail->setFrom('info@abdullateefhajjumrah.com', 'Abdullateef Hajj & Umrah');
        $mail->addAddress($to_email, $to_name);

        // Branded HTML Template
        $htmlTemplate = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #f9fafb; padding: 20px; border-radius: 10px;'>
            <div style='text-align: center; margin-bottom: 20px; background: #1B7D75; padding: 20px; border-radius: 10px 10px 0 0;'>
                <h1 style='color: #ffffff; margin: 0;'>Abdullateef</h1>
                <p style='color: #C8AA00; margin: 5px 0 0 0; font-size: 12px; text-transform: uppercase; letter-spacing: 2px;'>Integrated Hajj & Umrah</p>
            </div>
            <div style='background: #ffffff; padding: 30px; border-radius: 0 0 10px 10px; border: 1px solid #eeeeee;'>
                <h2 style='color: #1B7D75; margin-top: 0;'>As-salamu Alaykum, $to_name,</h2>
                <div style='color: #444444; line-height: 1.6;'>
                    $message_body
                </div>
                <hr style='border: none; border-top: 1px solid #eeeeee; margin: 30px 0;' />
                <p style='font-size: 12px; color: #888888; text-align: center;'>
                    May Allah accept your ibadah.<br>
                    <strong>Abdullateef Support Team</strong><br>
                    <a href='mailto:info@abdullateefhajjumrah.com' style='color: #1B7D75;'>info@abdullateefhajjumrah.com</a>
                </p>
            </div>
        </div>";

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlTemplate;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>'], ["\r\n", "\r\n\r\n"], $message_body));

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Mail Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>