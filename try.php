<?php
session_start();
if (!isset($_SESSION['room_no'])) {
    header("Location: sign-in.php");
    exit();
}

// Database connection
$host = 'localhost';
$db_name = 'mysociety';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Function to send JSON response
function sendResponse($success, $data = [], $message = '') {
    header('Content-Type: application/json');
    echo json_encode(['success' => $success, 'data' => $data, 'message' => $message]);
    exit();
}

// Handle advance payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && isset($_POST['advance_amount'])) {
    $advance_amount = floatval($_POST['advance_amount']);
    $payment_method = $_POST['payment_method'];
    $room_no = $_SESSION['room_no'];
    $name = $_SESSION['fh_name'];

    $sql_payment = "INSERT INTO payment (room_no, name, amount, mode, date, time, purpose) 
                    VALUES (:room_no, :name, :amount, :mode, NOW(), NOW(), 'maintenance')";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bindParam(':room_no', $room_no);
    $stmt_payment->bindParam(':name', $name);
    $stmt_payment->bindParam(':amount', $advance_amount);
    $stmt_payment->bindParam(':mode', $payment_method);
    $stmt_payment->execute();

    $sql_check_advance = "SELECT amount FROM advance WHERE room_no = :room_no";
    $stmt_check_advance = $conn->prepare($sql_check_advance);
    $stmt_check_advance->bindParam(':room_no', $room_no);
    $stmt_check_advance->execute();
    $existing_advance = $stmt_check_advance->fetch(PDO::FETCH_ASSOC);

    if ($existing_advance) {
        $new_advance_amount = $existing_advance['amount'] + $advance_amount;
        $sql_update_advance = "UPDATE advance SET amount = :amount, paid_date = NOW() WHERE room_no = :room_no";
        $stmt_update_advance = $conn->prepare($sql_update_advance);
        $stmt_update_advance->bindParam(':amount', $new_advance_amount);
        $stmt_update_advance->bindParam(':room_no', $room_no);
        $stmt_update_advance->execute();
    } else {
        $sql_insert_advance = "INSERT INTO advance (room_no, amount, paid_date) VALUES (:room_no, :amount, NOW())";
        $stmt_insert_advance = $conn->prepare($sql_insert_advance);
        $stmt_insert_advance->bindParam(':room_no', $room_no);
        $stmt_insert_advance->bindParam(':amount', $advance_amount);
        $stmt_insert_advance->execute();
    }

    $updated_data = fetchUpdatedData($conn, $room_no);
    sendResponse(true, $updated_data);
}

// Handle due payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_method']) && isset($_POST['due_amount'])) {
    $due_payment_amount = floatval($_POST['due_amount']);
    $payment_method = $_POST['payment_method'];
    $room_no = $_SESSION['room_no'];
    $name = $_SESSION['fh_name'];

    $sql_payment = "INSERT INTO payment (room_no, name, amount, mode, date, time, purpose) 
                    VALUES (:room_no, :name, :amount, :mode, NOW(), NOW(), 'maintenance')";
    $stmt_payment = $conn->prepare($sql_payment);
    $stmt_payment->bindParam(':room_no', $room_no);
    $stmt_payment->bindParam(':name', $name);
    $stmt_payment->bindParam(':amount', $due_payment_amount);
    $stmt_payment->bindParam(':mode', $payment_method);
    $stmt_payment->execute();

    $next_month_date = new DateTime();
    $next_month_date->modify('first day of next month');
    $next_bill_generate_date = $next_month_date->format('01-m-Y');
    
    $sql_update_due = "UPDATE due SET due_amount = 0, bill_date = :bill_date WHERE room_no = :room_no";
    $stmt_update_due = $conn->prepare($sql_update_due);
    $stmt_update_due->bindParam(':bill_date', $next_bill_generate_date);
    $stmt_update_due->bindParam(':room_no', $room_no);
    $stmt_update_due->execute();

    $updated_data = fetchUpdatedData($conn, $room_no);
    sendResponse(true, $updated_data);
}

