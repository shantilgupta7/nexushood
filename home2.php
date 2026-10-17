<?php
session_start();
if (!isset($_SESSION['room_no'])) {
    header("Location: sign-in.php");
    exit();
}

$payment_details = isset($_SESSION['payment_details']) ? $_SESSION['payment_details'] : [];
$complaint_details = isset($_SESSION['complaint_details']) ? $_SESSION['complaint_details'] : [];

date_default_timezone_set('Asia/Kolkata');

// Database connection using PDO
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

// Handle payment submission via AJAX
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['pay_contribution'])) {
    $room_no = $_SESSION['room_no'];
    $name = $_SESSION['fh_name'];
    $purpose = $_POST['purpose'];
    $amount = floatval($_POST['amount']);
    $mode = $_POST['payment_method'];
    $date = date("Y-m-d");
    $time = date("H:i:s");

    if ($amount <= 0) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Amount must be greater than zero']);
        exit();
    }

    $stmt = $conn->prepare("INSERT INTO payment (room_no, name, purpose, mode, amount, date, time) 
                           VALUES (:room_no, :name, :purpose, :mode, :amount, :date, :time)");
    $stmt->execute([
        ':room_no' => $room_no,
        ':name' => $name,
        ':purpose' => $purpose,
        ':mode' => $mode,
        ':amount' => $amount,
        ':date' => $date,
        ':time' => $time
    ]);

    // Fetch updated contribution data
    $sql = "SELECT room_no, purpose, amount, date FROM payment";
    $stmt = $conn->query($sql);
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_contribution = array_sum(array_column($contributions, 'amount'));
    $event_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Event Contribution'; }), 'amount'));
    $repair_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Repair Fund'; }), 'amount'));
    $misc_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Miscellaneous'; }), 'amount'));

    // Fetch expense data
    $expense_sql = "SELECT category, purpose, amount, date FROM expense";
    $expense_stmt = $conn->query($expense_sql);
    $expenses = $expense_stmt->fetchAll(PDO::FETCH_ASSOC);

    $event_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'event'; }), 'amount'));
    $repair_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'repair'; }), 'amount'));
    $misc_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'miscellaneous'; }), 'amount'));

    $event_available = $event_total - $event_expense_total;
    $repair_available = $repair_total - $repair_expense_total;
    $misc_available = $misc_total - $misc_expense_total;

    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Payment processed successfully',
        'data' => [
            'total_contribution' => number_format($total_contribution, 2),
            'event_total' => number_format($event_total, 2),
            'repair_total' => number_format($repair_total, 2),
            'misc_total' => number_format($misc_total, 2),
            'event_expense' => number_format($event_expense_total, 2),
            'repair_expense' => number_format($repair_expense_total, 2),
            'misc_expense' => number_format($misc_expense_total, 2),
            'event_available' => number_format($event_available, 2),
            'repair_available' => number_format($repair_available, 2),
            'misc_available' => number_format($misc_available, 2),
            'contributions' => $contributions,
            'expenses' => $expenses
        ]
    ]);
    exit();
}

// Initial fetch of contributions
$sql = "SELECT room_no, purpose, amount, date FROM payment";
$stmt = $conn->query($sql);
$contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_contribution = array_sum(array_column($contributions, 'amount'));
$event_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Event Contribution'; }), 'amount'));
$repair_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Repair Fund'; }), 'amount'));
$misc_total = array_sum(array_column(array_filter($contributions, function($c) { return $c['purpose'] === 'Miscellaneous'; }), 'amount'));

// Fetch expense records
$expense_sql = "SELECT category, purpose, amount, date FROM expense";
$expense_stmt = $conn->query($expense_sql);
$expenses = $expense_stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate expense totals
$event_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'event'; }), 'amount'));
$repair_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'repair'; }), 'amount'));
$misc_expense_total = array_sum(array_column(array_filter($expenses, function($e) { return $e['category'] === 'miscellaneous'; }), 'amount'));

// Calculate available amounts
$event_available = $event_total - $event_expense_total;
$repair_available = $repair_total - $repair_expense_total;
$misc_available = $misc_total - $misc_expense_total;

// Fetch complaints for the current month
$current_month = date('Y-m');
$sql = "SELECT room_no, description, date, time, reply, image_path, video_path, resolve_note 
        FROM complaint 
        WHERE DATE_FORMAT(date, '%Y-%m') = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$current_month]);
$complaints = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Maintenance data fetch based on building
$room_no = $_SESSION['room_no'];
$building = (stripos($room_no, 'A') === 0) ? 'A' : 'BCD';

if ($building === 'A') {
    $maintenance_sql = "SELECT facility, amount, total_maintenance, num_flats 
                       FROM building_a_flat_maintenance";
    $modal_title = "Building A Maintenance Chart (Per Flat)";
} else {
    $maintenance_sql = "SELECT facility, amount, total_maintenance, num_flats 
                       FROM building_bcd_flat_maintenance";
    $modal_title = "Buildings B, C, D Maintenance Chart (Per Flat)";
}

