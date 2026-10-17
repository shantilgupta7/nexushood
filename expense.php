<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

// Set timezone
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
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['delete_range'])) {
    $purpose = $_POST['purpose'];
    $category = $_POST['category'];
    $amount = $_POST['amount'];
    $date = $_POST['date'];
    $payment_type = $_POST['paymentType'];
    $cheque_number = isset($_POST['chequeNo']) ? $_POST['chequeNo'] : null;
    $bank_name = isset($_POST['bankName']) ? $_POST['bankName'] : null;

    $sql = "INSERT INTO expense (purpose, category, amount, date, payment_type, cheque_number, bank_name) 
            VALUES ('$purpose', '$category', '$amount', '$date', '$payment_type', '$cheque_number', '$bank_name')";

    if ($conn->query($sql) === TRUE) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        $error_message = "Error: " . $conn->error;
    }
}

// Handle delete range request
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_range'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    $sql_delete = "DELETE FROM expense WHERE date BETWEEN '$start_date' AND '$end_date'";
    if ($conn->query($sql_delete) === TRUE) {
        $delete_message = "Records deleted successfully.";
    } else {
        $delete_error = "Error deleting records: " . $conn->error;
    }
}

// Fetch expense records
$sql = "SELECT * FROM expense ORDER BY date DESC, id DESC";
$result = $conn->query($sql);

$rows = [];
$totalPrice = 0;
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $rows[] = $row;
        $totalPrice += $row['amount'];
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
    <link rel="stylesheet" href="css/expense.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
    </div>

    <!-- Form Container in Accordion Style -->
    <div class="form-accordion">
        <div class="accordion-header">
            <h2>Add Expense</h2>
            <span class="accordion-toggle">+</span>
        </div>
        <div class="accordion-content">
            <?php if (isset($error_message)): ?>
                <p class="error"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="purpose">Purpose:</label>
                        <input type="text" id="purpose" name="purpose" placeholder="Enter purpose..." required>
                    </div>
                    <div class="form-group">
                        <label for="category">Category:</label>
                        <select id="category" name="category" required>
                            <option value="">Select Category</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="repair">Repairs</option>
                            <option value="event">Society Event</option>
                            <option value="utilities">Utilities (Water/Electricity)</option>
                            <option value="salary">Staff Salary</option>
                            <option value="security">Security Services</option>
                            <option value="miscellaneous">Miscellaneous</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="amount">Amount (₹):</label>
                        <input type="number" id="amount" name="amount" step="0.01" placeholder="Enter amount..." required>
                    </div>
                    <div class="form-group">
                        <label for="date">Date:</label>
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="paymentType">Payment Type:</label>
                        <select id="paymentType" name="paymentType" required>
                            <option value="cash">Cash</option>
                            <option value="cheque">Cheque</option>
                        </select>
                    </div>
                    <div class="form-group button-group">
                        <button type="submit" class="submit-btn">Submit</button>
                    </div>
                </div>
                <div class="form-row">
                    <div id="chequeDetails" class="form-group hidden">
                        <label for="chequeNo">Cheque Number:</label>
                        <input type="number" id="chequeNo" name="chequeNo" placeholder="Enter cheque number...">
                    </div>
                    <div id="chequeDetailsBank" class="form-group hidden">
                        <label for="bankName">Bank Name:</label>
                        <input type="text" id="bankName" name="bankName" placeholder="Enter bank name...">
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Expense Table -->
    <div class="container9">
        <div class="header-with-button">
            <h2>Expenses (Latest First)</h2>
            <button class="delete-btn">Delete Records</button>
        </div>
        <div class="delete-range-form" style="display: none;">
            <?php if (isset($delete_message)) echo "<p class='success'>$delete_message</p>"; ?>
            <?php if (isset($delete_error)) echo "<p class='error'>$delete_error</p>"; ?>
            <form method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="start_date">Start Date:</label>
                        <input type="date" id="start_date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label for="end_date">End Date:</label>
                        <input type="date" id="end_date" name="end_date" required>
                    </div>
                </div>
                <input type="hidden" name="delete_range" value="1">
                <div class="button-group">
                    <button type="submit" class="confirm-delete-btn">Confirm Delete</button>
                    <button type="button" class="cancel-delete-btn">Cancel</button>
                </div>
            </form>
        </div>
        <div class="table-container">
            <table id="expenseTable">
                <thead>
                    <tr>
                        <th>Sr. No.</th>
                        <th>Category</th>
                        <th>Purpose</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Payment Type</th>
                        <th>Cheque Number</th>
                        <th>Bank Name</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)) : ?>
                        <?php foreach ($rows as $row) : ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo $row['category']; ?></td>
                                <td><?php echo $row['purpose']; ?></td>
                                <td class="amount">₹<?php echo number_format($row['amount'], 2); ?></td>
                                <td><?php echo date("d-m-Y", strtotime($row['date'])); ?></td>
                                <td><?php echo $row['payment_type']; ?></td>
                                <td><?php echo $row['cheque_number'] ?: '-'; ?></td>
                                <td><?php echo $row['bank_name'] ?: '-'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3"><strong>Total Expense:</strong></td>
                        <td colspan="5">₹<?php echo number_format($totalPrice, 2); ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/expense.js?v=<?php echo time(); ?>"></script>
<script src="js/script.js?v=<?php echo time(); ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>