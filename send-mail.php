<?php
header("Content-Type: application/json");

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

// ---- Student Details ----
$studentName = trim($_POST["studentName"] ?? "");
$dob         = trim($_POST["dob"] ?? "");
$gender      = trim($_POST["gender"] ?? "");
$classFor    = trim($_POST["classFor"] ?? "");
$prevSchool  = trim($_POST["prevSchool"] ?? "");

// ---- Parent / Guardian Details ----
$guardianName  = trim($_POST["guardianName"] ?? "");
$guardianPhone = trim($_POST["guardianPhone"] ?? "");
$guardianEmail = trim($_POST["guardianEmail"] ?? "");
$address       = trim($_POST["address"] ?? "");
$notes         = trim($_POST["notes"] ?? "");

// ---- Required field check ----
if (
    empty($studentName) || empty($dob) || empty($gender) || empty($classFor) ||
    empty($guardianName) || empty($guardianPhone) || empty($address)
) {
    echo json_encode(["success" => false, "message" => "Please fill all required fields."]);
    exit;
}

// ---- Optional email validation ----
if (!empty($guardianEmail) && !filter_var($guardianEmail, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email address."]);
    exit;
}

// ---- Phone format validation ----
if (!preg_match('/^[0-9+\-\s]{7,15}$/', $guardianPhone)) {
    echo json_encode(["success" => false, "message" => "Invalid phone number."]);
    exit;
}

// ---- SMTP credentials ----
$smtpUsername = "dosktech02@gmail.com";   // <-- Gmail address
$smtpPassword = "yqmtpkuhmjtjefkg";       // <-- 16-digit Gmail App Password
$schoolTo     = "dosktech02@gmail.com";   // <-- where admissions go

// Escape all user input before putting it into HTML
$e = function ($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
};

$submittedAt = date("d M Y, h:i A");

// =========================================================
//  Shared email chrome (header / footer) — brand colors
// =========================================================
$brandHeader = '
<tr>
  <td style="background-color:#5B0606; padding:28px 32px; text-align:center;">
    <div style="font-family: Georgia, \'Times New Roman\', serif; font-size:22px; font-weight:700; color:#FBF6EC; letter-spacing:0.5px;">
      Islamic Horizon
    </div>
    <div style="font-family: Arial, sans-serif; font-size:11px; letter-spacing:3px; text-transform:uppercase; color:#FFBD00; margin-top:4px;">
      School System
    </div>
  </td>
</tr>';

$brandFooter = '
<tr>
  <td style="background-color:#FBF6EC; padding:20px 32px; text-align:center; border-top:1px solid rgba(91,6,6,0.1);">
    <p style="margin:0; font-family: Arial, sans-serif; font-size:11px; color:#4A5952;">
      Mohallah Eid Gah Jassian Road, Mirza, Attock &nbsp;•&nbsp; +92 300 1234567
    </p>
    <p style="margin:6px 0 0; font-family: Arial, sans-serif; font-size:10px; color:#4A5952; opacity:0.7;">
      &copy; ' . date("Y") . ' Islamic Horizon School System. All rights reserved.
    </p>
  </td>
</tr>';

function wrapEmail($innerHtml, $brandHeader, $brandFooter) {
    return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"></head>
<body style="margin:0; padding:0; background-color:#f2ece0;">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f2ece0; padding:32px 16px;">
    <tr>
      <td align="center">
        <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 18px rgba(91,6,6,0.08);">
          ' . $brandHeader . '
          ' . $innerHtml . '
          ' . $brandFooter . '
        </table>
      </td>
    </tr>
  </table>
</body>
</html>';
}

// Helper to render a detail row
function detailRow($label, $value) {
    return '
    <tr>
      <td style="padding:9px 0; font-family: Arial, sans-serif; font-size:13px; color:#A66828; font-weight:700; width:170px; vertical-align:top;">' . $label . '</td>
      <td style="padding:9px 0; font-family: Arial, sans-serif; font-size:13px; color:#1A2420; vertical-align:top;">' . $value . '</td>
    </tr>';
}

// =========================================================
//  EMAIL 1 — To the School (admin notification)
// =========================================================
$schoolInner = '
<tr>
  <td style="padding:32px;">
    <div style="display:inline-block; font-family: Arial, sans-serif; font-size:10px; letter-spacing:2px; text-transform:uppercase; font-weight:700; color:#E5A823; margin-bottom:10px;">
      New Admission Application
    </div>
    <h2 style="margin:0 0 22px; font-family: Georgia, \'Times New Roman\', serif; font-size:22px; color:#5B0606;">
      ' . $e($studentName) . '
    </h2>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EC; border-radius:8px; padding:18px 20px; margin-bottom:18px;">
      <tr><td style="font-family: Arial, sans-serif; font-size:11px; letter-spacing:2px; text-transform:uppercase; font-weight:700; color:#5B0606; padding-bottom:8px;">Student Details</td></tr>
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          ' . detailRow('Full Name', $e($studentName)) . '
          ' . detailRow('Date of Birth', $e($dob)) . '
          ' . detailRow('Gender', $e($gender)) . '
          ' . detailRow('Class Applying For', $e($classFor)) . '
          ' . detailRow('Previous School', $prevSchool !== '' ? $e($prevSchool) : 'N/A') . '
        </table>
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EC; border-radius:8px; padding:18px 20px; margin-bottom:22px;">
      <tr><td style="font-family: Arial, sans-serif; font-size:11px; letter-spacing:2px; text-transform:uppercase; font-weight:700; color:#5B0606; padding-bottom:8px;">Parent / Guardian Details</td></tr>
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          ' . detailRow('Full Name', $e($guardianName)) . '
          ' . detailRow('Phone', $e($guardianPhone)) . '
          ' . detailRow('Email', $guardianEmail !== '' ? $e($guardianEmail) : 'N/A') . '
          ' . detailRow('Address', $e($address)) . '
          ' . detailRow('Notes', $notes !== '' ? $e($notes) : 'N/A') . '
        </table>
      </td></tr>
    </table>

    <p style="margin:0; font-family: Arial, sans-serif; font-size:11px; color:#4A5952;">
      Submitted on ' . $e($submittedAt) . '
    </p>
  </td>
</tr>';

$schoolHtml = wrapEmail($schoolInner, $brandHeader, $brandFooter);

// =========================================================
//  EMAIL 2 — To the Parent (confirmation)
// =========================================================
$parentInner = '
<tr>
  <td style="padding:36px 32px; text-align:center;">
    <div style="width:56px; height:56px; line-height:56px; background-color:rgba(255,189,0,0.18); border-radius:50%; margin:0 auto 18px; font-size:26px; color:#5B0606;">&#10003;</div>
    <h2 style="margin:0 0 10px; font-family: Georgia, \'Times New Roman\', serif; font-size:22px; color:#5B0606;">
      Application Received
    </h2>
    <p style="margin:0 0 26px; font-family: Arial, sans-serif; font-size:13px; line-height:1.7; color:#4A5952;">
      Jazak Allah Khair for applying to Islamic Horizon, ' . $e($guardianName) . '. We have received ' . $e($studentName) . '\'s
      application for <strong style="color:#1A2420;">' . $e($classFor) . '</strong>. Our admissions team will contact you
      within 1&ndash;2 working days to confirm the next steps.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#FBF6EC; border-radius:8px; padding:18px 20px; text-align:left; margin-bottom:8px;">
      <tr><td style="font-family: Arial, sans-serif; font-size:11px; letter-spacing:2px; text-transform:uppercase; font-weight:700; color:#5B0606; padding-bottom:8px;">Application Summary</td></tr>
      <tr><td>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
          ' . detailRow('Student', $e($studentName)) . '
          ' . detailRow('Class Applying For', $e($classFor)) . '
          ' . detailRow('Submitted On', $e($submittedAt)) . '
        </table>
      </td></tr>
    </table>
  </td>
</tr>';

$parentHtml = wrapEmail($parentInner, $brandHeader, $brandFooter);

// =========================================================
//  Send both emails
// =========================================================
$mail = new PHPMailer(true);

try {
    // ---- Email to School ----
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $smtpUsername;
    $mail->Password   = $smtpPassword;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
    $mail->Port       = 465;

    $mail->setFrom($smtpUsername, 'Islamic Horizon Admissions');
    $mail->addAddress($schoolTo);
    if (!empty($guardianEmail)) {
        $mail->addReplyTo($guardianEmail, $guardianName);
    }
    $mail->isHTML(true);
    $mail->Subject = "New Admission Application - " . $studentName;
    $mail->Body    = $schoolHtml;
    $mail->AltBody = "New admission application from $guardianName for $studentName ($classFor). Phone: $guardianPhone";
    $mail->send();

    // ---- Confirmation email to Parent (only if email provided) ----
    if (!empty($guardianEmail)) {
        $mail->clearAddresses();
        $mail->clearReplyTos();
        $mail->addAddress($guardianEmail, $guardianName);
        $mail->Subject = "We've received " . $studentName . "'s application - Islamic Horizon";
        $mail->Body    = $parentHtml;
        $mail->AltBody = "Jazak Allah Khair for applying to Islamic Horizon. We have received {$studentName}'s application for {$classFor}. Our admissions team will contact you within 1-2 working days.";
        $mail->send();
    }

    echo json_encode(["success" => true, "message" => "Application submitted successfully!"]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => "Failed to send application. Please try again.",
        "debug"   => $mail->ErrorInfo // testing ke baad ye line hata dein
    ]);
}