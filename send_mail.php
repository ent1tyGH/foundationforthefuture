<?php
$receiving_email_address = 'foundationforthefutureph@gmail.com'; 
$redirect_url = 'About Us.html'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = filter_var($_POST['Name'] ?? '', FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['Email'] ?? '', FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['Subject'] ?? '', FILTER_SANITIZE_STRING);
    $message = filter_var($_POST['Message'] ?? '', FILTER_SANITIZE_STRING);

    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        header("Location: $redirect_url?status=error&message=All%20fields%20are%20required.");
        exit;
    }

    $email_subject = "NEW WEBSITE MESSAGE: " . $subject;
    $email_body = "You have received a new message from your website contact form.\n\n";
    $email_body .= "Name: " . $name . "\n";
    $email_body .= "Email: " . $email . "\n";
    $email_body .= "Message:\n" . $message . "\n";

    $headers = "From: Website Contact Form <no-reply@yourdomain.com>\r\n";
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    if (mail($receiving_email_address, $email_subject, $email_body, $headers)) {
    $custom_success_message = "Thank you for your feedback! Your message has been successfully submitted and we will review it shortly. We appreciate you reaching out to us.";
    header("Location: $redirect_url?status=success&message=" . urlencode($custom_success_message));
    } else {
        header("Location: $redirect_url?status=error&message=Server%20Error:%20Could%20not%20send%20email.");
    }
    
} else {
    header("Location: $redirect_url");
}
exit;
?>