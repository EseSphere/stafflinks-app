<?php
// Connect to MySQL
$conn = new mysqli('localhost', 'root', '', 'stafflinks');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => $conn->connect_error]);
    exit;
}
