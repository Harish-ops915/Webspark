<?php
include "config.php";
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$website_id = (int)$input['website_id'];
$dwell_time = (int)$input['dwell_time'];
$bounce = (int)$input['bounce'];

// Insert visit record
$stmt = $mysqli->prepare("INSERT INTO visits (website_id, visitor_user_id, dwell_time, bounce) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiii", $website_id, $user_id, $dwell_time, $bounce);

if ($stmt->execute()) {
    // Award credit to visitor
    $mysqli->query("UPDATE users SET credits = credits + 1 WHERE id = $user_id");
    
    // Log credit transaction
    $stmt = $mysqli->prepare("INSERT INTO credits_log (user_id, change_amount, reason) VALUES (?, 1, 'Visit completed')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
