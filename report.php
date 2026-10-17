<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Expense Report Data
$sql = "SELECT * FROM expense";
$expenseResult = $conn->query($sql);
$expenseRows = [];
$totalExpense = 0;
if ($expenseResult && $expenseResult->num_rows > 0) {
    while ($row = $expenseResult->fetch_assoc()) {
        $expenseRows[] = $row;
        $totalExpense += $row['amount'];
    }
    usort($expenseRows, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });
}

// Payment Report Data
$sql = "SELECT * FROM payment";
$paymentResult = $conn->query($sql);
$paymentRows = [];
$totalMaintenance = 0;
if ($paymentResult && $paymentResult->num_rows > 0) {
    while ($row = $paymentResult->fetch_assoc()) {
        $paymentRows[] = $row;
        $totalMaintenance += $row['amount'];
    }
    // Date aur time ke hisab se descending order mein sort karo
    usort($paymentRows, function($a, $b) {
        $dateTimeA = strtotime($a['date'] . ' ' . ($a['time'] ?? '00:00:00'));
        $dateTimeB = strtotime($b['date'] . ' ' . ($b['time'] ?? '00:00:00'));
        return $dateTimeB - $dateTimeA;
    });
} else {
    $paymentRows = []; // Agar records nahi hain to empty array rakho
}

// Complaint Report Data
$sql = "SELECT * FROM complaint";
$complaintResult = $conn->query($sql);
$complaintRows = [];
if ($complaintResult && $complaintResult->num_rows > 0) {
    while ($row = $complaintResult->fetch_assoc()) {
        $complaintRows[] = $row;
    }
    usort($complaintRows, function($a, $b) {
        $dateTimeA = strtotime($a['date'] . ' ' . $a['time']);
        $dateTimeB = strtotime($b['date'] . ' ' . $b['time']);
        return $dateTimeB - $dateTimeA;
    });
}

// Pending Maintenance Report Data
$current_date = new DateTime();
$current_year = $current_date->format('Y');
$current_month = $current_date->format('m');

function getPerFlatCost($conn, $room_no) {
    $building = (stripos($room_no, 'A') === 0) ? 'A' : 'BCD';
    if ($building === 'A') {
        $sql = "SELECT SUM(amount) / num_flats AS per_flat_cost FROM building_a_flat_maintenance LIMIT 1";
    } else {
        $sql = "SELECT SUM(amount) / num_flats AS per_flat_cost FROM building_bcd_flat_maintenance LIMIT 1";
    }
    $result = $conn->query($sql);
    $row = $result->fetch_assoc();
    return $row['per_flat_cost'] ?? 0;
}

$pendingRows = [];
$totalPending = 0;
$flat_holders_sql = "
    SELECT 
        fh.room_no, 
        fh.bhk, 
        fh.sq_feet, 
        fh.fh_name, 
        fh.category, 
        fh.email, 
        fh.contact,
        fh.alternate_number,
        fh.no_of_persons,
        fh.joining_date, 
        fh.documents,
        COALESCE(a.amount, 0) AS advance_amount,
        COALESCE(SUM(p.amount), 0) AS total_paid,
        fh.occupancy_status
    FROM flat_holder fh
    LEFT JOIN advance a ON fh.room_no = a.room_no
    LEFT JOIN payment p ON fh.room_no = p.room_no 
        AND p.purpose = 'maintenance' 
        AND p.mode != 'Advance Maint.'
    GROUP BY fh.room_no, fh.bhk, fh.sq_feet, fh.fh_name, fh.category, fh.email, fh.contact, fh.alternate_number, fh.no_of_persons, fh.joining_date, fh.documents, a.amount, fh.occupancy_status
