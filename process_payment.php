<?php
header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

// Create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check the connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve and sanitize input values
    $name = htmlspecialchars(trim($_POST['name']));
    $room_no = htmlspecialchars(trim($_POST['room_no']));
    $category = htmlspecialchars(trim($_POST['category']));
    $amount = htmlspecialchars(trim($_POST['amount']));
    $date = htmlspecialchars(trim($_POST['date']));
    $time = htmlspecialchars(trim($_POST['time']));

    // Prepare the SQL statement for inserting payment data
    $sql = "INSERT INTO payment (name, room_no, category, amount, date, time) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // Check if the statement was prepared correctly
    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'SQL statement preparation failed: ' . $conn->error]);
        $conn->close();
        exit;
    }

    // Bind the parameters to the statement
    $stmt->bind_param('ssssss', $name, $room_no, $category, $amount, $date, $time);

    // Execute the statement and check for success
    if ($stmt->execute()) {
        // Fetch the latest records after successful insertion
        $fetchSql = "SELECT * FROM payment ORDER BY id DESC";
        $result = $conn->query($fetchSql);

        $payments = [];
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $payments[] = $row;
            }
        }

        echo json_encode(['success' => true, 'message' => 'Payment inserted successfully', 'data' => $payments]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Payment insertion failed: ' . $stmt->error]);
    }

    // Close the statement
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}

// Close the database connection
$conn->close();
?>
