<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

date_default_timezone_set('Asia/Kolkata');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $room_no = $_POST['room_no'];
    $name = $_POST['name'];
    $purpose = $_POST['purpose'];
    $mode = $_POST['mode'];
    $payment_type = $_POST['paymentType'];
    $cheque_number = ($_POST['paymentType'] === 'cheque' && !empty($_POST['chequeNo'])) ? $_POST['chequeNo'] : null;
    $bank_name = ($_POST['paymentType'] === 'cheque' && !empty($_POST['bankName'])) ? $_POST['bankName'] : null;
    $amount = $_POST['amount'];
    $date = date('Y-m-d');
    $time = date('H:i:s');

    // Prepared statement for payment table
    $stmt = $conn->prepare("INSERT INTO payment (room_no, name, purpose, mode, payment_type, cheque_number, bank_name, amount, date, time) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssdss", $room_no, $name, $purpose, $mode, $payment_type, $cheque_number, $bank_name, $amount, $date, $time);
    
    if ($stmt->execute()) {
        // If mode is Advance Maint., update advance table
        if ($mode === 'Advance Maint.') {
            $check_stmt = $conn->prepare("SELECT amount FROM advance WHERE room_no = ?");
            $check_stmt->bind_param("s", $room_no);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $update_stmt = $conn->prepare("UPDATE advance SET amount = amount + ?, paid_date = ? WHERE room_no = ?");
                $update_stmt->bind_param("dss", $amount, $date, $room_no);
                $update_stmt->execute();
                $update_stmt->close();
            } else {
                $insert_stmt = $conn->prepare("INSERT INTO advance (room_no, amount, paid_date) VALUES (?, ?, ?)");
                $insert_stmt->bind_param("sds", $room_no, $amount, $date);
                $insert_stmt->execute();
                $insert_stmt->close();
            }
            $check_stmt->close();
        }
        $stmt->close();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
    $stmt->close();
}

// Fetch Society Fund data
$stmt_payment = $conn->prepare("SELECT SUM(amount) AS total_payment FROM payment WHERE mode != 'Advance Deduction'");
$stmt_payment->execute();
$result_payment = $stmt_payment->get_result();
$totalPayments = ($result_payment && $result_payment->num_rows > 0) ? $result_payment->fetch_assoc()['total_payment'] : 0;
$stmt_payment->close();

$sql_expenses = "SELECT SUM(amount) AS total_expense FROM expense";
$result_expense = $conn->query($sql_expenses);
$totalExpenses = ($result_expense && $result_expense->num_rows > 0) ? $result_expense->fetch_assoc()['total_expense'] : 0;

$societyFund = $totalPayments - $totalExpenses;
$color = $societyFund >= 0 ? "#27AE60" : "#E74C3C";
$symbol = $societyFund >= 0 ? "+ " : "- ";
$formattedTotal = number_format(abs($societyFund), 2);

// Fetch latest 200 payment records
$sql_records = "SELECT id, room_no, name, purpose, mode, payment_type, cheque_number, bank_name, amount, date, time FROM payment ORDER BY date DESC, time DESC LIMIT 200";
$result_records = $conn->query($sql_records);
$rows = [];
$totalPrice = 0;
if ($result_records && $result_records->num_rows > 0) {
    while ($row = $result_records->fetch_assoc()) {
        $rows[] = $row;
        if ($row['mode'] !== 'Advance Deduction') {
            $totalPrice += $row['amount'];
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">		
    <title>NeXusHooD.com</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/payment.css?v=<?php echo time(); ?>">	
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
    </div>

    <!-- Society Fund Container -->
    <div class="fund-accordion">
        <div class="accordion-header">
            <h2>Society Fund</h2>
            <span class="accordion-toggle">+</span>
        </div>
        <div class="accordion-content">
            <div class="fund-box">
                <span class="fund-symbol"><?php echo $symbol; ?></span>
                <span class="fund-amount" style="color: <?php echo $color; ?>;">₹<?php echo $formattedTotal; ?></span>
            </div>
        </div>
    </div>

    <!-- Form Container -->
    <div class="form-accordion">
        <div class="accordion-header">
            <h2>Add Income</h2>
           <span class="accordion-toggle">+</span>
        </div>
        <div class="accordion-content">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="room_no">Room No:</label>
                    <input type="text" id="room_no" name="room_no" required>
                </div>
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="purpose">Purpose:</label>
                    <select id="purpose" name="purpose" required>
                        <option value="">Select Purpose</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Event Contribution">Event Contribution</option>
                        <option value="Repair Fund">Repair Fund</option>
                        <option value="Miscellaneous">Miscellaneous</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="mode">Payment Mode:</label>
                    <select id="mode" name="mode" required>
                        <option value="Offline" selected>Offline</option>
                        <option value="Advance Maint.">Advance Maint.</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="paymentType">Payment Type:</label>
                    <select id="paymentType" name="paymentType" required>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div id="chequeDetails" class="form-group hidden">
                    <label for="chequeNo">Cheque Number:</label>
                    <input type="number" id="chequeNo" name="chequeNo" placeholder="Enter cheque number...">
                </div>
                <div id="chequeDetailsBank" class="form-group hidden">
                    <label for="bankName">Bank Name:</label>
                    <input type="text" id="bankName" name="bankName" placeholder="Enter bank name...">
                </div>
                <div class="form-group">
                    <label for="amount">Amount (₹):</label>
                    <input type="number" id="amount" name="amount" step="0.01" required>
                </div>
                <button type="submit">Submit</button>
            </form>
        </div>
    </div>

    <!-- Income Table -->
    <div class="container9">
        <h2>Incomes (Latest 200 Records)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Room/Shop No</th>
                        <th>Name</th>
                        <th>Purpose</th>
                        <th>Mode</th>
                        <th>Payment Type</th>
                        <th>Cheque Number</th> 
                        <th>Bank Name</th> 
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)) : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                <td><?php echo htmlspecialchars($row['mode']); ?></td>
                                <td><?php echo (in_array(strtolower($row['mode']), ['advance deduction', 'online'])) ? '-' : htmlspecialchars($row['payment_type'] ?? '-'); ?></td>
                                <td><?php echo (in_array(strtolower($row['mode']), ['advance deduction', 'online']) || $row['payment_type'] !== 'cheque') ? '-' : htmlspecialchars($row['cheque_number'] ?? '-'); ?></td>
                                <td><?php echo (in_array(strtolower($row['mode']), ['advance deduction', 'online']) || $row['payment_type'] !== 'cheque') ? '-' : htmlspecialchars($row['bank_name'] ?? '-'); ?></td>
                                <td class="amount">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date("d-m-Y", strtotime($row['date'])); ?></td>
                                <td><?php echo date("h:i A", strtotime($row['time'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="10">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="7"><strong>Total Income (Shown Records):</strong></td> <!-- Adjusted colspan -->
                        <td colspan="3">₹<?php echo number_format($totalPrice, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/payment.js?v=<?php echo time(); ?>"></script>
<script src="js/script.js?v=<?php echo time(); ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>