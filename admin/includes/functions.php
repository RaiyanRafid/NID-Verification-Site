<?php
require_once(__DIR__ . '/../../config/database.php');

/**
 * Log an admin action
 * 
 * @param int $user_id The ID of the admin performing the action
 * @param string $action The type of action (LOGIN, VERIFY, REJECT, UPDATE, etc.)
 * @param string $description Description of the action
 * @return bool Whether the logging was successful
 */
function log_admin_action($user_id, $action, $description) {
    global $conn;
    
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    
    $stmt = $conn->prepare("INSERT INTO audit_log (user_id, action, description, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if (!$stmt) {
        error_log("Failed to prepare audit log statement: " . $conn->error);
        return false;
    }
    
    $stmt->bind_param("issss", $user_id, $action, $description, $ip_address, $user_agent);
    $success = $stmt->execute();
    
    if (!$success) {
        error_log("Failed to log admin action: " . $stmt->error);
    }
    
    return $success;
}

/**
 * Get admin details by ID
 * 
 * @param int $admin_id The ID of the admin
 * @return array|null Admin details or null if not found
 */
function get_admin_details($admin_id) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT id, username, first_name, last_name, email, phone, created_at FROM admins WHERE id = ?");
    if (!$stmt) {
        return null;
    }
    
    $stmt->bind_param("i", $admin_id);
    if (!$stmt->execute()) {
        return null;
    }
    
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Check if admin has specific permission
 * 
 * @param int $admin_id The ID of the admin
 * @param string $permission The permission to check
 * @return bool Whether the admin has the permission
 */
