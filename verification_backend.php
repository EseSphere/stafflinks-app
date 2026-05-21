<?php
header('Content-Type: application/json');
error_reporting(E_ERROR | E_PARSE);

require_once('dbconnection.php');

function sendSMTPMail($to, $subject, $htmlMessage) {
    $smtpHost = 'stafflinks.co.uk';
    $smtpPort = 465;
    $smtpUser = 'no-reply@stafflinks.co.uk';
    $smtpPass = 'Noreply@121!';
    $fromEmail = 'no-reply@stafflinks.co.uk';
    $fromName = 'StaffLinks Support';

    $socket = fsockopen("ssl://" . $smtpHost, $smtpPort, $errno, $errstr, 30);

    if (!$socket) {
        return false;
    }

    $read = function () use ($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    };

    $write = function ($command) use ($socket) {
        fwrite($socket, $command . "\r\n");
    };

    $serverName = isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost';

    $read();
    $write("EHLO " . $serverName);
    $read();

    $write("AUTH LOGIN");
    $read();

    $write(base64_encode($smtpUser));
    $read();

    $write(base64_encode($smtpPass));
    $read();

    $write("MAIL FROM:<{$fromEmail}>");
    $read();

    $write("RCPT TO:<{$to}>");
    $read();

    $write("DATA");
    $read();

    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "From: {$fromName} <{$fromEmail}>\r\n";
    $headers .= "Reply-To: {$fromEmail}\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: {$subject}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: 8bit\r\n";
    $headers .= "Message-ID: <" . time() . "." . md5($to . $subject) . "@stafflinks.co.uk>\r\n";

    $emailData = $headers . "\r\n" . $htmlMessage . "\r\n.";

    $write($emailData);
    $read();

    $write("QUIT");
    fclose($socket);

    return true;
}

function generatedPasswordAlreadyExists($conn, $plainPassword) {
    $checkStmt = $conn->prepare("
        SELECT user_password 
        FROM tbl_team_account 
        WHERE user_password IS NOT NULL 
        AND user_password != ''
    ");

    $checkStmt->execute();
    $result = $checkStmt->get_result();

    $exists = false;

    while ($row = $result->fetch_assoc()) {
        $storedPassword = $row['user_password'];

        if (password_verify($plainPassword, $storedPassword)) {
            $exists = true;
            break;
        }

        if ($storedPassword === $plainPassword) {
            $exists = true;
            break;
        }
    }

    $checkStmt->close();

    return $exists;
}

function generateUniquePassword($conn) {
    do {
        $password = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        $exists = generatedPasswordAlreadyExists($conn, $password);
    } while ($exists);

    return $password;
}

try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Database connection failed: " . $e->getMessage()
    ]);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$action = isset($input['action']) ? trim($input['action']) : '';
$email = isset($input['email']) ? trim($input['email']) : '';

if (!$email) {
    echo json_encode([
        "success" => false,
        "message" => "Email is required."
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        "success" => false,
        "message" => "Invalid email address."
    ]);
    exit;
}

if ($action !== "send_generated_passcode") {
    echo json_encode([
        "success" => false,
        "message" => "Invalid action."
    ]);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT id, user_email_address FROM tbl_team_account WHERE user_email_address = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows <= 0) {
        echo json_encode([
            "success" => false,
            "message" => "Account not found."
        ]);
        $stmt->close();
        $conn->close();
        exit;
    }

    $stmt->close();

    $generatedPassword = generateUniquePassword($conn);
    $hashedPassword = password_hash($generatedPassword, PASSWORD_DEFAULT);

    $updateStmt = $conn->prepare("
        UPDATE tbl_team_account 
        SET user_password = ? 
        WHERE user_email_address = ?
    ");

    $updateStmt->bind_param("ss", $hashedPassword, $email);

    if (!$updateStmt->execute()) {
        echo json_encode([
            "success" => false,
            "message" => "Unable to save generated passcode."
        ]);

        $updateStmt->close();
        $conn->close();
        exit;
    }

    $updateStmt->close();

    $safePassword = htmlspecialchars($generatedPassword, ENT_QUOTES, 'UTF-8');

    $subject = "Your StaffLinks Login Passcode";

    $message = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your StaffLinks Passcode</title>
</head>
<body style="margin:0; padding:0; background-color:#f4f6f8; font-family:Arial, Helvetica, sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f6f8; padding:30px 0;">
        <tr>
            <td align="center">
                <table width="100%" cellpadding="0" cellspacing="0" style="max-width:600px; background-color:#ffffff; border-radius:14px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background:linear-gradient(90deg,#c94a57,#e88a3d); padding:28px 30px; text-align:center;">
                            <h1 style="margin:0; color:#ffffff; font-size:24px; font-weight:700;">
                                Account Verification Successful
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:35px 30px; color:#333333;">
                            <p style="margin:0 0 16px; font-size:16px; line-height:1.6;">
                                Hello,
                            </p>

                            <p style="margin:0 0 22px; font-size:16px; line-height:1.6;">
                                Your StaffLinks account has been verified successfully.
                                Please use the secure passcode below to login to your account.
                            </p>

                            <div style="margin:30px 0; text-align:center;">
                                <div style="display:inline-block; background-color:#f7faff; border:1px solid #e3e8ef; border-radius:12px; padding:18px 32px;">
                                    <div style="font-size:13px; color:#6c757d; margin-bottom:8px; text-transform:uppercase; letter-spacing:1px;">
                                        Login Passcode
                                    </div>

                                    <div style="font-size:34px; font-weight:800; letter-spacing:8px; color:#c94a57;">
                                        ' . $safePassword . '
                                    </div>
                                </div>
                            </div>

                            <p style="margin:0 0 16px; font-size:15px; line-height:1.6; color:#555555;">
                                For security reasons, please keep this passcode private and do not share it with anyone.
                            </p>

                            <p style="margin:28px 0 0; font-size:15px; line-height:1.6;">
                                Regards,<br>
                                <strong>StaffLinks Support Team</strong>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#f7faff; padding:18px 30px; text-align:center; color:#6c757d; font-size:12px; line-height:1.5;">
                            This is an automated message from StaffLinks. Please do not reply to this email.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

    $mailSent = sendSMTPMail($email, $subject, $message);

    if (!$mailSent) {
        echo json_encode([
            "success" => false,
            "message" => "Passcode generated successfully, but email could not be sent."
        ]);

        $conn->close();
        exit;
    }

    echo json_encode([
        "success" => true,
        "message" => "Unique login passcode generated and sent successfully."
    ]);

    $conn->close();
    exit;

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Server error: " . $e->getMessage()
    ]);
    exit;
}
?>