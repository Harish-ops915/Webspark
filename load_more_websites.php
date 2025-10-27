<?php
include "config.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

$stmt = $mysqli->prepare("
    SELECT w.*, u.username, u.country as user_country,
           COALESCE(wa.visits_count, 0) as total_visits,
           COALESCE(wa.unique_visitors, 0) as unique_visitors
    FROM websites w 
    JOIN users u ON w.user_id = u.id 
    LEFT JOIN website_analytics wa ON w.id = wa.website_id AND wa.date = CURDATE()
    WHERE w.user_id != ? AND w.status = 'active' 
    ORDER BY RAND() 
    LIMIT ? OFFSET ?
");
$stmt->bind_param("iii", $user_id, $limit, $offset);
$stmt->execute();
$websites = $stmt->get_result();

$websites_array = [];
while ($website = $websites->fetch_assoc()) {
    $websites_array[] = $website;
}

echo json_encode(['success' => true, 'websites' => $websites_array]);
?>