$stmt = $conn->query($maintenance_sql);
$total_maintenance = 0;
$num_flats = 0;
$maintenance_data = [];

if ($stmt && $stmt->rowCount() > 0) {
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $maintenance_data[] = $row;
        $total_maintenance += $row['amount'];
        $num_flats = $row['num_flats'];
    }
}
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
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

    <style>
        /* Existing styles remain unchanged */
        .table-container {
            overflow-x: auto;
            margin-top: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .maintenance-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Inter', sans-serif;
        }

        .maintenance-table th,
        .maintenance-table td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .maintenance-table th {
            background: linear-gradient(90deg, #4169E1, #00246b);
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .maintenance-table td {
            background: #fff;
            color: #333;
            font-size: 15px;
            transition: background 0.3s ease;
        }

        .maintenance-table tr:hover td {
            background: #f0f4ff;
            color: black;
        }

        .maintenance-table tfoot td {
            background: linear-gradient(90deg, #4CAF50);
            color: #fff;
            font-weight: 700;
            font-size: 16px;
        }

        .maintenance-table tbody tr:nth-child(even) {
            background: #fafafa;
        }

        .maintenance-table tbody tr:nth-child(odd) {
            background: #fff;
        }

        .modal-content h2 {
            text-align: center;
            color: #4169E1;
            font-size: 30px;
            margin-bottom: 15px;
            font-weight: 700;
            border-bottom: 3px solid #4169E1;
            padding-bottom: 10px;
        }

        .modal-subtitle {
            text-align: center;
            color: #666;
            font-size: 1.1em;
            margin-bottom: 20px;
            font-weight: 500;
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
        @keyframes spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        .icon-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            margin-top: 50px;
            max-width: 1200px;
            margin: 50px auto;
        }
        .icon-item {
            text-align: center;
            width: 30%;
            min-width: 250px;
            height: 100px;
            padding: 20px;
            background: #4169E1;
            border-radius: 20px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;
            cursor: pointer;
        }
        .icon-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
        }
        .icon-item ion-icon {
            font-size: 3.5rem;
            color: #fff;
            margin-right: 20px;
        }
        .icon-item p {
            margin: 0;
            font-size: 1.3rem;
            color: #fff !important;
            font-weight: bold;
        }
        .icon-item a {
            text-decoration: none;
            display: flex;
            flex-direction: row;
            justify-content: flex-start;
            align-items: center;
            width: 100%;
            height: 100%;
        }
        .icon-item:hover ion-icon {
            color: #e0e0e0;
        }
        .user-info p {
            font-weight: bold;
            margin: 0;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.6);
        }
        .modal-content {
            background: #ffffff;
            margin: 5% auto;
            padding: 30px;
            border: none;
            width: 80%;
            max-width: 1000px;
            border-radius: 15px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: slideIn 0.3s ease-in-out;
        }
        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .close {
            color: #4169E1;
            float: right;
            font-size: 36px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close:hover,
        .close:focus {
            color: #00246b;
        }
        .contribution-modal-content {
            background: linear-gradient(135deg, #ffffff, #f0f4f8);
            margin: 5% auto;
            padding: 20px;
            border-radius: 15px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            transform: scale(0.9);
            animation: slideIn 0.3s ease-out forwards;
        }
        .contribution-modal-content h2 {
            color: #00246b;
            font-size: 1.8em;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .modal-subtitle {
            text-align: center;
            color: #666;
            font-size: 1em;
            margin-bottom: 20px;
        }
        .contribution-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .form-group label {
            font-size: 14px;
            font-weight: 600;
            color: #333;
        }
        .form-group select,
        .form-group input[type="number"] {
            padding: 10px;
            font-size: 14px;
            border: 2px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-group select:focus,
        .form-group input[type="number"]:focus {
            border-color: #4169E1;
            box-shadow: 0 0 8px rgba(65, 105, 225, 0.3);
        }
        .form-group select {
            appearance: none;
            background: url('data:image/svg+xml;utf8,<svg fill="%234169E1" height="20" viewBox="0 0 24 24" width="20" xmlns="http://www.w3.org/2000/svg"><path d="M7 10l5 5 5-5z"/></svg>') no-repeat right 8px center;
            background-size: 18px;
        }
        .hidden {
            display: none;
        }
        .pay-btn {
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
        .pay-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
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
        .payment-details-modal-content h3 {
            color: #00246b;
            font-size: 1.8em;
            text-align: center;
            margin-bottom: 15px;
            font-weight: 700;
        }
        .payment-details-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .payment-details-form input[type="text"],
        .payment-details-form input[type="password"],
        .payment-details-form select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 1.1em;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .payment-details-form input[type="text"]:focus,
        .payment-details-form input[type="password"]:focus,
        .payment-details-form select:focus {
            border-color: #4169E1;
            box-shadow: 0 0 8px rgba(65, 105, 225, 0.3);
            outline: none;
        }
        .pay-now-btn {
            padding: 12px;
            background: linear-gradient(90deg, #4CAF50, #45a049);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1.2em;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.3s;
            width: 100%;
            margin-top: 20px;
        }
        .pay-now-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(76, 175, 80, 0.4);
        }
        .success-modal-content {
            text-align: center;
            padding: 20px;
            background: #fff;
            border-radius: 15px;
            width: 400px;
            height: 200px;
            max-width: 90%;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            position: relative;
            animation: slideIn 0.3s ease-out forwards;
        }
        .success-modal-content i {
            font-size: 2.5em;
            color: #4CAF50;
            margin-bottom: 10px;
        }
        .success-modal-content h3 {
            color: #333;
            font-size: 1.5em;
            margin-bottom: 5px;
        }
        .success-modal-content p {
            color: #666;
            font-size: 1em;
        }
        .complaint-container {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 20px;
        }
        .complaint-column, .status-column {
            width: 48%;
            background: #f9f9f9;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        .scrollable-table {
            max-height: 350px;
            overflow-y: auto;
            overflow-x: auto;
            display: none;
        }
        .complaint-table {
            width: 100%;
            min-width: 600px;
            border-collapse: collapse;
        }
        .complaint-table th, 
        .complaint-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .complaint-table th {
            background: #4169E1;
            color: white;
            position: sticky;
            top: 0;
            z-index: 1;
            font-weight: 600;
        }
        .complaint-table td {
            background: #fff;
            color: #333;
            font-size: 14px;
        }
        .complaint-table tr:hover td {
            background: #eef2ff;
            transition: background 0.3s ease;
        }
        .status-section {
            margin-top: 25px;
        }
        .status-section h3, .complaint-column h3 {
            color: #4169E1;
            font-size: 22px;
            margin-bottom: 15px;
            font-weight: 700;
            padding: 10px 15px;
            background: #eef2ff;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .status-section h3:hover, .complaint-column h3:hover {
            background: #d6e0ff;
        }
        .toggle-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }
        .toggle-icon.open {
            transform: rotate(180deg);
        }
        .contribution-summary {
            margin: 20px 0;
            padding: 20px;
            background: #f9f9f9;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .contribution-summary .total {
            font-size: 1.5em;
            font-weight: 700;
            color: #00246b;
            text-align: center;
            margin-bottom: 20px;
            background: linear-gradient(90deg, #4169E1, #00246b);
            color: white;
            padding: 10px;
            border-radius: 8px;
        }
        .category-totals {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-around;
            gap: 15px;
        }
        .category-box {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            width: 30%;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .category-box:hover {
            transform: translateY(-5px);
        }
        .category-box h4 {
            font-size: 1.2em;
            color: #4169E1;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .category-box p {
            margin: 5px 0;
            font-size: 1em;
            color: #333;
        }
        .category-box .amount-collected {
            color: #4CAF50;
            font-weight: 600;
        }
        .category-box .amount-spent {
            color: #FF5733;
            font-weight: 600;
        }
        .category-box .amount-available {
            color: #4169E1;
            font-weight: 600;
        }
        .category-buttons {
            margin: 20px 0;
            display: flex;
            justify-content: center;
            gap: 15px;
        }
        .category-buttons button {
            padding: 10px 20px;
            background: #4169E1;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .category-buttons button:hover {
            background: #00246b;
        }
        .category-buttons button.active {
            background: #00246b;
            font-weight: bold;
        }

        /* New Society Services Section Styling */
        .services-section {
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 20px;
            text-align: center;
        }
        .services-section h2 {
            text-align: center;
            color: #4169E1;
            font-size: 2.5em;
            font-weight: 700;
            margin-bottom: 40px;
            border-bottom: 3px solid #4169E1;
            display: inline-block;
            padding-bottom: 10px;
        }
        .services-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 25px;
            margin-bottom: 30px;
        }
        .service-card {
            width: 30%;
            min-width: 250px;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            text-align: center;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
        }
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }
        .service-card ion-icon {
            font-size: 3rem;
            color: #4169E1;
            margin-bottom: 15px;
        }
        .service-card h3 {
            font-size: 1.5em;
            color: #00246b;
            margin-bottom: 10px;
            font-weight: 600;
        }
        .service-card p {
            font-size: 1em;
            color: #666;
            margin-bottom: 15px;
        }
        .service-card .contact-number {
            font-size: 1.1em;
            color: #4CAF50;
            font-weight: 600;
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
                        <li class="nav-item"><a class="nav-link active" href="home2.php"><i class="fas fa-home"></i>Home</a></li>
                        <li class="nav-item"><a class="nav-link" href="m_details.php"><i class="fas fa-wallet"></i>Maintenance</a></li>
                        <li class="nav-item"><a class="nav-link" href="c_details.php"><i class="fas fa-comments"></i>Complaint</a></li>
                        <li class="nav-item"><a class="nav-link" href="about2.php"><i class="fas fa-info-circle"></i>About</a></li>
                        <li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out"></i>Sign-out</a></li>
                    </ul>
                </div>
            </div>
        </nav>

        <section class="slick-slideshow">
            <div class="slick-custom">
                <img src="images/slideshow/s2.jpg" class="img-fluid" alt="Slideshow Image 1">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slick-custom">
                <img src="images/slideshow/s12.jpg" class="img-fluid" alt="Slideshow Image 2">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="slick-custom">
                <img src="images/slideshow/s4.jpg" class="img-fluid" alt="Slideshow Image 3">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="icon-container">
            <div class="icon-item"><a href="m_details.php" title="Navigate to pay maintenance"><ion-icon name="cash-outline"></ion-icon><p>Pay Maintenance</p></a></div>
            <div class="icon-item" onclick="openContributionModal()" title="Open contribution modal"><ion-icon name="heart-outline"></ion-icon><p>Contribution</p></div>
            <div class="icon-item"><a href="c_details.php" title="Navigate to complaints"><ion-icon name="chatbubbles-outline"></ion-icon><p>Complaint</p></a></div>
            <div class="icon-item" onclick="openModal()" title="Open maintenance chart modal"><ion-icon name="clipboard-outline"></ion-icon><p>Maintenance Chart</p></div>
            <div class="icon-item" onclick="openContributionChartModal()" title="Open contribution chart modal"><ion-icon name="bar-chart-outline"></ion-icon><p>Contribution Chart</p></div>
            <div class="icon-item" onclick="openResolveComplaintModal()" title="Open resolve complaint modal"><ion-icon name="checkmark-done-outline"></ion-icon><p>Resolve Complaint</p></div>
        </div>

        <!-- New Society Services Section -->
        <section class="services-section">
            <h2>Services</h2>
            <div class="services-container">
                <div class="service-card">
                    <ion-icon name="trash-outline"></ion-icon>
                    <h3>Raddi Wala</h3>
                    <p>Contact our trusted scrap dealer for hassle-free disposal of newspapers, cardboard, and more.</p>
                    <p class="contact-number">+91 98765 43210</p>
                </div>
                <div class="service-card">
                    <ion-icon name="construct-outline"></ion-icon>
                    <h3>Plumber</h3>
                    <p>Quick and reliable plumbing services for leaks, pipe repairs, and installations.</p>
                    <p class="contact-number">+91 91234 56789</p>
                </div>
                <div class="service-card">
                    <ion-icon name="bulb-outline"></ion-icon>
                    <h3>Electrician</h3>
                    <p>Expert electrician for wiring, repairs, and electrical maintenance in your home.</p>
                    <p class="contact-number">+91 99887 66554</p>
                </div>
                <div class="service-card">
                    <ion-icon name="brush-outline"></ion-icon>
                    <h3>Painter</h3>
                    <p>Professional painting services to refresh your home with quality finishes.</p>
                    <p class="contact-number">+91 97654 32109</p>
                </div>
                <div class="service-card">
                    <ion-icon name="hammer-outline"></ion-icon>
                    <h3>Carpenter</h3>
                    <p>Skilled carpenter for furniture repairs, custom designs, and woodwork.</p>
                    <p class="contact-number">+91 96543 21098</p>
                </div>
                <div class="service-card">
                    <ion-icon name="water-outline"></ion-icon>
                    <h3>Water Tank Cleaning</h3>
                    <p>Ensure clean and safe water with our tank cleaning service.</p>
                    <p class="contact-number">+91 94321 09876</p>
                </div>
            </div>
        </section>

        <div id="maintenanceModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeModal()" title="Close maintenance modal">×</span>
                <h2><?php echo $modal_title; ?></h2>
                <p class="modal-subtitle"><strong>Number of Flats:</strong> <?php echo $num_flats > 0 ? $num_flats : 'Not specified'; ?></p>
                <div class="table-container">
                    <table class="maintenance-table">
                        <thead>
                            <tr>
                                <th>Facility</th>
                                <th>Per Flat Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($maintenance_data) && $num_flats > 0) : foreach ($maintenance_data as $row) : ?>
                                <tr>
                                    <td><?php echo ucfirst($row['facility']); ?></td>
                                    <td>₹<?php echo number_format($row['amount'] / $num_flats, 2); ?></td>
                                </tr>
                            <?php endforeach; else : ?>
                                <tr>
                                    <td colspan="2"><?php echo $num_flats == 0 ? "Number of flats not specified" : "No maintenance records found"; ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td>Total Per Flat Maintenance</td>
                                <td>₹<?php echo $num_flats > 0 ? number_format($total_maintenance / $num_flats, 2) : 'N/A'; ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div id="contributionModal" class="modal">
            <div class="modal-content contribution-modal-content">
                <span class="close" onclick="closeContributionModal()" title="Close contribution modal">×</span>
                <h2>Make a Contribution</h2>
                <p class="modal-subtitle">Support your community!</p>
                <form id="contributionForm" class="contribution-form">
                    <input type="text" name="room_no" value="<?php echo htmlspecialchars($_SESSION['room_no']); ?>" class="hidden" title="Room number (hidden)">
                    <input type="text" name="name" value="<?php echo htmlspecialchars($_SESSION['fh_name']); ?>" class="hidden" title="User name (hidden)">
                    <div class="form-group">
                        <label for="purpose">Purpose</label>
                        <select name="purpose" id="purpose" required title="Select the purpose of your contribution">
                            <option value="">Select Purpose</option>
                            <option value="Event Contribution">Event Contribution</option>
                            <option value="Repair Fund">Repair Fund</option>
                            <option value="Miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="amount">Amount (₹)</label>
                        <input type="number" name="amount" id="amount" min="1" placeholder="Enter amount" required title="Enter the contribution amount in Indian Rupees">
                    </div>
                    <button type="button" onclick="proceedToPaymentMethod()" class="pay-btn" title="Proceed to payment method selection">Pay Contribution</button>
                </form>
            </div>
        </div>

        <div id="paymentMethodModal" class="modal">
            <div class="modal-content payment-method-modal-content">
                <span class="close" onclick="closePaymentMethodModal()" title="Close payment method modal">×</span>
                <h3>Select Payment Method</h3>
                <p class="modal-subtitle">Choose a secure payment option</p>
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Credit Card" required title="Select credit/debit card payment method">
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
                        <input type="radio" name="payment_method" value="UPI" title="Select UPI payment method">
                        <div class="payment-card">
                            <i class="fas fa-mobile-alt payment-icon"></i>
                            <span>UPI</span>
                            <div class="card-brands">
                                <img src="https://upload.wikimedia.org/wikipedia/commons/f/f2/Google_Pay_Logo.svg" alt="Google Pay" class="brand-logo">
                            </div>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="payment_method" value="Net Banking" title="Select net banking payment method">
                        <div class="payment-card">
                            <i class="fas fa-university payment-icon"></i>
                            <span>Net Banking</span>
                            <div class="card-brands">
                                <span class="bank-text">SBI, HDFC, ICICI & more</span>
                            </div>
                        </div>
                    </label>
                </div>
                <button id="proceedToDetails" class="pay-now-btn" title="Proceed to enter payment details">Proceed</button>
                <p class="security-note"><i class="fas fa-lock"></i> Secured by 256-bit encryption</p>
            </div>
        </div>

        <div id="paymentDetailsModal" class="modal">
            <div class="modal-content payment-details-modal-content">
                <span class="close" onclick="closePaymentDetailsModal()" title="Close payment details modal">×</span>
                <h3 id="paymentDetailsTitle"></h3>
                <form id="paymentDetailsForm" class="payment-details-form">
                    <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($_SESSION['room_no']); ?>" title="Room number (hidden)">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($_SESSION['fh_name']); ?>" title="User name (hidden)">
                    <input type="hidden" name="purpose" id="paymentPurpose" title="Purpose of payment (hidden)">
                    <input type="hidden" name="amount" id="paymentAmount" title="Payment amount (hidden)">
                    <input type="hidden" name="payment_method" id="paymentMethod" title="Selected payment method (hidden)">
                    <input type="hidden" name="pay_contribution" value="1" title="Payment submission flag (hidden)">
                    <div id="creditCardDetails" class="payment-details" style="display: none;">
                        <label for="cardNumber">Card Number</label>
                        <input type="text" id="cardNumber" name="card_number" placeholder="1234 5678 9012 3456" required title="Enter your 16-digit credit/debit card number">
                        <label for="expiry">Expiry Date</label>
                        <input type="text" id="expiry" name="expiry" placeholder="MM/YY" required title="Enter the expiry date of your card (MM/YY format)">
                        <label for="cvv">CVV</label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" required title="Enter the 3-digit CVV code from the back of your card">
                    </div>
                    <div id="upiDetails" class="payment-details" style="display: none;">
                        <label for="upiId">UPI ID</label>
                        <input type="text" id="upiId" name="upi_id" placeholder="example@upi" required title="Enter your UPI ID (e.g., example@upi)">
                    </div>
                    <div id="netBankingDetails" class="payment-details" style="display: none;">
                        <label for="bank">Select Bank</label>
                        <select id="bank" name="bank" required title="Select your bank for net banking">
                            <option value="">-- Select Bank --</option>
                            <option value="SBI">State Bank of India</option>
                            <option value="HDFC">HDFC Bank</option>
                            <option value="ICICI">ICICI Bank</option>
                        </select>
                        <label for="netBankingUserId">User ID</label>
                        <input type="text" id="netBankingUserId" name="user_id" placeholder="Enter User ID" required title="Enter your net banking user ID">
                        <label for="netBankingPassword">Password</label>
                        <input type="password" id="netBankingPassword" name="password" placeholder="Enter Password" required title="Enter your net banking password">
                    </div>
                    <button type="button" id="payNowBtn" class="pay-now-btn" title="Submit the payment details">Pay Now</button>
                </form>
            </div>
        </div>

        <div id="successModal" class="modal">
            <div class="modal-content success-modal-content">
                <i class="fas fa-check-circle"></i>
                <h3>Payment Successful!</h3>
                <p>Your contribution has been processed successfully.</p>
            </div>
        </div>

        <div id="contributionChartModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeContributionChartModal()" title="Close contribution chart modal">×</span>
                <h2>Contribution Chart - All Records</h2>
                <div class="contribution-summary">
                    <div class="total">Total Contribution: ₹<span id="totalContribution"><?php echo number_format($total_contribution, 2); ?></span></div>
                    <div class="category-totals">
                        <div class="category-box">
                            <h4>Event Contribution</h4>
                            <p>Collected: <span class="amount-collected">₹<span id="eventTotal"><?php echo number_format($event_total, 2); ?></span></span></p>
                            <p>Spent: <span class="amount-spent">₹<span id="eventExpense"><?php echo number_format($event_expense_total, 2); ?></span></span></p>
                            <p>Available: <span class="amount-available">₹<span id="eventAvailable"><?php echo number_format($event_available, 2); ?></span></span></p>
                        </div>
                        <div class="category-box">
                            <h4>Repair Fund</h4>
                            <p>Collected: <span class="amount-collected">₹<span id="repairTotal"><?php echo number_format($repair_total, 2); ?></span></span></p>
                            <p>Spent: <span class="amount-spent">₹<span id="repairExpense"><?php echo number_format($repair_expense_total, 2); ?></span></span></p>
                            <p>Available: <span class="amount-available">₹<span id="repairAvailable"><?php echo number_format($repair_available, 2); ?></span></span></p>
                        </div>
                        <div class="category-box">
                            <h4>Miscellaneous</h4>
                            <p>Collected: <span class="amount-collected">₹<span id="miscTotal"><?php echo number_format($misc_total, 2); ?></span></span></p>
                            <p>Spent: <span class="amount-spent">₹<span id="miscExpense"><?php echo number_format($misc_expense_total, 2); ?></span></span></p>
                            <p>Available: <span class="amount-available">₹<span id="miscAvailable"><?php echo number_format($misc_available, 2); ?></span></span></p>
                        </div>
                    </div>
                </div>
                <div class="category-buttons">
                    <button onclick="showCategory('event')" class="active" title="View event expenses">Event Expenses</button>
                    <button onclick="showCategory('repair')" title="View repair expenses">Repair Expenses</button>
                    <button onclick="showCategory('misc')" title="View miscellaneous expenses">Miscellaneous Expenses</button>
                </div>
                <div class="scrollable-table" id="event-table">
                    <table class="complaint-table">
                        <thead><tr><th>Purpose</th><th>Amount (₹)</th><th>Date</th></tr></thead>
                        <tbody id="eventExpenseTableBody">
                            <?php $event_expense_found = false; foreach ($expenses as $expense) : if ($expense['category'] === 'event') : $event_expense_found = true; ?>
                                <tr><td><?php echo htmlspecialchars($expense['purpose']); ?></td><td><?php echo number_format($expense['amount'], 2); ?></td><td><?php echo htmlspecialchars($expense['date']); ?></td></tr>
                            <?php endif; endforeach; if (!$event_expense_found) : ?><tr><td colspan="3">No Society Event expenses found</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="scrollable-table" id="repair-table" style="display: none;">
                    <table class="complaint-table">
                        <thead><tr><th>Purpose</th><th>Amount (₹)</th><th>Date</th></tr></thead>
                        <tbody id="repairExpenseTableBody">
                            <?php $repair_expense_found = false; foreach ($expenses as $expense) : if ($expense['category'] === 'repair') : $repair_expense_found = true; ?>
                                <tr><td><?php echo htmlspecialchars($expense['purpose']); ?></td><td><?php echo number_format($expense['amount'], 2); ?></td><td><?php echo htmlspecialchars($expense['date']); ?></td></tr>
                            <?php endif; endforeach; if (!$repair_expense_found) : ?><tr><td colspan="3">No Repair expenses found</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="scrollable-table" id="misc-table" style="display: none;">
                    <table class="complaint-table">
                        <thead><tr><th>Purpose</th><th>Amount (₹)</th><th>Date</th></tr></thead>
                        <tbody id="miscExpenseTableBody">
                            <?php $misc_expense_found = false; foreach ($expenses as $expense) : if ($expense['category'] === 'miscellaneous') : $misc_expense_found = true; ?>
                                <tr><td><?php echo htmlspecialchars($expense['purpose']); ?></td><td><?php echo number_format($expense['amount'], 2); ?></td><td><?php echo htmlspecialchars($expense['date']); ?></td></tr>
                            <?php endif; endforeach; if (!$misc_expense_found) : ?><tr><td colspan="3">No Miscellaneous expenses found</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="resolveComplaintModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeResolveComplaintModal()" title="Close resolve complaint modal">×</span>
                <h2>Complaints - <?php echo date('F Y'); ?></h2>
                <div class="complaint-container">
                    <div class="complaint-column">
                        <h3>Current Month Complaints</h3>
                        <div class="scrollable-table" id="current-complaints-table">
                            <table class="complaint-table">
                                <thead><tr><th>Room No</th><th>Description</th><th>Date</th></tr></thead>
                                <tbody>
                                    <?php if (!empty($complaints)) : foreach ($complaints as $complaint) : ?>
                                        <tr><td><?php echo htmlspecialchars($complaint['room_no']); ?></td><td><?php echo htmlspecialchars($complaint['description']); ?></td><td><?php echo htmlspecialchars($complaint['date']); ?></td></tr>
                                    <?php endforeach; else : ?>
                                        <tr><td colspan="3">No complaints found for this month.</td></tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="status-column">
                        <div class="status-section">
                            <h3 onclick="toggleTable('solved-complaints-table')" title="Toggle solved complaints table">Solved Complaints <span class="toggle-icon">▼</span></h3>
                            <div class="scrollable-table" id="solved-complaints-table">
                                <table class="complaint-table">
                                    <thead><tr><th>Room No</th><th>Description</th><th>Resolve Note</th></tr></thead>
                                    <tbody>
                                        <?php $solved_complaints = array_filter($complaints, function($c) { return !empty($c['resolve_note']); }); if (!empty($solved_complaints)) : foreach ($solved_complaints as $complaint) : ?>
                                            <tr><td><?php echo htmlspecialchars($complaint['room_no']); ?></td><td><?php echo htmlspecialchars($complaint['description']); ?></td><td><?php echo htmlspecialchars($complaint['resolve_note']); ?></td></tr>
                                        <?php endforeach; else : ?><tr><td colspan="3">No solved complaints this month.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="status-section">
                            <h3 onclick="toggleTable('pending-complaints-table')" title="Toggle pending complaints table">Pending Complaints <span class="toggle-icon">▼</span></h3>
                            <div class="scrollable-table" id="pending-complaints-table">
                                <table class="complaint-table">
                                    <thead><tr><th>Room No</th><th>Description</th><th>Date</th></tr></thead>
                                    <tbody>
                                        <?php $pending_complaints = array_filter($complaints, function($c) { return empty($c['resolve_note']); }); if (!empty($pending_complaints)) : foreach ($pending_complaints as $complaint) : ?>
                                            <tr><td><?php echo htmlspecialchars($complaint['room_no']); ?></td><td><?php echo htmlspecialchars($complaint['description']); ?></td><td><?php echo htmlspecialchars($complaint['date']); ?></td></tr>
                                        <?php endforeach; else : ?><tr><td colspan="3">No pending complaints this month.</td></tr><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <section class="front-product">
            <div class="container-fluid p-0">
                <div class="row align-items-center">
                    <div class="col-lg-6 col-12">
                        <img src="images/slideshow/s8.jpg" class="img-fluid" alt="Front product image">
                    </div>
                    <div class="col-lg-6 col-12">
                        <div class="px-5 py-5 py-lg-0">
                            <h2 class="mb-4"><span>NeXusHooD</span>.com</h2>
                            <p class="lead mb-4">Our goal is to bring luxury and comfort to your doorstep, making life within the community more enjoyable, stress-free, and connected.</p>
                            <a href="about.php" class="custom-link mb-2" title="Learn more about NeXusHooD">Learn more about us <i class="bi-arrow-right ms-2"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script>
        let currentCategory = 'event';

        function openModal() { document.getElementById("maintenanceModal").style.display = "block"; }
        function closeModal() { document.getElementById("maintenanceModal").style.display = "none"; }
        function openContributionModal() { document.getElementById("contributionModal").style.display = "block"; }
        function closeContributionModal() { document.getElementById("contributionModal").style.display = "none"; }
        function closePaymentMethodModal() { document.getElementById("paymentMethodModal").style.display = "none"; document.getElementById("contributionModal").style.display = "block"; }
        function closePaymentDetailsModal() { document.getElementById("paymentDetailsModal").style.display = "none"; document.getElementById("paymentMethodModal").style.display = "block"; }
        function openContributionChartModal() { 
            document.getElementById("contributionChartModal").style.display = "block"; 
            showCategory(currentCategory); 
        }
        function closeContributionChartModal() { document.getElementById("contributionChartModal").style.display = "none"; }
        function openResolveComplaintModal() { 
            document.getElementById("resolveComplaintModal").style.display = "block"; 
            document.getElementById("current-complaints-table").style.display = "block"; 
        }
        function closeResolveComplaintModal() { document.getElementById("resolveComplaintModal").style.display = "none"; }

        function toggleTable(tableId) {
            var table = document.getElementById(tableId);
            var icon = table.previousElementSibling.querySelector('.toggle-icon');
            table.style.display = table.style.display === "block" ? "none" : "block";
            icon.classList.toggle("open", table.style.display === "block");
        }

        function showCategory(category) {
            currentCategory = category;
            document.getElementById('event-table').style.display = category === 'event' ? 'block' : 'none';
            document.getElementById('repair-table').style.display = category === 'repair' ? 'block' : 'none';
            document.getElementById('misc-table').style.display = category === 'misc' ? 'block' : 'none';
            document.querySelectorAll('.category-buttons button').forEach(btn => btn.classList.remove('active'));
            document.querySelector(`.category-buttons button:nth-child(${category === 'event' ? 1 : category === 'repair' ? 2 : 3})`).classList.add('active');
        }

        function updateContributionUI(data) {
            document.getElementById('totalContribution').textContent = data.total_contribution;
            document.getElementById('eventTotal').textContent = data.event_total;
            document.getElementById('repairTotal').textContent = data.repair_total;
            document.getElementById('miscTotal').textContent = data.misc_total;
            document.getElementById('eventExpense').textContent = data.event_expense || '0.00';
            document.getElementById('repairExpense').textContent = data.repair_expense || '0.00';
            document.getElementById('miscExpense').textContent = data.misc_expense || '0.00';
            document.getElementById('eventAvailable').textContent = data.event_available || data.event_total;
            document.getElementById('repairAvailable').textContent = data.repair_available || data.repair_total;
            document.getElementById('miscAvailable').textContent = data.misc_available || data.misc_total;

            const eventExpenseTableBody = document.getElementById('eventExpenseTableBody');
            const repairExpenseTableBody = document.getElementById('repairExpenseTableBody');
            const miscExpenseTableBody = document.getElementById('miscExpenseTableBody');

            eventExpenseTableBody.innerHTML = '';
            repairExpenseTableBody.innerHTML = '';
            miscExpenseTableBody.innerHTML = '';

            data.expenses.forEach(expense => {
                const row = `<tr>
                    <td>${expense.purpose}</td>
                    <td>${parseFloat(expense.amount).toFixed(2)}</td>
                    <td>${expense.date}</td>
                </tr>`;
                if (expense.category === 'event') {
                    eventExpenseTableBody.insertAdjacentHTML('beforeend', row);
                } else if (expense.category === 'repair') {
                    repairExpenseTableBody.insertAdjacentHTML('beforeend', row);
                } else if (expense.category === 'miscellaneous') {
                    miscExpenseTableBody.insertAdjacentHTML('beforeend', row);
                }
            });

            if (eventExpenseTableBody.children.length === 0) eventExpenseTableBody.innerHTML = '<tr><td colspan="3">No Society Event expenses found</td></tr>';
            if (repairExpenseTableBody.children.length === 0) repairExpenseTableBody.innerHTML = '<tr><td colspan="3">No Repair expenses found</td></tr>';
            if (miscExpenseTableBody.children.length === 0) miscExpenseTableBody.innerHTML = '<tr><td colspan="3">No Miscellaneous expenses found</td></tr>';
        }

        function proceedToPaymentMethod() {
            const purpose = document.getElementById("purpose").value;
            const amount = document.getElementById("amount").value;
            if (!purpose || !amount || amount < 1) {
                alert("Please select a purpose and enter a valid amount.");
                return;
            }
            document.getElementById("paymentPurpose").value = purpose;
            document.getElementById("paymentAmount").value = amount;
            document.getElementById("contributionModal").style.display = "none";
            document.getElementById("paymentMethodModal").style.display = "block";
        }

        document.getElementById("proceedToDetails").onclick = function() {
            const selectedMethod = document.querySelector('input[name="payment_method"]:checked');
            if (!selectedMethod) {
                alert("Please select a payment method.");
                return;
            }
            document.getElementById("paymentMethod").value = selectedMethod.value;
            document.getElementById("paymentDetailsTitle").textContent = selectedMethod.value + " Details";
            document.getElementById("creditCardDetails").style.display = selectedMethod.value === "Credit Card" ? "block" : "none";
            document.getElementById("upiDetails").style.display = selectedMethod.value === "UPI" ? "block" : "none";
            document.getElementById("netBankingDetails").style.display = selectedMethod.value === "Net Banking" ? "block" : "none";
            document.getElementById("paymentMethodModal").style.display = "none";
            document.getElementById("paymentDetailsModal").style.display = "block";
        };

        document.getElementById("payNowBtn").onclick = function(e) {
            e.preventDefault();
            const payNowBtn = this;
            payNowBtn.classList.add('processing');
            payNowBtn.textContent = 'Processing...';

            const formData = new FormData(document.getElementById("paymentDetailsForm"));
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                payNowBtn.classList.remove('processing');
                payNowBtn.textContent = 'Pay Now';
                if (data.success) {
                    document.getElementById("paymentDetailsModal").style.display = "none";
                    const successModal = document.getElementById("successModal");
                    successModal.style.display = "flex";
                    setTimeout(() => successModal.style.display = "none", 3000);
                    updateContributionUI(data.data);
                    document.getElementById("contributionForm").reset();
                } else {
                    alert(data.message || "Payment failed. Please try again.");
                }
            })
            .catch(error => {
                payNowBtn.classList.remove('processing');
                payNowBtn.textContent = 'Pay Now';
                console.error('Error:', error);
                alert("An error occurred. Please try again.");
            });
        };

        window.onclick = function(event) {
            const modals = ["maintenanceModal", "contributionModal", "paymentMethodModal", "paymentDetailsModal", "successModal", "resolveComplaintModal", "contributionChartModal"];
            modals.forEach(modalId => {
                if (event.target == document.getElementById(modalId)) {
                    document.getElementById(modalId).style.display = "none";
                }
            });
        };
    </script>
</body>
</html>