// Function to fetch updated data
function fetchUpdatedData($conn, $room_no) {
    $current_date = new DateTime();
    $bill_generate_date = $current_date->format('01-m-Y');
    $next_month_date = clone $current_date;
    $next_month_date->modify('first day of next month');
    $next_bill_generate_date = $next_month_date->format('01-m-Y');

    $sql_flat = "SELECT per_flat_cost FROM flat_maintenance LIMIT 1";
    $stmt_flat = $conn->prepare($sql_flat);
    $stmt_flat->execute();
    $flat_data = $stmt_flat->fetch(PDO::FETCH_ASSOC);
    $per_flat_cost = $flat_data ? $flat_data['per_flat_cost'] : 0;

    $sql_joining = "SELECT joining_date FROM flat_holder WHERE room_no = :room_no";
    $stmt_joining = $conn->prepare($sql_joining);
    $stmt_joining->bindParam(':room_no', $room_no);
    $stmt_joining->execute();
    $joining_data = $stmt_joining->fetch(PDO::FETCH_ASSOC);
    $joining_date = $joining_data ? new DateTime($joining_data['joining_date']) : new DateTime('2025-01-01');

    $joining_date->modify('first day of this month');
    $months_since_joining = (int)$current_date->diff($joining_date)->format('%m') + 
                           (12 * ((int)$current_date->format('Y') - $joining_date->format('Y')));
    if ($months_since_joining < 0) $months_since_joining = 0;

    $sql_all_payments = "SELECT SUM(amount) as total_paid 
                         FROM payment 
                         WHERE room_no = :room_no 
                         AND purpose = 'maintenance' 
                         AND mode != 'Advance Maint.' 
                         AND date <= LAST_DAY(:current_date)";
    $stmt_all_payments = $conn->prepare($sql_all_payments);
    $stmt_all_payments->bindParam(':room_no', $room_no);
    $stmt_all_payments->bindValue(':current_date', $current_date->format('Y-m-d'));
    $stmt_all_payments->execute();
    $payment_data = $stmt_all_payments->fetch(PDO::FETCH_ASSOC);
    $total_paid = $payment_data['total_paid'] ?? 0;

    $total_due_till_now = $per_flat_cost * $months_since_joining;
    $due_amount = $total_due_till_now - $total_paid;
    if ($due_amount < 0) $due_amount = 0;

    $sql_check_due = "SELECT due_amount FROM due WHERE room_no = :room_no";
    $stmt_check_due = $conn->prepare($sql_check_due);
    $stmt_check_due->bindParam(':room_no', $room_no);
    $stmt_check_due->execute();
    $existing_due = $stmt_check_due->fetch(PDO::FETCH_ASSOC);

    if ($existing_due) {
        $sql_update_due = "UPDATE due SET due_amount = :due_amount, bill_date = :bill_date WHERE room_no = :room_no";
        $stmt_update_due = $conn->prepare($sql_update_due);
        $stmt_update_due->bindParam(':due_amount', $due_amount);
        $stmt_update_due->bindParam(':bill_date', $bill_generate_date);
        $stmt_update_due->bindParam(':room_no', $room_no);
        $stmt_update_due->execute();
    } else {
        $sql_insert_due = "INSERT INTO due (room_no, due_amount, bill_date) VALUES (:room_no, :due_amount, :bill_date)";
        $stmt_insert_due = $conn->prepare($sql_insert_due);
        $stmt_insert_due->bindParam(':room_no', $room_no);
        $stmt_insert_due->bindParam(':due_amount', $due_amount);
        $stmt_insert_due->bindParam(':bill_date', $bill_generate_date);
        $stmt_insert_due->execute();
    }

    $sql_advance = "SELECT amount, paid_date FROM advance WHERE room_no = :room_no ORDER BY paid_date DESC LIMIT 1";
    $stmt_advance = $conn->prepare($sql_advance);
    $stmt_advance->bindParam(':room_no', $room_no);
    $stmt_advance->execute();
    $advance_data = $stmt_advance->fetch(PDO::FETCH_ASSOC);
    $advance_amount = $advance_data ? $advance_data['amount'] : 0;
    $advance_date = $advance_data ? (new DateTime($advance_data['paid_date']))->format('d-m-Y') : 'N/A';

    if ($due_amount > 0 && $advance_amount > 0) {
        $amount_to_deduct = min($due_amount, $advance_amount);
        $new_advance_amount = $advance_amount - $amount_to_deduct;
        $due_amount -= $amount_to_deduct;

        $sql_insert_payment = "INSERT INTO payment (room_no, name, amount, mode, date, time, purpose) 
                               VALUES (:room_no, :name, :amount, 'Advance Deduction', NOW(), NOW(), 'maintenance')";
        $stmt_insert_payment = $conn->prepare($sql_insert_payment);
        $stmt_insert_payment->bindParam(':room_no', $room_no);
        $stmt_insert_payment->bindParam(':name', $_SESSION['fh_name']);
        $stmt_insert_payment->bindParam(':amount', $amount_to_deduct);
        $stmt_insert_payment->execute();

        if ($new_advance_amount > 0) {
            $sql_update_advance = "UPDATE advance SET amount = :amount, paid_date = NOW() WHERE room_no = :room_no";
            $stmt_update_advance = $conn->prepare($sql_update_advance);
            $stmt_update_advance->bindParam(':amount', $new_advance_amount);
            $stmt_update_advance->bindParam(':room_no', $room_no);
            $stmt_update_advance->execute();
        } else {
            $sql_delete_advance = "DELETE FROM advance WHERE room_no = :room_no";
            $stmt_delete_advance = $conn->prepare($sql_delete_advance);
            $stmt_delete_advance->bindParam(':room_no', $room_no);
            $stmt_delete_advance->execute();
        }

        $sql_update_due = "UPDATE due SET due_amount = :due_amount, bill_date = :bill_date WHERE room_no = :room_no";
        $stmt_update_due = $conn->prepare($sql_update_due);
        $stmt_update_due->bindParam(':due_amount', $due_amount);
        $stmt_update_due->bindParam(':bill_date', $bill_generate_date);
        $stmt_update_due->bindParam(':room_no', $room_no);
        $stmt_update_due->execute();
    }

    $bill_text = $due_amount == 0 ? "Next Bill Generate On" : "Bill Generated";
    $bill_date = $due_amount == 0 ? $next_bill_generate_date : $bill_generate_date;

    $sql_history = "SELECT name, amount, mode, date, time FROM payment 
                    WHERE room_no = :room_no 
                    AND purpose = 'maintenance' 
                    AND mode != 'Advance Maint.' 
                    ORDER BY date DESC, time DESC 
                    LIMIT 100";
    $stmt_history = $conn->prepare($sql_history);
    $stmt_history->bindParam(':room_no', $room_no);
    $stmt_history->execute();
    $payment_history = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
    foreach ($payment_history as &$payment) {
        $payment['date'] = (new DateTime($payment['date']))->format('d-m-Y');
        $payment['time'] = date('g:i A', strtotime($payment['time']));
    }

    return [
        'due_amount' => number_format($due_amount, 2),
        'advance_amount' => number_format($advance_amount, 2),
        'advance_date' => $advance_date,
        'bill_text' => $bill_text,
        'bill_date' => $bill_date,
        'payment_history' => $payment_history
    ];
}

// Initial data fetch for page load
$current_date = new DateTime();
$current_month = $current_date->format('m');
$current_year = $current_date->format('Y');
$bill_generate_date = $current_date->format('01-m-Y');

$next_month_date = clone $current_date;
$next_month_date->modify('first day of next month');
$next_bill_generate_date = $next_month_date->format('01-m-Y');

$bill_text = "Bill Generated";

$sql_flat = "SELECT per_flat_cost FROM flat_maintenance LIMIT 1";
$stmt_flat = $conn->prepare($sql_flat);
$stmt_flat->execute();
$flat_data = $stmt_flat->fetch(PDO::FETCH_ASSOC);
$per_flat_cost = $flat_data ? $flat_data['per_flat_cost'] : 0;

$room_no = $_SESSION['room_no'];

$sql_joining = "SELECT joining_date FROM flat_holder WHERE room_no = :room_no";
$stmt_joining = $conn->prepare($sql_joining);
$stmt_joining->bindParam(':room_no', $room_no);
$stmt_joining->execute();
$joining_data = $stmt_joining->fetch(PDO::FETCH_ASSOC);
$joining_date = $joining_data ? new DateTime($joining_data['joining_date']) : new DateTime('2025-01-01');

$joining_date->modify('first day of this month');

