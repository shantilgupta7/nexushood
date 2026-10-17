<?php
session_start();
if (!isset($_SESSION['room_no'])) {
    header("Location: sign-in.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_no = $_SESSION['room_no'];
    $new_amount = floatval($_POST['amount']); // New advance amount from user

    $conn = new PDO("mysql:host=localhost;dbname=mysociety", "root", "");
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Check if an advance record already exists for this room_no
    $sql_check = "SELECT SUM(amount) as total_advance FROM advance WHERE room_no = :room_no";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bindParam(':room_no', $room_no);
    $stmt_check->execute();
    $existing_data = $stmt_check->fetch(PDO::FETCH_ASSOC);
    $existing_amount = $existing_data['total_advance'] ? $existing_data['total_advance'] : 0;

    if ($existing_amount > 0) {
        // Update existing record by adding new amount and setting latest paid_date
        $updated_amount = $existing_amount + $new_amount;
        $sql_update = "UPDATE advance SET amount = :amount, paid_date = NOW() 
                       WHERE room_no = :room_no";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':amount', $updated_amount);
        $stmt_update->bindParam(':room_no', $room_no);
        $stmt_update->execute();
    } else {
        // Insert new record with initial amount and current paid_date
        $sql_insert = "INSERT INTO advance (room_no, amount, paid_date) 
                       VALUES (:room_no, :amount, NOW())";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bindParam(':room_no', $room_no);
        $stmt_insert->bindParam(':amount', $new_amount);
        $stmt_insert->execute();
    }

    header("Location: m_details.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Advance Payment</title>
    <style>
        body { font-family: Arial, sans-serif; text-align: center; margin-top: 50px; }
        input { padding: 10px; margin: 5px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer; }
        button:hover { background: #45a049; }
    </style>
</head>
<body>
    <h2>Add Advance Payment</h2>
    <form method="POST">
        <input type="number" name="amount" placeholder="Enter Amount" step="0.01" required>
        <br>
        <button type="submit">Submit Advance Payment</button>
    </form>
</body>
</html>