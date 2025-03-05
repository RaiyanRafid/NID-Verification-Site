<?php
// Start output buffering
ob_start();

session_start();
require_once '../../config/database.php';
require_once '../includes/functions.php';

// Clear any previous output
if (ob_get_length()) ob_clean();

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Prevent any unwanted output
error_reporting(0);
ini_set('display_errors', 0);

// Function to send JSON response
function sendJsonResponse($success, $message = '', $data = null) {
    // Clear any previous output
    if (ob_get_length()) ob_clean();
    
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Error handler to catch any PHP errors
function errorHandler($errno, $errstr, $errfile, $errline) {
    sendJsonResponse(false, "PHP Error: $errstr");
    return true;
}
set_error_handler("errorHandler");

// Exception handler
function exceptionHandler($e) {
    sendJsonResponse(false, "Exception: " . $e->getMessage());
}
set_exception_handler("exceptionHandler");

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    sendJsonResponse(false, 'Unauthorized access');
}

// Validate input
$required_fields = ['host', 'port', 'username', 'password', 'from_email', 'from_name', 'encryption', 'test_email'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        sendJsonResponse(false, "Missing required field: $field");
    }
}

try {
    // Initialize PHPMailer
    if (!file_exists('../../vendor/autoload.php')) {
        // Temporary fallback path
        require_once '../../includes/PHPMailer/src/Exception.php';
        require_once '../../includes/PHPMailer/src/PHPMailer.php';
        require_once '../../includes/PHPMailer/src/SMTP.php';
    } else {
        require_once '../../vendor/autoload.php';
    }
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    // Basic settings
    $mail->CharSet = 'UTF-8';
    $mail->Encoding = 'base64';
    $mail->XMailer = ' '; // Disable X-Mailer header

    // Server settings
    $mail->isSMTP();
    $mail->Host = trim($_POST['host']);
    $mail->SMTPAuth = true;
    $mail->Username = trim($_POST['username']);
    $mail->Password = trim($_POST['password']);
    $mail->Port = intval($_POST['port']);
    
    // Set encryption
    switch(strtolower(trim($_POST['encryption']))) {
        case 'tls':
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            break;
        case 'ssl':
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
            break;
        default:
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
    }

    // Enable debug mode but capture output
    $mail->SMTPDebug = 3; // More detailed debug output
    $debugOutput = '';
    $mail->Debugoutput = function($str, $level) use (&$debugOutput) {
        $debugOutput .= $str . "\n";
    };

    // Set timeout
    $mail->Timeout = 30;
    $mail->SMTPKeepAlive = false;

    // Recipients
    $mail->setFrom(trim($_POST['from_email']), trim($_POST['from_name']));
    $mail->addAddress(trim($_POST['test_email']));

    // Content
    require_once '../includes/email_templates.php';
    
    $content = '
    <h2>SMTP Test Successful!</h2>
    <p>This email confirms that your SMTP settings are working correctly.</p>
    
    <div style="background-color: #e9ecef; padding: 15px; border-radius: 4px; margin: 15px 0;">
        <h3 style="margin-top: 0;">Configuration Details</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 8px 0;"><strong>Host:</strong></td>
                <td style="padding: 8px 0;">' . htmlspecialchars(trim($_POST['host'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Port:</strong></td>
                <td style="padding: 8px 0;">' . htmlspecialchars($_POST['port']) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>Encryption:</strong></td>
                <td style="padding: 8px 0;">' . strtoupper(htmlspecialchars(trim($_POST['encryption']))) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>From Email:</strong></td>
                <td style="padding: 8px 0;">' . htmlspecialchars(trim($_POST['from_email'])) . '</td>
            </tr>
            <tr>
                <td style="padding: 8px 0;"><strong>From Name:</strong></td>
                <td style="padding: 8px 0;">' . htmlspecialchars(trim($_POST['from_name'])) . '</td>
            </tr>
        </table>
    </div>
    
    <p>✅ If you received this email, your SMTP configuration is working properly.</p>
    <p>You can now use these settings to send emails through the system.</p>';

    // Plain text version
    $plain_message = "SMTP Test Successful!\n\n" .
                    "This email confirms that your SMTP settings are working correctly.\n\n" .
                    "Configuration Details:\n" .
                    "- Host: " . trim($_POST['host']) . "\n" .
                    "- Port: " . $_POST['port'] . "\n" .
                    "- Encryption: " . strtoupper(trim($_POST['encryption'])) . "\n" .
                    "- From Email: " . trim($_POST['from_email']) . "\n" .
                    "- From Name: " . trim($_POST['from_name']) . "\n\n" .
                    "If you received this email, your SMTP configuration is working properly.\n" .
                    "You can now use these settings to send emails through the system.";

    $mail->Subject = 'SMTP Test Email';
    $mail->isHTML(true);
    $mail->Body = build_email_message($content);
    $mail->AltBody = $plain_message;

    // Attempt to send the email
    if (!$mail->send()) {
        throw new Exception($mail->ErrorInfo);
    }
    
    // Log the successful test
    log_admin_action(
        $_SESSION['admin_id'],
        'TEST_SMTP',
        'Successfully tested SMTP settings'
    );

    sendJsonResponse(true, 'Test email sent successfully!', [
        'debug' => $debugOutput
    ]);

} catch (Exception $e) {
    // Log the error
    log_error('SMTP Test Failed', [
        'error' => $e->getMessage(),
        'debug_output' => $debugOutput ?? '',
        'smtp_settings' => [
            'host' => $_POST['host'],
            'port' => $_POST['port'],
            'encryption' => $_POST['encryption'],
            'from_email' => $_POST['from_email']
        ]
    ]);

    // Send error response with debug information
    sendJsonResponse(false, 'Failed to send test email: ' . $e->getMessage(), [
        'debug' => $debugOutput ?? 'No debug output available'
    ]);
}

// End output buffering and clean it
if (ob_get_length()) ob_end_clean(); 