$sql_all_payments = "SELECT SUM(amount) as total_paid 
                     FROM payment 
                     WHERE room_no = :room_no 
                     AND purpose = 'maintenance' 
                     AND mode != 'Advance Maint.' 
                     AND date <= LAST_DAY(:current_date)";
$stmt_all_payments = $conn->prepare($sql_all_payments);
$stmt_all_payments->bindParam(':room_no', $room_no);
$stmt_all_payments->bindValue(':current_date', $current_date->format('Y-m-d'));
$stmt_all_payments->execute();
$payment_data = $stmt_all_payments->fetch(PDO::FETCH_ASSOC);
$total_paid = $payment_data['total_paid'] ?? 0;

$months_since_joining = (int)$current_date->diff($joining_date)->format('%m') + 
                       (12 * ((int)$current_year - $joining_date->format('Y')));
if ($months_since_joining < 0) $months_since_joining = 0;
$total_due_till_now = $per_flat_cost * $months_since_joining;
$due_amount = $total_due_till_now - $total_paid;
if ($due_amount < 0) $due_amount = 0;

$sql_check_due = "SELECT due_amount FROM due WHERE room_no = :room_no";
$stmt_check_due = $conn->prepare($sql_check_due);
$stmt_check_due->bindParam(':room_no', $room_no);
$stmt_check_due->execute();
$existing_due = $stmt_check_due->fetch(PDO::FETCH_ASSOC);

if ($existing_due) {
    $sql_update_due = "UPDATE due SET due_amount = :due_amount, bill_date = :bill_date WHERE room_no = :room_no";
    $stmt_update_due = $conn->prepare($sql_update_due);
    $stmt_update_due->bindParam(':due_amount', $due_amount);
    $stmt_update_due->bindParam(':bill_date', $bill_generate_date);
    $stmt_update_due->bindParam(':room_no', $room_no);
    $stmt_update_due->execute();
} else {
    $sql_insert_due = "INSERT INTO due (room_no, due_amount, bill_date) VALUES (:room_no, :due_amount, :bill_date)";
    $stmt_insert_due = $conn->prepare($sql_insert_due);
    $stmt_insert_due->bindParam(':room_no', $room_no);
    $stmt_insert_due->bindParam(':due_amount', $due_amount);
    $stmt_insert_due->bindParam(':bill_date', $bill_generate_date);
    $stmt_insert_due->execute();
}

$sql_advance = "SELECT amount, paid_date FROM advance WHERE room_no = :room_no ORDER BY paid_date DESC LIMIT 1";
$stmt_advance = $conn->prepare($sql_advance);
$stmt_advance->bindParam(':room_no', $room_no);
$stmt_advance->execute();
$advance_data = $stmt_advance->fetch(PDO::FETCH_ASSOC);
$advance_payment_amount = $advance_data ? $advance_data['amount'] : 0;
$advance_payment_date = $advance_data ? (new DateTime($advance_data['paid_date']))->format('d-m-Y') : 'N/A';

if ($due_amount > 0 && $advance_payment_amount > 0) {
    $amount_to_deduct = min($due_amount, $advance_payment_amount);
    $new_advance_amount = $advance_payment_amount - $amount_to_deduct;
    $due_amount -= $amount_to_deduct;

    $sql_insert_payment = "INSERT INTO payment (room_no, name, amount, mode, date, time, purpose) 
                           VALUES (:room_no, :name, :amount, 'Advance Deduction', NOW(), NOW(), 'maintenance')";
    $stmt_insert_payment = $conn->prepare($sql_insert_payment);
    $stmt_insert_payment->bindParam(':room_no', $room_no);
    $stmt_insert_payment->bindParam(':name', $_SESSION['fh_name']);
    $stmt_insert_payment->bindParam(':amount', $amount_to_deduct);
    $stmt_insert_payment->execute();

    if ($new_advance_amount > 0) {
        $sql_update_advance = "UPDATE advance SET amount = :amount, paid_date = NOW() WHERE room_no = :room_no";
        $stmt_update_advance = $conn->prepare($sql_update_advance);
        $stmt_update_advance->bindParam(':amount', $new_advance_amount);
        $stmt_update_advance->bindParam(':room_no', $room_no);
        $stmt_update_advance->execute();
    } else {
        $sql_delete_advance = "DELETE FROM advance WHERE room_no = :room_no";
        $stmt_delete_advance = $conn->prepare($sql_delete_advance);
        $stmt_delete_advance->bindParam(':room_no', $room_no);
        $stmt_delete_advance->execute();
    }

    $sql_update_due = "UPDATE due SET due_amount = :due_amount, bill_date = :bill_date WHERE room_no = :room_no";
    $stmt_update_due = $conn->prepare($sql_update_due);
    $stmt_update_due->bindParam(':due_amount', $due_amount);
    $stmt_update_due->bindParam(':bill_date', $bill_generate_date);
    $stmt_update_due->bindParam(':room_no', $room_no);
    $stmt_update_due->execute();
}

if ($due_amount == 0) {
    $bill_generate_date = $next_bill_generate_date;
    $bill_text = "Next Bill Generate On";
}

$sql_history = "SELECT name, amount, mode, date, time FROM payment 
                WHERE room_no = :room_no 
                AND purpose = 'maintenance' 
                AND mode != 'Advance Maint.' 
                ORDER BY date DESC, time DESC 
                LIMIT 100";