function admin_has_permission($admin_id, $permission) {
    global $conn;
    
    $stmt = $conn->prepare("
        SELECT permissions 
        FROM admin_roles ar 
        JOIN admins a ON ar.role_id = a.role_id 
        WHERE a.id = ?
    ");
    
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if (!$row) {
        return false;
    }
    
    $permissions = json_decode($row['permissions'], true);
    return in_array($permission, $permissions);
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Desired format (default: 'Y-m-d H:i:s')
 * @return string Formatted date
 */
function format_date($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

/**
 * Get status badge class
 * 
 * @param string $status Status value
 * @return string CSS class for the badge
 */
function get_status_badge_class($status) {
    switch ($status) {
        case 'Pending':
            return 'bg-warning text-dark';
        case 'Verified':
            return 'bg-success';
        case 'Rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

/**
 * Get document type badge class
 * 
 * @param string $type Document type
 * @return string CSS class for the badge
 */
function get_document_badge_class($type) {
    switch($type) {
        case 'nid': return 'bg-info';
        case 'passport': return 'bg-primary';
        case 'birth': return 'bg-success';
        default: return 'bg-secondary';
    }
}

/**
 * Sanitize output for HTML display
 * 
 * @param string $text Text to sanitize
 * @return string Sanitized text
 */
function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Generate pagination links
 * 
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $url_pattern URL pattern with :page placeholder
 * @return string HTML for pagination links
 */
function generate_pagination($current_page, $total_pages, $url_pattern) {
    if ($total_pages <= 1) return '';
    
    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
    
    // Previous button
    if ($current_page > 1) {
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="%s">&laquo;</a></li>',
            str_replace('{page}', $current_page - 1, $url_pattern)
        );
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
    }
    
    // Page numbers
    for ($i = max(1, $current_page - 2); $i <= min($total_pages, $current_page + 2); $i++) {
        if ($i == $current_page) {
            $html .= sprintf('<li class="page-item active"><span class="page-link">%d</span></li>', $i);
        } else {
            $html .= sprintf(
                '<li class="page-item"><a class="page-link" href="%s">%d</a></li>',
                str_replace('{page}', $i, $url_pattern),
                $i
            );
        }
    }
    
    // Next button
    if ($current_page < $total_pages) {
        $html .= sprintf(
            '<li class="page-item"><a class="page-link" href="%s">&raquo;</a></li>',
            str_replace('{page}', $current_page + 1, $url_pattern)
        );
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
    }
    
    $html .= '</ul></nav>';
    return $html;
}

/**
 * Format file size for display
 * 
 * @param int $bytes Size in bytes
 * @return string Formatted size
 */
function format_file_size($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    return round($bytes / pow(1024, $pow), 2) . ' ' . $units[$pow];
}

/**
 * Check if a string is a valid date
 * 
 * @param string $date Date string
 * @param string $format Expected format
 * @return bool Whether the string is a valid date
 */
function is_valid_date($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Generate a random string
 * 
 * @param int $length Length of the string
 * @return string Random string
 */
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * Send email notification
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message Email message
 * @return bool Whether the email was sent
 */
function send_notification_email($to, $subject, $message) {
    $headers = [
        'From' => 'ID Verification System <noreply@example.com>',
        'Reply-To' => 'noreply@example.com',
        'X-Mailer' => 'PHP/' . phpversion(),
        'MIME-Version' => '1.0',
        'Content-Type' => 'text/html; charset=UTF-8'
    ];
    
    return mail($to, $subject, $message, $headers);
}

/**
 * Log system error
 * 
 * @param string $message Error message
 * @param array $context Additional context
 * @return bool Whether the error was logged
 */
function log_error($message, $context = []) {
    $log_file = __DIR__ . '/../../logs/error.log';
    $timestamp = date('Y-m-d H:i:s');
    $context_str = !empty($context) ? json_encode($context) : '';
    
    $log_entry = sprintf(
        "[%s] %s %s\n",
        $timestamp,
        $message,
        $context_str
    );
    
    return file_put_contents($log_file, $log_entry, FILE_APPEND) !== false;
}

/**
 * Send an email using SMTP with HTML support
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $message HTML message content
 * @param string $plain_message Plain text version of the message
 * @return bool Whether the email was sent successfully
 */
function send_smtp_email($to, $subject, $message, $plain_message = '') {
    global $conn;
    
    try {
        // Get active SMTP settings
        $stmt = $conn->prepare("SELECT * FROM smtp_settings WHERE is_active = 1 LIMIT 1");
        if (!$stmt || !$stmt->execute()) {
            throw new Exception("Failed to get SMTP settings");
        }
        
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            throw new Exception("No active SMTP settings found");
        }
        
        $smtp = $result->fetch_assoc();
        
        // Initialize PHPMailer
        require_once __DIR__ . '/../../vendor/autoload.php';
        require_once __DIR__ . '/email_templates.php';
        
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host = $smtp['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $smtp['username'];
        $mail->Password = $smtp['password'];
        $mail->Port = $smtp['port'];
        
        // Set encryption
        switch($smtp['encryption']) {
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
        
        // Recipients
        $mail->setFrom($smtp['from_email'], $smtp['from_name']);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Build complete HTML message with header and footer
        $mail->Body = build_email_message($message);
        
        // Set plain text version if provided, or strip tags from HTML
        $mail->AltBody = $plain_message ?: strip_tags($message);
        
        return $mail->send();
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Send form submission confirmation email to user
 */
function send_submission_confirmation_email($customer_data) {
    try {
        $subject = "ID Verification Request Received";
        
        $content = '
        <h2>Thank You for Your Submission</h2>
        <p>We have received your ID verification request. Our team will review it shortly.</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Your Submission Details:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Name:</strong></td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['middle_name'] . ' ' . $customer_data['last_name']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Email:</strong></td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($customer_data['email']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Phone:</strong></td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($customer_data['phone']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Document Type:</strong></td>
                    <td style="padding: 8px 0;">' . strtoupper(htmlspecialchars($customer_data['document_type'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Status:</strong></td>
                    <td style="padding: 8px 0;">' . get_status_badge_html('Pending') . '</td>
                </tr>
            </table>
        </div>
        
        <p>We will notify you once our team reviews your submission.</p>';

        return send_smtp_email($customer_data['email'], $subject, $content);
    } catch (Exception $e) {
        log_error('Failed to send submission confirmation email', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Send status update notification to user
 */
function send_status_update_email($customer_data) {
    try {
        $subject = "ID Verification Status Update";
        $status = $customer_data['status'];
        
        $content = '
        <h2>Your ID Verification Status Has Been Updated</h2>
        <p>Your verification request has been reviewed and the status has been updated.</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Current Status: ' . get_status_badge_html($status) . '</h3>';
        
        if ($status == 'Rejected' && !empty($customer_data['rejection_reason'])) {
            $content .= '
            <div style="margin-top: 15px;">
                <strong>Reason for Rejection:</strong><br>
                <p style="margin-top: 5px;">' . htmlspecialchars($customer_data['rejection_reason']) . '</p>
            </div>';
        }
        
        $content .= '</div>';
        
        if ($status == 'Verified') {
            $content .= '<p>Congratulations! Your ID has been verified successfully.</p>';
        } elseif ($status == 'Rejected') {
            $content .= '<p>You may submit a new verification request with the correct documentation.</p>';
        }

        return send_smtp_email($customer_data['email'], $subject, $content);
    } catch (Exception $e) {
        log_error('Failed to send status update email', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Notify all admins about new submission
 */
function notify_admins_new_submission($customer_data) {
    try {
        global $conn;
        
        $stmt = $conn->prepare("SELECT email FROM admins WHERE 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $subject = "New ID Verification Request";
        
        $content = '
        <h2>New ID Verification Request Received</h2>
        <p>A new verification request has been submitted and requires review.</p>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 12px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Request Details:</h3>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Name:</strong></td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($customer_data['first_name'] . ' ' . $customer_data['middle_name'] . ' ' . $customer_data['last_name']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Email:</strong></td>
                    <td style="padding: 8px 0;">' . htmlspecialchars($customer_data['email']) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Document Type:</strong></td>
                    <td style="padding: 8px 0;">' . strtoupper(htmlspecialchars($customer_data['document_type'])) . '</td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Submission Time:</strong></td>
                    <td style="padding: 8px 0;">' . date('Y-m-d H:i:s') . '</td>
                </tr>
            </table>
        </div>
        
        <a href="' . get_base_url() . '/admin/dashboard.php" style="display: inline-block; padding: 12px 24px; background: var(--primary); color: white; text-decoration: none; border-radius: 6px; margin-top: 15px;">Review Request</a>';

        while ($admin = $result->fetch_assoc()) {
            send_smtp_email($admin['email'], $subject, $content);
        }
        
        return true;
    } catch (Exception $e) {
        log_error('Failed to notify admins about new submission', ['error' => $e->getMessage()]);
        return false;
    }
}

/**
 * Get base URL of the application
 */
function get_base_url() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $path = dirname($_SERVER['PHP_SELF']);
    return rtrim($protocol . $host . $path, '/');
} 