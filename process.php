<?php
require_once 'config/database.php';

header('Content-Type: application/json');

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to generate unique ID
function generate_unique_id() {
    return uniqid('CUS_', true);
}

// Function to handle file upload
function handle_file_upload($file, $unique_id, $side = '') {
    $allowed = ['png' => 'image/png', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg'];
    $filename = $file['name'];
    $filetype = $file['type'];
    $filesize = $file['size'];

    // Verify file extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    if (!array_key_exists($ext, $allowed)) {
        throw new Exception("Invalid file format for " . ($side ? $side : 'document'));
    }

    // Verify MIME type
    if (!in_array($filetype, $allowed)) {
        throw new Exception("Invalid file type for " . ($side ? $side : 'document'));
    }

    // Verify file size - 25MB maximum
    $maxsize = 25 * 1024 * 1024;
    if ($filesize > $maxsize) {
        throw new Exception("File size must be less than 25MB for " . ($side ? $side : 'document'));
    }

    // Create filename with side indicator if provided
    $new_filename = $unique_id . ($side ? '_' . $side : '') . '.' . $ext;
    $upload_path = 'uploads/';

    // Create upload directory if it doesn't exist
    if (!file_exists($upload_path)) {
        mkdir($upload_path, 0777, true);
    }

    // Save the file
    if (!move_uploaded_file($file['tmp_name'], $upload_path . $new_filename)) {
        throw new Exception("Failed to upload " . ($side ? $side : 'document'));
    }

    return $upload_path . $new_filename;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validate and sanitize input
        $firstName = sanitize_input($_POST['firstName']);
        $middleName = isset($_POST['middleName']) ? sanitize_input($_POST['middleName']) : '';
        $lastName = sanitize_input($_POST['lastName']);
        $address = sanitize_input($_POST['address']);
        $phone = sanitize_input($_POST['phone']);
        $email = sanitize_input($_POST['email']);
        $documentType = sanitize_input($_POST['documentType']);

        // Validate document type
        if (!in_array($documentType, ['nid', 'passport', 'birth'])) {
            throw new Exception("Invalid document type");
        }

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        $unique_id = generate_unique_id();
        $document_paths = [];

        // Handle file uploads based on document type
        switch ($documentType) {
            case 'nid':
                if (!isset($_FILES['documentFront']) || $_FILES['documentFront']['error'] !== 0) {
                    throw new Exception("NID front side is required");
                }
                if (!isset($_FILES['documentBack']) || $_FILES['documentBack']['error'] !== 0) {
                    throw new Exception("NID back side is required");
                }
                $document_paths['front'] = handle_file_upload($_FILES['documentFront'], $unique_id, 'front');
                $document_paths['back'] = handle_file_upload($_FILES['documentBack'], $unique_id, 'back');
                break;

            case 'passport':
                if (!isset($_FILES['documentPassport']) || $_FILES['documentPassport']['error'] !== 0) {
                    throw new Exception("Passport front page is required");
                }
                $document_paths['front'] = handle_file_upload($_FILES['documentPassport'], $unique_id, 'front');
                break;

            case 'birth':
                if (!isset($_FILES['documentBirth']) || $_FILES['documentBirth']['error'] !== 0) {
                    throw new Exception("Birth certificate is required");
                }
                $document_paths['front'] = handle_file_upload($_FILES['documentBirth'], $unique_id, 'front');
                break;
        }

        // Insert into database
        $stmt = $conn->prepare("
            INSERT INTO customers (
                first_name, middle_name, last_name, address, phone, email, 
                document_type, document_path, document_path_back
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $document_path = $document_paths['front'];
        $document_path_back = $documentType === 'nid' ? $document_paths['back'] : null;

        $stmt->bind_param(
            "sssssssss",
            $firstName, $middleName, $lastName, $address, $phone, $email,
            $documentType, $document_path, $document_path_back
        );

        if ($stmt->execute()) {
            // Get the customer data for email
            $customer_id = $stmt->insert_id;
            $customer_data = [
                'id' => $customer_id,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'document_type' => $documentType,
                'status' => 'Pending'
            ];

            // Send confirmation email to user
            send_submission_confirmation_email($customer_data);

            // Notify all admins about new submission
            notify_admins_new_submission($customer_data);

            // Log the action
            $log_stmt = $conn->prepare("INSERT INTO audit_log (action, description) VALUES (?, ?)");
            $action = "NEW_SUBMISSION";
            $description = "New customer verification submitted - ID: " . $customer_id . " (Document Type: " . strtoupper($documentType) . ")";
            $log_stmt->bind_param("ss", $action, $description);
            $log_stmt->execute();

            echo json_encode(['success' => true, 'message' => 'Form submitted successfully']);
        } else {
            throw new Exception("Database error");
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?> 