$stmt_history = $conn->prepare($sql_history);
$stmt_history->bindParam(':room_no', $room_no);
$stmt_history->execute();
$payment_details = $stmt_history->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NeXusHooD.com</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/slick.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="js/paymentSlip.js" defer></script>

    <style>
    .container4 {
        width: 90%;
        max-width: 1200px;
        margin: 30px auto;
        padding: 20px;
        background-color: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
        font-size: 2em;
    }

    .payment-overview {
        display: flex;
        justify-content: space-between;
        gap: 20px;
        padding: 30px;
        background: linear-gradient(135deg, #4169E1, #00246b);
        border-radius: 15px;
        box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
        margin-bottom: 30px;
        color: #fff;
    }

    .payment-section {
        flex: 1;
        text-align: center;
        padding: 20px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .payment-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
    }

    .payment-section h3 {
        font-size: 1.5em;
        margin-bottom: 15px;
        color: #fff;
    }

    .payment-section p {
        font-size: 1.2em;
        margin: 10px 0;
        color: #e0e0e0;
    }

    .pay-btn {
        padding: 12px 25px;
        background: linear-gradient(90deg, #4CAF50, #45a049);
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1em;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .pay-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

    .pay-btn:disabled {
        background: #cccccc;
        cursor: not-allowed;
    }

    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6);
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease-in-out;
    }

    .modal.active {
        opacity: 1;
    }

    .modal:not(.active) {
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .modal-content {
        background: linear-gradient(135deg, #ffffff, #f0f4f8);
        padding: 30px;
        border-radius: 15px;
        width: 450px;
        max-width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        position: relative;
        transform: scale(0.9);
        animation: slideIn 0.3s ease-out forwards;
    }

    .modal-content h3 {
        margin: 0 0 15px;
        color: #00246b;
        font-size: 1.8em;
        text-align: center;
        font-weight: 700;
    }

    .modal-content p {
        text-align: center;
        color: #666;
        font-size: 1.1em;
        margin-bottom: 20px;
    }

    .modal-content label {
        display: block;
        margin: 10px 0 5px;
        color: #333;
        font-weight: 500;
    }

    .modal-content input[type="text"],
    .modal-content input[type="number"],
    .modal-content select {
        width: 100%;
        padding: 12px;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1.1em;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    .modal-content input[type="text"]:focus,
    .modal-content input[type="number"]:focus,
    .modal-content select:focus {
        border-color: #4169E1;
        box-shadow: 0 0 8px rgba(65, 105, 225, 0.3);
        outline: none;
    }

    .pay-now-btn {
        width: 100%;
        padding: 12px;
        background: linear-gradient(90deg, #4CAF50, #45a049);
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 1.2em;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.3s;
    }

    .pay-now-btn:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
    }

    .close-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        font-size: 1.8em;
        color: #aaa;
        cursor: pointer;
        transition: color 0.3s;
    }

    .close-btn:hover {
        color: #00246b;
    }

    .payment-method-modal-content {
        background: linear-gradient(135deg, #ffffff, #f0f4f8);
        padding: 30px;
        border-radius: 15px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        position: relative;
        overflow: hidden;
    }

    .payment-method-modal-content h3 {
        color: #00246b;
        font-size: 1.8em;
        text-align: center;
        margin-bottom: 10px;
    }

    .modal-subtitle {
        text-align: center;
        color: #666;
        font-size: 1em;
        margin-bottom: 20px;
    }

    .payment-options {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .payment-option {
        position: relative;
        display: flex;
        align-items: center;
        padding: 0;
        margin: 0;
        cursor: pointer;
        transition: transform 0.3s ease;
    }

    .payment-option input[type="radio"] {
        display: none;
    }

    .payment-card {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
        padding: 15px 20px;
        background: #fff;
        border: 2px solid #ddd;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        transition: all 0.3s ease;
    }

    .payment-option input[type="radio"]:checked + .payment-card {
        border-color: #4CAF50;
        box-shadow: 0 6px 15px rgba(76, 175, 80, 0.2);
        background: linear-gradient(90deg, #f0fff0, #fff);
    }

    .payment-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    }

    .payment-icon {
        font-size: 1.8em;
        color: #4169E1;
        margin-right: 15px;
    }

    .payment-card span {
        font-size: 1.2em;
        font-weight: 500;
        color: #333;
        flex-grow: 1;
    }

    .card-brands {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .brand-logo {
        height: 20px;
        width: auto;
        opacity: 0.8;
        transition: opacity 0.3s ease;
    }

    .payment-option input[type="radio"]:checked + .payment-card .brand-logo {
        opacity: 1;
    }

    .bank-text {
        font-size: 0.9em;
        color: #666;
    }

    .security-note {
        text-align: center;
        font-size: 0.9em;
        color: #888;
        margin-top: 15px;
    }

    .security-note i {
        color: #4CAF50;
        margin-right: 5px;
    }

    .payment-details-modal-content {
        background: linear-gradient(135deg, #ffffff, #f0f4f8);
        padding: 30px;
        border-radius: 15px;
        width: 450px;
        max-width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        position: relative;
    }

    .success-modal-content {
        text-align: center;
        padding: 40px;
        background: #fff;
        border-radius: 15px;
        width: 400px;
        max-width: 90%;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        position: relative;
        animation: slideIn 0.3s ease-out forwards;
    }

    .success-modal-content i {
        font-size: 3em;
        color: #4CAF50;
        margin-bottom: 20px;
    }

    .success-modal-content h3 {
        color: #333;
        font-size: 1.8em;
        margin-bottom: 15px;
    }

    .success-modal-content p {
        color: #666;
        font-size: 1.1em;
    }

    .processing {
        position: relative;
        pointer-events: none;
    }

    .processing::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        border: 3px solid #fff;
        border-top: 3px solid transparent;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        transform: translate(-50%, -50%);
    }

    table {
        width: 100%;
        border-collapse: collapse;
        border-radius: 10px;
    }

    table th, table td {
        border: 1px solid #ddd;
        padding: 15px;
        text-align: center;
        color: #555;
    }

    table th {
        background: #4169E1;
        color: #fff;
        font-weight: bold;
    }

    table tr:nth-child(even) {
        background: #f9f9f9;
    }

    table tr:hover {
        background: #f1f1f1;
    }

    .download-btn {
        padding: 8px 12px;
        background: #4169E1;
        color: #fff;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s, transform 0.2s;
        text-decoration: none;
        display: inline-block;
    }

    .download-btn:hover {
        background: #00246b;
        transform: scale(1.05);
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    @keyframes slideIn {
        from { transform: scale(0.9); opacity: 0; }
        to { transform: scale(1); opacity: 1; }
    }

    @keyframes spin {
        0% { transform: translate(-50%, -50%) rotate(0deg); }
        100% { transform: translate(-50%, -50%) rotate(360deg); }
    }

    @media (max-width: 768px) {
        .container4 {
            width: 100%;
            padding: 15px;
        }

        .payment-overview {
            flex-direction: column;
            padding: 20px;
        }

        .payment-section {
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 10px;
        }

        .modal-content, .payment-method-modal-content, .payment-details-modal-content, .success-modal-content {
            width: 90%;
            padding: 20px;
        }

        .modal-content h3, .payment-method-modal-content h3, .payment-details-modal-content h3, .success-modal-content h3 {
            font-size: 1.5em;
        }

        .pay-now-btn {
            font-size: 1.1em;
        }
    }
    .user-info p {
        font-weight: bold;
        margin: 0;
    }
    </style>
</head>

<body>
    <section class="preloader">
        <div class="spinner">
            <span class="sk-inner-circle"></span>
        </div>
    </section>
        
    <main>
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand" href="home2.php">
                    <strong><span><img src="images/logo2.png" alt="logo" class="logo"></span> NeXusHooD</strong>
                </a>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="home2.php"><i class="fas fa-home"></i>Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="m_details.php"><i class="fas fa-wallet"></i>Maintenance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="c_details.php"><i class="fas fa-comments"></i>Complaint</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about2.php"><i class="fas fa-info-circle"></i>About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out"></i>Sign-out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <section class="slick-slideshow">   
            <div class="slick-custom">
                <img src="images/slideshow/s2.jpg" class="img-fluid" alt="">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Category: <?php echo htmlspecialchars($_SESSION['category']); ?></p>
                                    <?php
                                    $joiningDate = new DateTime($_SESSION['joining_date']);
                                    $formattedDate = $joiningDate->format('d-m-Y');
                                    ?>
                                    <p class="lead text-white">Joining Date: <?php echo htmlspecialchars($formattedDate); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slick-custom">
                <img src="images/slideshow/s12.jpg" class="img-fluid" alt="">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Category: <?php echo htmlspecialchars($_SESSION['category']); ?></p>
                                    <?php
                                    $joiningDate = new DateTime($_SESSION['joining_date']);
                                    $formattedDate = $joiningDate->format('d-m-Y');
                                    ?>
                                    <p class="lead text-white">Joining Date: <?php echo htmlspecialchars($formattedDate); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slick-custom">
                <img src="images/slideshow/s4.jpg" class="img-fluid" alt="">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Category: <?php echo htmlspecialchars($_SESSION['category']); ?></p>
                                    <?php
                                    $joiningDate = new DateTime($_SESSION['joining_date']);
                                    $formattedDate = $joiningDate->format('d-m-Y');
                                    ?>
                                    <p class="lead text-white">Joining Date: <?php echo htmlspecialchars($formattedDate); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="container4">
            <div class="payment-overview">
                <div class="payment-section">
                    <h3>Due Amount</h3>
                    <p>₹<?php echo number_format($due_amount, 2); ?></p>
                    <p><?php echo $bill_text; ?>: <?php echo $bill_generate_date; ?></p>
                    <?php if ($due_amount > 0): ?>
                        <button class="pay-btn" id="duePayBtn">Pay Now</button>
                    <?php else: ?>
                        <button class="pay-btn" disabled>Paid</button>
                    <?php endif; ?>
                </div>
                <div class="payment-section">
                    <h3>Advance Payment</h3>
                    <p>₹<?php echo number_format($advance_payment_amount, 2); ?></p>
                    <p>Paid On: <?php echo $advance_payment_date; ?></p>
                    <button class="pay-btn" id="advancePayBtn">Pay Now</button>
                </div>
            </div>

            <!-- Modal for Due Payment -->
            <div id="dueModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">×</span>
                    <h3>Pay Due Amount</h3>
                    <p>Amount: ₹<?php echo number_format($due_amount, 2); ?></p>
                    <button id="proceedToDuePayment" class="pay-now-btn">Proceed to Payment</button>
                </div>
            </div>

            <!-- Modal for Due Payment Method Selection -->
            <div id="duePaymentMethodModal" class="modal">
                <div class="modal-content payment-method-modal-content">
                    <span class="close-btn">×</span>
                    <h3>Select Payment Method</h3>
                    <p class="modal-subtitle">Choose a secure payment option</p>
                    <div class="input-group payment-options">
                        <label class="payment-option">
                            <input type="radio" name="due_payment_method" value="Credit Card" required>
                            <div class="payment-card">
                                <i class="fas fa-credit-card payment-icon"></i>
                                <span>Credit/Debit Card</span>
                                <div class="card-brands">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="brand-logo">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" class="brand-logo">
                                </div>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="due_payment_method" value="UPI">
                            <div class="payment-card">
                                <i class="fas fa-mobile-alt payment-icon"></i>
                                <span>UPI</span>
                                <div class="card-brands">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" alt="Google Pay" class="brand-logo">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/89/UPI_Logo_vector.svg" alt="UPI" class="brand-logo">
                                </div>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="due_payment_method" value="Net Banking">
                            <div class="payment-card">
                                <i class="fas fa-university payment-icon"></i>
                                <span>Net Banking</span>
                                <div class="card-brands">
                                    <span class="bank-text">SBI, HDFC, ICICI & more</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <button id="proceedToDueDetails" class="pay-now-btn">Proceed</button>
                    <p class="security-note"><i class="fas fa-lock"></i> Secured by 256-bit encryption</p>
                </div>
            </div>

            <!-- Modal for Due Payment Details -->
            <div id="duePaymentDetailsModal" class="modal">
                <div class="modal-content payment-details-modal-content">
                    <span class="close-btn">×</span>
                    <h3 id="dueDetailsTitle"></h3>
                    <form id="duePaymentDetailsForm" method="POST" action="">
                        <input type="hidden" name="due_amount" value="<?php echo $due_amount; ?>">
                        <input type="hidden" name="payment_method" id="duePaymentMethod">
                        <div id="dueCreditCardDetails" class="payment-details" style="display: none;">
                            <label for="dueCardNumber">Card Number</label>
                            <input type="text" id="dueCardNumber" placeholder="1234 5678 9012 3456" required>
                            <label for="dueExpiry">Expiry Date</label>
                            <input type="text" id="dueExpiry" placeholder="MM/YY" required>
                            <label for="dueCvv">CVV</label>
                            <input type="text" id="dueCvv" placeholder="123" required>
                        </div>
                        <div id="dueUpiDetails" class="payment-details" style="display: none;">
                            <label for="dueUpiId">UPI ID</label>
                            <input type="text" id="dueUpiId" placeholder="example@upi" required>
                        </div>
                        <div id="dueNetBankingDetails" class="payment-details" style="display: none;">
                            <label for="dueBank">Select Bank</label>
                            <select id="dueBank" required>
                                <option value="">-- Select Bank --</option>
                                <option value="SBI">State Bank of India</option>
                                <option value="HDFC">HDFC Bank</option>
                                <option value="ICICI">ICICI Bank</option>
                            </select>
                        </div>
                        <button type="submit" class="pay-now-btn" id="duePayNowBtn">Pay Now</button>
                    </form>
                </div>
            </div>

            <!-- Modal for Advance Payment -->
            <div id="advanceModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">×</span>
                    <h3>Advance Payment</h3>
                    <p>Secure your maintenance with an advance payment! (Minimum ₹100)</p>
                    <div class="input-group">
                        <label for="advance_amount_input">Amount (₹):</label>
                        <input type="number" id="advance_amount_input" min="100" step="0.01" placeholder="Enter amount (min 100)" required>
                    </div>
                    <button id="proceedToAdvancePayment" class="pay-now-btn">Proceed to Payment</button>
                </div>
            </div>

            <!-- Modal for Advance Payment Method Selection -->
            <div id="advancePaymentMethodModal" class="modal">
                <div class="modal-content payment-method-modal-content">
                    <span class="close-btn">×</span>
                    <h3>Select Payment Method</h3>
                    <p class="modal-subtitle">Choose a secure payment option</p>
                    <div class="input-group payment-options">
                        <label class="payment-option">
                            <input type="radio" name="advance_payment_method" value="Credit Card" required>
                            <div class="payment-card">
                                <i class="fas fa-credit-card payment-icon"></i>
                                <span>Credit/Debit Card</span>
                                <div class="card-brands">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/5/5e/Visa_Inc._logo.svg" alt="Visa" class="brand-logo">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" class="brand-logo">
                                </div>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="advance_payment_method" value="UPI">
                            <div class="payment-card">
                                <i class="fas fa-mobile-alt payment-icon"></i>
                                <span>UPI</span>
                                <div class="card-brands">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" alt="Google Pay" class="brand-logo">
                                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/89/UPI_Logo_vector.svg" alt="UPI" class="brand-logo">
                                </div>
                            </div>
                        </label>
                        <label class="payment-option">
                            <input type="radio" name="advance_payment_method" value="Net Banking">
                            <div class="payment-card">
                                <i class="fas fa-university payment-icon"></i>
                                <span>Net Banking</span>
                                <div class="card-brands">
                                    <span class="bank-text">SBI, HDFC, ICICI & more</span>
                                </div>
                            </div>
                        </label>
                    </div>
                    <button id="proceedToAdvanceDetails" class="pay-now-btn">Proceed</button>
                    <p class="security-note"><i class="fas fa-lock"></i> Secured by 256-bit encryption</p>
                </div>
            </div>

            <!-- Modal for Advance Payment Details -->
            <div id="advancePaymentDetailsModal" class="modal">
                <div class="modal-content payment-details-modal-content">
                    <span class="close-btn">×</span>
                    <h3 id="advanceDetailsTitle"></h3>
                    <form id="advancePaymentDetailsForm" method="POST" action="">
                        <input type="hidden" name="advance_amount" id="advance_amount_hidden">
                        <input type="hidden" name="payment_method" id="advancePaymentMethod">
                        <div id="advanceCreditCardDetails" class="payment-details" style="display: none;">
                            <label for="advanceCardNumber">Card Number</label>
                            <input type="text" id="advanceCardNumber" placeholder="1234 5678 9012 3456" required>
                            <label for="advanceExpiry">Expiry Date</label>
                            <input type="text" id="advanceExpiry" placeholder="MM/YY" required>
                            <label for="advanceCvv">CVV</label>
                            <input type="text" id="advanceCvv" placeholder="123" required>
                        </div>
                        <div id="advanceUpiDetails" class="payment-details" style="display: none;">
                            <label for="advanceUpiId">UPI ID</label>
                            <input type="text" id="advanceUpiId" placeholder="example@upi" required>
                        </div>
                        <div id="advanceNetBankingDetails" class="payment-details" style="display: none;">
                            <label for="advanceBank">Select Bank</label>
                            <select id="advanceBank" required>
                                <option value="">-- Select Bank --</option>
                                <option value="SBI">State Bank of India</option>
                                <option value="HDFC">HDFC Bank</option>
                                <option value="ICICI">ICICI Bank</option>
                            </select>
                        </div>
                        <button type="submit" class="pay-now-btn" id="advancePayNowBtn">Pay Now</button>
                    </form>
                </div>
            </div>

            <!-- Success Modal -->
            <div id="successModal" class="modal">
                <div class="modal-content success-modal-content">
                    <i class="fas fa-check-circle"></i>
                    <h3>Payment Successful!</h3>
                    <p>Your payment has been processed successfully.</p>
                </div>
            </div>

            <h2>Maintenance Transaction History</h2>
            <?php if (!empty($payment_details)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Amount</th>
                            <th>Mode</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Download Slip</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $totalMaintenance = 0;
                        foreach ($payment_details as $payment): 
                            $date = new DateTime($payment['date']);
                            $formattedDate = $date->format('d-m-Y'); 
                            $formattedTime = date('g:i A', strtotime($payment['time'])); 
                            $totalMaintenance += $payment['amount']; 
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['name']); ?></td>
                                <td><?php echo htmlspecialchars('₹' . number_format($payment['amount'], 2)); ?></td>
                                <td><?php echo htmlspecialchars($payment['mode']); ?></td>
                                <td><?php echo htmlspecialchars($formattedDate); ?></td>
                                <td><?php echo htmlspecialchars($formattedTime); ?></td>
                                <td><a href="#" class="download-btn">Download</a></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr>
                            <td><strong>Total Maintenance:</strong></td>
                            <td><strong><?php echo '₹' . number_format($totalMaintenance, 2); ?></strong></td>
                            <td colspan="4"></td>
                        </tr>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No maintenance payment details available.</p>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>

    <script>
    const advanceModal = document.getElementById('advanceModal');
    const advanceBtn = document.getElementById('advancePayBtn');
    const advanceCloseBtn = advanceModal.getElementsByClassName('close-btn')[0];
    const proceedToAdvancePayment = document.getElementById('proceedToAdvancePayment');
    const advancePaymentMethodModal = document.getElementById('advancePaymentMethodModal');
    const advancePaymentMethodCloseBtn = advancePaymentMethodModal.getElementsByClassName('close-btn')[0];
    const proceedToAdvanceDetails = document.getElementById('proceedToAdvanceDetails');
    const advancePaymentDetailsModal = document.getElementById('advancePaymentDetailsModal');
    const advancePaymentDetailsCloseBtn = advancePaymentDetailsModal.getElementsByClassName('close-btn')[0];
    const advanceAmountInput = document.getElementById('advance_amount_input');
    const advanceAmountHidden = document.getElementById('advance_amount_hidden');
    const advancePayNowBtn = document.getElementById('advancePayNowBtn');
    const advancePaymentMethod = document.getElementById('advancePaymentMethod');
    const advanceDetailsTitle = document.getElementById('advanceDetailsTitle');

    const dueModal = document.getElementById('dueModal');
    const dueBtn = document.getElementById('duePayBtn');
    const dueCloseBtn = dueModal.getElementsByClassName('close-btn')[0];
    const proceedToDuePayment = document.getElementById('proceedToDuePayment');
    const duePaymentMethodModal = document.getElementById('duePaymentMethodModal');
    const duePaymentMethodCloseBtn = duePaymentMethodModal.getElementsByClassName('close-btn')[0];
    const proceedToDueDetails = document.getElementById('proceedToDueDetails');
    const duePaymentDetailsModal = document.getElementById('duePaymentDetailsModal');
    const duePaymentDetailsCloseBtn = duePaymentDetailsModal.getElementsByClassName('close-btn')[0];
    const duePayNowBtn = document.getElementById('duePayNowBtn');
    const duePaymentMethod = document.getElementById('duePaymentMethod');
    const dueDetailsTitle = document.getElementById('dueDetailsTitle');

    const successModal = document.getElementById('successModal');

    // Function to update the UI after payment
    function updateUI(data) {
        document.querySelector('.payment-section:nth-child(1) p:nth-child(2)').textContent = `₹${data.due_amount}`;
        document.querySelector('.payment-section:nth-child(2) p:nth-child(2)').textContent = `₹${data.advance_amount}`;
        document.querySelector('.payment-section:nth-child(2) p:nth-child(3)').textContent = `Paid On: ${data.advance_date}`;
        document.querySelector('.payment-section:nth-child(1) p:nth-child(3)').textContent = `${data.bill_text}: ${data.bill_date}`;
        
        if (parseFloat(data.due_amount) === 0) {
            document.getElementById('duePayBtn').disabled = true;
            document.getElementById('duePayBtn').textContent = 'Paid';
        } else {
            document.getElementById('duePayBtn').disabled = false;
            document.getElementById('duePayBtn').textContent = 'Pay Now';
        }

        const tbody = document.querySelector('table tbody');
        if (data.payment_history && data.payment_history.length > 0) {
            tbody.innerHTML = '';
            let totalMaintenance = 0;
            data.payment_history.forEach(payment => {
                totalMaintenance += parseFloat(payment.amount);
                const row = `
                    <tr>
                        <td>${payment.name}</td>
                        <td>₹${parseFloat(payment.amount).toFixed(2)}</td>
                        <td>${payment.mode}</td>
                        <td>${payment.date}</td>
                        <td>${payment.time}</td>
                        <td><a href="#" class="download-btn">Download</a></td>
                    </tr>`;
                tbody.insertAdjacentHTML('beforeend', row);
            });
            const totalRow = `
                <tr>
                    <td><strong>Total Maintenance:</strong></td>
                    <td><strong>₹${totalMaintenance.toFixed(2)}</strong></td>
                    <td colspan="4"></td>
                </tr>`;
            tbody.insertAdjacentHTML('beforeend', totalRow);
        }
    }

    // Show success modal if payment is successful
    <?php if (isset($_SESSION['payment_success'])): ?>
        successModal.style.display = 'flex';
        setTimeout(() => successModal.classList.add('active'), 10);
        setTimeout(() => {
            successModal.classList.remove('active');
            setTimeout(() => successModal.style.display = 'none', 300);
        }, 3000);
        <?php unset($_SESSION['payment_success']); ?>
    <?php endif; ?>

    // Open Advance Modal
    advanceBtn.onclick = function() {
        advanceModal.style.display = 'flex';
        setTimeout(() => advanceModal.classList.add('active'), 10);
    }

    // Close Advance Modal
    advanceCloseBtn.onclick = function() {
        advanceModal.classList.remove('active');
        setTimeout(() => advanceModal.style.display = 'none', 300);
    }

    // Proceed to Advance Payment Method Modal
    proceedToAdvancePayment.onclick = function() {
        const amount = parseFloat(advanceAmountInput.value);
        if (isNaN(amount) || amount < 100) {
            alert('Please enter an amount of at least ₹100.');
            return;
        }
        advanceAmountHidden.value = advanceAmountInput.value;
        advanceModal.classList.remove('active');
        setTimeout(() => {
            advanceModal.style.display = 'none';
            advancePaymentMethodModal.style.display = 'flex';
            setTimeout(() => advancePaymentMethodModal.classList.add('active'), 10);
        }, 300);
    }

    // Close Advance Payment Method Modal
    advancePaymentMethodCloseBtn.onclick = function() {
        advancePaymentMethodModal.classList.remove('active');
        setTimeout(() => advancePaymentMethodModal.style.display = 'none', 300);
    }

    // Proceed to Advance Payment Details Modal
    proceedToAdvanceDetails.onclick = function() {
        const selectedMethod = document.querySelector('input[name="advance_payment_method"]:checked');
        if (!selectedMethod) {
            alert('Please select a payment method.');
            return;
        }
        advancePaymentMethod.value = selectedMethod.value;
        advanceDetailsTitle.textContent = selectedMethod.value + ' Details';
        document.getElementById('advanceCreditCardDetails').style.display = selectedMethod.value === 'Credit Card' ? 'block' : 'none';
        document.getElementById('advanceUpiDetails').style.display = selectedMethod.value === 'UPI' ? 'block' : 'none';
        document.getElementById('advanceNetBankingDetails').style.display = selectedMethod.value === 'Net Banking' ? 'block' : 'none';
        advancePaymentMethodModal.classList.remove('active');
        setTimeout(() => {
            advancePaymentMethodModal.style.display = 'none';
            advancePaymentDetailsModal.style.display = 'flex';
            setTimeout(() => advancePaymentDetailsModal.classList.add('active'), 10);
        }, 300);
    }

    // Close Advance Payment Details Modal
    advancePaymentDetailsCloseBtn.onclick = function() {
        advancePaymentDetailsModal.classList.remove('active');
        setTimeout(() => advancePaymentDetailsModal.style.display = 'none', 300);
    }

    // Handle Advance Payment via AJAX
    advancePayNowBtn.onclick = function(e) {
        e.preventDefault();
        advancePayNowBtn.classList.add('processing');
        advancePayNowBtn.textContent = 'Processing...';

        const formData = new FormData(document.getElementById('advancePaymentDetailsForm'));
        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                advancePayNowBtn.classList.remove('processing');
                advancePayNowBtn.textContent = 'Pay Now';
                advancePaymentDetailsModal.classList.remove('active');
                setTimeout(() => {
                    advancePaymentDetailsModal.style.display = 'none';
                    successModal.style.display = 'flex';
                    setTimeout(() => successModal.classList.add('active'), 10);
                    setTimeout(() => {
                        successModal.classList.remove('active');
                        setTimeout(() => successModal.style.display = 'none', 300);
                    }, 3000);
                }, 300);
                updateUI(data.data);
            } else {
                alert('Payment failed: ' + data.message);
                advancePayNowBtn.classList.remove('processing');
                advancePayNowBtn.textContent = 'Pay Now';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during payment.');
            advancePayNowBtn.classList.remove('processing');
            advancePayNowBtn.textContent = 'Pay Now';
        });
    };

    // Open Due Modal
    if (dueBtn) {
        dueBtn.onclick = function() {
            dueModal.style.display = 'flex';
            setTimeout(() => dueModal.classList.add('active'), 10);
        }
    }

    // Close Due Modal
    dueCloseBtn.onclick = function() {
        dueModal.classList.remove('active');
        setTimeout(() => dueModal.style.display = 'none', 300);
    }

    // Proceed to Due Payment Method Modal
    if (proceedToDuePayment) {
        proceedToDuePayment.onclick = function() {
            dueModal.classList.remove('active');
            setTimeout(() => {
                dueModal.style.display = 'none';
                duePaymentMethodModal.style.display = 'flex';
                setTimeout(() => duePaymentMethodModal.classList.add('active'), 10);
            }, 300);
        }
    }

    // Close Due Payment Method Modal
    duePaymentMethodCloseBtn.onclick = function() {
        duePaymentMethodModal.classList.remove('active');
        setTimeout(() => duePaymentMethodModal.style.display = 'none', 300);
    }

    // Proceed to Due Payment Details Modal
    proceedToDueDetails.onclick = function() {
        const selectedMethod = document.querySelector('input[name="due_payment_method"]:checked');
        if (!selectedMethod) {
            alert('Please select a payment method.');
            return;
        }
        duePaymentMethod.value = selectedMethod.value;
        dueDetailsTitle.textContent = selectedMethod.value + ' Details';
        document.getElementById('dueCreditCardDetails').style.display = selectedMethod.value === 'Credit Card' ? 'block' : 'none';
        document.getElementById('dueUpiDetails').style.display = selectedMethod.value === 'UPI' ? 'block' : 'none';
        document.getElementById('dueNetBankingDetails').style.display = selectedMethod.value === 'Net Banking' ? 'block' : 'none';
        duePaymentMethodModal.classList.remove('active');
        setTimeout(() => {
            duePaymentMethodModal.style.display = 'none';
            duePaymentDetailsModal.style.display = 'flex';
            setTimeout(() => duePaymentDetailsModal.classList.add('active'), 10);
        }, 300);
    }

    // Close Due Payment Details Modal
    duePaymentDetailsCloseBtn.onclick = function() {
        duePaymentDetailsModal.classList.remove('active');
        setTimeout(() => duePaymentDetailsModal.style.display = 'none', 300);
    }

    // Handle Due Payment via AJAX
    duePayNowBtn.onclick = function(e) {
        e.preventDefault();
        duePayNowBtn.classList.add('processing');
        duePayNowBtn.textContent = 'Processing...';

        const formData = new FormData(document.getElementById('duePaymentDetailsForm'));
        fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                duePayNowBtn.classList.remove('processing');
                duePayNowBtn.textContent = 'Pay Now';
                duePaymentDetailsModal.classList.remove('active');
                setTimeout(() => {
                    duePaymentDetailsModal.style.display = 'none';
                    successModal.style.display = 'flex';
                    setTimeout(() => successModal.classList.add('active'), 10);
                    setTimeout(() => {
                        successModal.classList.remove('active');
                        setTimeout(() => successModal.style.display = 'none', 300);
                    }, 3000);
                }, 300);
                updateUI(data.data);
            } else {
                alert('Payment failed: ' + data.message);
                duePayNowBtn.classList.remove('processing');
                duePayNowBtn.textContent = 'Pay Now';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred during payment.');
            duePayNowBtn.classList.remove('processing');
            duePayNowBtn.textContent = 'Pay Now';
        });
    };

    // Close modals when clicking outside
    window.onclick = function(event) {
        if (event.target == advanceModal) {
            advanceModal.classList.remove('active');
            setTimeout(() => advanceModal.style.display = 'none', 300);
        }
        if (event.target == advancePaymentMethodModal) {
            advancePaymentMethodModal.classList.remove('active');
            setTimeout(() => advancePaymentMethodModal.style.display = 'none', 300);
        }
        if (event.target == advancePaymentDetailsModal) {
            advancePaymentDetailsModal.classList.remove('active');
            setTimeout(() => advancePaymentDetailsModal.style.display = 'none', 300);
        }
        if (event.target == dueModal) {
            dueModal.classList.remove('active');
            setTimeout(() => dueModal.style.display = 'none', 300);
        }
        if (event.target == duePaymentMethodModal) {
            duePaymentMethodModal.classList.remove('active');
            setTimeout(() => duePaymentMethodModal.style.display = 'none', 300);
        }
        if (event.target == duePaymentDetailsModal) {
            duePaymentDetailsModal.classList.remove('active');
            setTimeout(() => duePaymentDetailsModal.style.display = 'none', 300);
        }
        if (event.target == successModal) {
            successModal.classList.remove('active');
            setTimeout(() => successModal.style.display = 'none', 300);
        }
    }
    </script>
</body>
</html>