";
$flat_holders_result = $conn->query($flat_holders_sql);
if ($flat_holders_result && $flat_holders_result->num_rows > 0) {
    while ($row = $flat_holders_result->fetch_assoc()) {
        $per_flat_cost = getPerFlatCost($conn, $row['room_no']);
        $joining_date = new DateTime($row['joining_date']);
        $joining_date->modify('first day of this month');
        $months_since_joining = (int)$current_date->diff($joining_date)->format('%m') + 
                               (12 * ((int)$current_year - (int)$joining_date->format('Y')));
        if ($months_since_joining < 0) $months_since_joining = 0;
        $total_due = $per_flat_cost * $months_since_joining;

        $total_paid = $row['total_paid'];
        $advance_amount = $row['advance_amount'];
        $due_amount = $total_due - $total_paid;

        if ($due_amount > 0 && $advance_amount > 0) {
            $deducted = min($due_amount, $advance_amount);
            $due_amount -= $deducted;
            $advance_amount -= $deducted;
        }

        if ($due_amount > 0) {
            $row['pending'] = $due_amount;
            $pendingRows[] = $row;
            $totalPending += $due_amount;
        }
    }
    usort($pendingRows, function($a, $b) {
        return strtotime($b['joining_date']) - strtotime($a['joining_date']);
    });
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeXusHooD.com</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/report.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>
    <div class="main">
        <div class="topber">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <div class="report-container">
            <!-- Payment Report Accordion -->
            <div class="accordion">
                <div class="accordion-header">
                    <h2>Income Report</h2>
                    <span class="accordion-toggle">+</span>
                </div>
                <div class="accordion-content">
                    <div class="filter-section">
                        <div class="date-filter">
                            <label for="fromDatePayment">From:</label>
                            <input type="date" id="fromDatePayment">
                            <label for="toDatePayment">To:</label>
                            <input type="date" id="toDatePayment">
                        </div>
                        <div class="filter-container">
                            <label>Purpose:</label>
                            <input type="radio" name="purposeFilter" id="purpose_all" value="all" checked>
                            <label for="purpose_all">All</label>
                            <input type="radio" name="purposeFilter" id="purpose_maintenance" value="maintenance">
                            <label for="purpose_maintenance">Maintenance</label>
                            <input type="radio" name="purposeFilter" id="purpose_event" value="event contribution">
                            <label for="purpose_event">Event Contribution</label>
                            <input type="radio" name="purposeFilter" id="purpose_repair" value="repair fund">
                            <label for="purpose_repair">Repair Fund</label>
                            <input type="radio" name="purposeFilter" id="purpose_misc" value="miscellaneous">
                            <label for="purpose_misc">Miscellaneous</label>
                        </div>
                        <div class="filter-container">
                            <label>Mode:</label>
                            <input type="radio" name="modeFilter" id="mode_all" value="all" checked>
                            <label for="mode_all">All</label>
                            <input type="radio" name="modeFilter" id="mode_online" value="online">
                            <label for="mode_online">Online</label>
                            <input type="radio" name="modeFilter" id="mode_offline" value="offline">
                            <label for="mode_offline">Offline</label>
                            <input type="radio" name="modeFilter" id="mode_advanceMaint" value="advance maint.">
                            <label for="mode_advanceMaint">Advance Maint.</label>
                            <input type="radio" name="modeFilter" id="mode_advanceDeduct" value="advance deduction">
                            <label for="mode_advanceDeduct">Advance Deduction</label>
                        </div>
                        <div class="filter-container">
                            <label>Payment Type:</label>
                            <input type="radio" name="paymentTypeFilter" id="paymentType_all" value="all" checked>
                            <label for="paymentType_all">All</label>
                            <input type="radio" name="paymentTypeFilter" id="paymentType_cash" value="cash">
                            <label for="paymentType_cash">Cash</label>
                            <input type="radio" name="paymentTypeFilter" id="paymentType_cheque" value="cheque">
                            <label for="paymentType_cheque">Cheque</label>
                        </div>
                        <div class="button-group">
                            <button onclick="filterPaymentTable()">Filter</button>
                            <button onclick="printPaymentTable()">Print</button>
                            <button onclick="downloadPaymentTable()">Download PDF</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="paymentTable">
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
                                <?php if (!empty($paymentRows)) : ?>
                                    <?php foreach ($paymentRows as $row) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['room_no'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose'] ?? 'Miscellaneous'); ?></td>
                                            <td><?php echo htmlspecialchars($row['mode'] ?? 'Offline'); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_type'] ?? '-'); ?></td>
                                            <td><?php echo ($row['payment_type'] === 'cheque' && $row['cheque_number']) ? htmlspecialchars($row['cheque_number']) : '-'; ?></td>
                                            <td><?php echo ($row['payment_type'] === 'cheque' && $row['bank_name']) ? htmlspecialchars($row['bank_name']) : '-'; ?></td>
                                            <td class="amount">₹<?php echo number_format($row['amount'] ?? 0, 2); ?></td>
                                            <td><?php echo $row['date'] ? date("d-m-Y", strtotime($row['date'])) : '-'; ?></td>
                                            <td><?php echo $row['time'] ? (new DateTime($row['time']))->format('h:i A') : '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="10">No records found</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="7"><strong>Total Income:</strong></td>
                                    <td colspan="3">₹<span id="totalMaintenance"><?php echo number_format($totalMaintenance, 2); ?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Expense Report Accordion -->
            <div class="accordion">
                <div class="accordion-header">
                    <h2>Expense Report</h2>
                    <span class="accordion-toggle">+</span>
                </div>
                <div class="accordion-content">
                    <div class="filter-section">
                        <div class="date-filter">
                            <label for="fromDateExpense">From:</label>
                            <input type="date" id="fromDateExpense">
                            <label for="toDateExpense">To:</label>
                            <input type="date" id="toDateExpense">
                        </div>
                        <div class="filter-container">
                            <label>Category:</label>
                            <input type="radio" name="categoryFilter" id="category_all" value="all" checked>
                            <label for="category_all">All</label>
                            <input type="radio" name="categoryFilter" id="category_maintenance" value="maintenance">
                            <label for="category_maintenance">Maintenance</label>
                            <input type="radio" name="categoryFilter" id="category_repair" value="repair">
                            <label for="category_repair">Repairs</label>
                            <input type="radio" name="categoryFilter" id="category_event" value="event">
                            <label for="category_event">Society Event</label>
                            <input type="radio" name="categoryFilter" id="category_utilities" value="utilities">
                            <label for="category_utilities">Utilities</label>
                            <input type="radio" name="categoryFilter" id="category_salary" value="salary">
                            <label for="category_salary">Staff Salary</label>
                            <input type="radio" name="categoryFilter" id="category_security" value="security">
                            <label for="category_security">Security Services</label>
                            <input type="radio" name="categoryFilter" id="category_miscellaneous" value="miscellaneous">
                            <label for="category_miscellaneous">Miscellaneous</label>
                        </div>
                        <div class="filter-container">
                            <label>Payment Type:</label>
                            <input type="radio" name="expensePaymentTypeFilter" id="expensePaymentType_all" value="all" checked>
                            <label for="expensePaymentType_all">All</label>
                            <input type="radio" name="expensePaymentTypeFilter" id="expensePaymentType_cash" value="cash">
                            <label for="expensePaymentType_cash">Cash</label>
                            <input type="radio" name="expensePaymentTypeFilter" id="expensePaymentType_cheque" value="cheque">
                            <label for="expensePaymentType_cheque">Cheque</label>
                        </div>
                        <div class="button-group">
                            <button onclick="filterExpenseTable()">Filter</button>
                            <button onclick="printExpenseTable()">Print</button>
                            <button onclick="downloadExpenseTable()">Download PDF</button>
                        </div>
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
                                <?php if (!empty($expenseRows)) : ?>
                                    <?php foreach ($expenseRows as $row) : ?> 
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['category']); ?></td>
                                            <td><?php echo htmlspecialchars($row['purpose']); ?></td>
                                            <td class="amount">₹<?php echo number_format($row['amount'], 2); ?></td>
                                            <td><?php echo date("d-m-Y", strtotime($row['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['payment_type']); ?></td>
                                            <td><?php echo $row['cheque_number'] ?: '-'; ?></td>
                                            <td><?php echo $row['bank_name'] ?: '-'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="8">No records found</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3"><strong>Total Expense:</strong></td>
                                    <td colspan="5">₹<span id="totalExpense"><?php echo number_format($totalExpense, 2); ?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Complaint Report Accordion -->
            <div class="accordion">
                <div class="accordion-header">
                    <h2>Complaint Report</h2>
                    <span class="accordion-toggle">+</span>
                </div>
                <div class="accordion-content">
                    <div class="filter-section">
                        <div class="date-filter">
                            <label for="fromDateComplaint">From:</label>
                            <input type="date" id="fromDateComplaint">
                            <label for="toDateComplaint">To:</label>
                            <input type="date" id="toDateComplaint">
                        </div>
                        <div class="filter-container">
                            <label>Status:</label>
                            <input type="radio" name="statusFilter" id="status_all" value="all" checked>
                            <label for="status_all">All</label>
                            <input type="radio" name="statusFilter" id="status_resolved" value="resolved">
                            <label for="status_resolved">Resolved</label>
                            <input type="radio" name="statusFilter" id="status_unresolved" value="unresolved">
                            <label for="status_unresolved">Unresolved</label>
                        </div>
                        <div class="filter-container">
                            <label>Blocked:</label>
                            <input type="radio" name="blockedFilter" id="blocked_all" value="all" checked>
                            <label for="blocked_all">All</label>
                            <input type="radio" name="blockedFilter" id="blocked_blocked" value="blocked">
                            <label for="blocked_blocked">Blocked</label>
                            <input type="radio" name="blockedFilter" id="blocked_unblocked" value="unblocked">
                            <label for="blocked_unblocked">Unblocked</label>
                        </div>
                        <div class="button-group">
                            <button onclick="filterComplaintTable()">Filter</button>
                            <button onclick="printComplaintTable()">Print</button>
                            <button onclick="downloadComplaintTable()">Download PDF</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="complaintTable">
                            <thead>
                                <tr>
                                    <th>Sr. No.</th>
                                    <th>Room No</th>
                                    <th>Name</th>
                                    <th>Mobile No</th>
                                    <th>Description</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Status</th>
                                    <th>Blocked</th>
                                    <th>Reply</th>
                                    <th>Resolve Note</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($complaintRows)) : ?>
                                    <?php $index = 1; ?>
                                    <?php foreach ($complaintRows as $row) : ?>
                                        <tr>
                                            <td><?php echo $index++; ?></td>
                                            <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['mobile'] ?? 'Not provided'); ?></td>
                                            <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                                            <td><?php echo date("d-m-Y", strtotime($row['date'])); ?></td>
                                            <td><?php echo date('h:i A', strtotime($row['time'])); ?></td>
                                            <td><?php echo $row['resolved'] ? 'Resolved' : 'Unresolved'; ?></td>
                                            <td><?php echo $row['blocked'] ? 'Yes' : 'No'; ?></td>
                                            <td><?php echo htmlspecialchars($row['reply'] ?? 'Not replied yet'); ?></td>
                                            <td><?php echo htmlspecialchars($row['resolve_note'] ?? 'Not resolved yet'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="11">No records found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Pending Maintenance Report Accordion -->
            <div class="accordion">
                <div class="accordion-header">
                    <h2>Pending Maintenance Report</h2>
                    <span class="accordion-toggle">+</span>
                </div>
                <div class="accordion-content">
                    <div class="filter-section">
                        <div class="date-filter">
                            <label for="fromDatePending">From:</label>
                            <input type="date" id="fromDatePending">
                            <label for="toDatePending">To:</label>
                            <input type="date" id="toDatePending">
                        </div>
                        <div class="filter-container">
                            <label>Occupancy:</label>
                            <input type="radio" name="occupancyFilter" id="occupancy_all" value="all" checked>
                            <label for="occupancy_all">All</label>
                            <input type="radio" name="occupancyFilter" id="occupancy_owner" value="Owner">
                            <label for="occupancy_owner">Owner</label>
                            <input type="radio" name="occupancyFilter" id="occupancy_rented" value="Rented">
                            <label for="occupancy_rented">Rented</label>
                        </div>
                        <div class="button-group">
                            <button onclick="filterPendingTable()">Filter</button>
                            <button onclick="printPendingTable()">Print</button>
                            <button onclick="downloadPendingTable()">Download PDF</button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table id="pendingTable">
                            <thead>
                                <tr>
                                    <th>Room No</th>
                                    <th>Name</th>
                                    <th>Contact</th>
                                    <th>Joining Date</th>
                                    <th>Occupancy</th>
                                    <th>Pending Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($pendingRows)) : ?>
                                    <?php foreach ($pendingRows as $row) : ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                                            <td><?php echo htmlspecialchars($row['fh_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                            <td><?php echo date("d-m-Y", strtotime($row['joining_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($row['occupancy_status']); ?></td>
                                            <td class="amount due-amount">₹<?php echo number_format($row['pending'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr><td colspan="6">No records found</td></tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="5"><strong>Total Pending:</strong></td>
                                    <td>₹<span id="totalPending"><?php echo number_format($totalPending, 2); ?></span></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="js/report.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>