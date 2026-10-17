<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">		
    <title>NeXusHooD.com</title>
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/dashboard.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
    </div>

    <?php
    $conn->close();
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Reconnection failed: " . $conn->connect_error);
    }

    $total_rooms_sql = "SELECT COUNT(*) as total_rooms FROM room_details";
    $total_rooms_result = $conn->query($total_rooms_sql);
    $total_rooms = $total_rooms_result->fetch_assoc()['total_rooms'];

    $remain_rooms_sql = "SELECT COUNT(*) as remain_rooms FROM room_details WHERE status = 'Empty'";
    $remain_rooms_result = $conn->query($remain_rooms_sql);
    $remain_rooms = $remain_rooms_result->fetch_assoc()['remain_rooms'];

    $owner_room_sql = "SELECT COUNT(*) as owner_room FROM flat_holder WHERE category = 'Owner'";
    $owner_room_result = $conn->query($owner_room_sql);
    $owner_room = $owner_room_result->fetch_assoc()['owner_room'];

    $tenant_room_sql = "SELECT COUNT(*) as tenant_room FROM flat_holder WHERE occupancy_status = 'Rented'";
    $tenant_room_result = $conn->query($tenant_room_sql);
    $tenant_room = $tenant_room_result->fetch_assoc()['tenant_room'];
    ?>

    <div class="cardbox">
        <div class="card" onclick="location.href='total_room.php'">
            <div><div class="number"><?php echo $total_rooms; ?></div><div class="cardname">Total Units</div></div>
            <div class="iconbox"><ion-icon name="podium-outline"></ion-icon></div>
        </div>
        <div class="card" onclick="location.href='remain_room.php'">
            <div><div class="number"><?php echo $remain_rooms; ?></div><div class="cardname">Remain Units</div></div>
            <div class="iconbox"><ion-icon name="home-outline"></ion-icon></div>
        </div>
        <div class="card" onclick="location.href='own_room.php'">
            <div><div class="number"><?php echo $owner_room; ?></div><div class="cardname">Owned Units</div></div>
            <div class="iconbox"><ion-icon name="person-outline"></ion-icon></div>
        </div>
        <div class="card" onclick="location.href='rent_room.php'">
            <div><div class="number"><?php echo $tenant_room; ?></div><div class="cardname">Tenants</div></div>
            <div class="iconbox"><ion-icon name="people-outline"></ion-icon></div>
        </div>
    </div>

    <?php
    $complaint_sql = "SELECT room_no, name, date, time, description, image_path, video_path FROM complaint ORDER BY date DESC, time DESC LIMIT 1";
    $complaint_result = $conn->query($complaint_sql);
    $complaint = $complaint_result->num_rows > 0 ? $complaint_result->fetch_assoc() : ['room_no' => 'N/A', 'name' => 'N/A', 'date' => 'N/A', 'time' => 'N/A', 'description' => 'No complaints found.', 'image_path' => '', 'video_path' => ''];

    // Check if date and time are valid before creating DateTime objects
    $formatted_date = $complaint['date'] !== 'N/A' ? (new DateTime($complaint['date']))->format('d-m-Y') : 'N/A';
    $formatted_time = $complaint['time'] !== 'N/A' ? (new DateTime($complaint['time']))->format('h:i A') : 'N/A';

    $total_payments_sql = "SELECT SUM(amount) AS total_payment FROM payment";
    $total_payments_result = $conn->query($total_payments_sql);
    $total_payments = $total_payments_result->fetch_assoc()['total_payment'] ?? 0;

    $total_expenses_sql = "SELECT SUM(amount) AS total_expense FROM expense";
    $total_expenses_result = $conn->query($total_expenses_sql);
    $total_expenses = $total_expenses_result->fetch_assoc()['total_expense'] ?? 0;

    $society_fund = $total_payments - $total_expenses;
    $color = $society_fund >= 0 ? "green" : "red";
    $symbol = $society_fund >= 0 ? "+ " : "- ";
    $formatted_total = number_format(abs($society_fund), 2);
    ?>

    <div class="container">
        <div class="complaint-container" onclick="location.href='complaint.php'">
            <h2>Recent Complaint</h2>
            <div class="complaint-details">
                <div class="complaint-header">
                    <p><strong>Room No:</strong> <span><?php echo htmlspecialchars($complaint['room_no']); ?></span></p>
                    <p><strong>Name:</strong> <span><?php echo htmlspecialchars($complaint['name']); ?></span></p>
                </div>
                <div class="complaint-meta">
                    <p><strong>Date:</strong> <span><?php echo $formatted_date; ?></span></p>
                    <p><strong>Time:</strong> <span><?php echo $formatted_time; ?></span></p>
                </div>
                <div class="complaint-body">
                    <p><strong>Description:</strong> <span><?php echo htmlspecialchars($complaint['description']); ?></span></p>
                    <?php if (!empty($complaint['image_path'])): ?>
                        <p><strong>Image:</strong> <a href="<?php echo htmlspecialchars($complaint['image_path']); ?>" target="_blank" class="media-link">View Image</a></p>
                    <?php endif; ?>
                    <?php if (!empty($complaint['video_path'])): ?>
                        <p><strong>Video:</strong> <a href="<?php echo htmlspecialchars($complaint['video_path']); ?>" target="_blank" class="media-link">View Video</a></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="total-fund" onclick="location.href='payment.php'">
            <h2>Society Fund</h2>
            <div class="fund-details">
                <div class="total-box" style="color: <?php echo $color; ?>">
                    <span class="fund-symbol"><?php echo $symbol; ?></span>
                    <span class="fund-amount">₹<?php echo $formatted_total; ?></span>
                </div>
                <div class="fund-status">
                    <?php echo $society_fund >= 0 ? 'In Surplus' : 'In Deficit'; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="js/dashboard.js"></script>
<script src="js/script.js?v=<?php echo time(); ?>"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

<?php $conn->close(); ?>
</body>
</html>