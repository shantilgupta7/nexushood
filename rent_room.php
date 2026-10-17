<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

// Database connection parameters
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Filter logic with prepared statements
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter === 'all') {
    $sql = "SELECT rd.room_no, fh.fh_name AS owner_name, td.tenant_name, td.tenant_contact, td.tenant_email, td.tenant_joining_date, td.tenant_document 
            FROM room_details rd
            INNER JOIN flat_holder fh ON rd.room_no = fh.room_no
            LEFT JOIN tenant_details td ON rd.room_no = td.room_no
            WHERE fh.occupancy_status = 'Rented' AND td.tenant_name IS NOT NULL";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed (all): " . $conn->error);
    }
} else {
    $sql = "SELECT rd.room_no, fh.fh_name AS owner_name, td.tenant_name, td.tenant_contact, td.tenant_email, td.tenant_joining_date, td.tenant_document 
            FROM room_details rd
            INNER JOIN flat_holder fh ON rd.room_no = fh.room_no
            LEFT JOIN tenant_details td ON rd.room_no = td.room_no
            WHERE fh.occupancy_status = 'Rented' AND td.tenant_name IS NOT NULL AND rd.room_no LIKE ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed (filtered): " . $conn->error);
    }
    $filterParam = $filter . '%';
    $stmt->bind_param("s", $filterParam);
}

$stmt->execute();
$result = $stmt->get_result();

$totalRooms = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">		
    <title>NeXusHooD.com</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/total_room.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'admin_sidebar.php'; ?>

<!--===================== Top Bar Start ==========================-->	 
<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
        <a href="mysociety.php" class="back-btn">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
    </div>
<!--===================== Top Bar End ==========================-->	
 
    <!--===================== Table Start ==========================-->
    <div class="container3">
        <h2>Rental Units</h2>
        <div class="button-container">
            <div class="filter-buttons">
                <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?filter=A" class="filter-btn <?php echo $filter === 'A' ? 'active' : ''; ?>">A</a>
                <a href="?filter=B" class="filter-btn <?php echo $filter === 'B' ? 'active' : ''; ?>">B</a>
                <a href="?filter=C" class="filter-btn <?php echo $filter === 'C' ? 'active' : ''; ?>">C</a>
                <a href="?filter=D" class="filter-btn <?php echo $filter === 'D' ? 'active' : ''; ?>">D</a>
            </div>
        </div>
        <div class="table-container">
            <table id="userTable">
                <thead>
                    <tr>
                        <th>Room No</th>
                        <th>Owner Name</th>
                        <th>Tenant Name</th>
                        <th>Tenant Contact</th>
                        <th>Tenant Email</th>
                        <th>Tenant Joining Date</th>
                        <th>Tenant Document</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($row["room_no"]) . "</td>
                                    <td>" . htmlspecialchars($row["owner_name"]) . "</td>
                                    <td>" . htmlspecialchars($row["tenant_name"]) . "</td>
                                    <td>" . htmlspecialchars($row["tenant_contact"]) . "</td>
                                    <td>" . htmlspecialchars($row["tenant_email"]) . "</td>
                                    <td>" . ($row["tenant_joining_date"] ? date("d-m-Y", strtotime($row["tenant_joining_date"])) : 'N/A') . "</td>
                                    <td>" . ($row["tenant_document"] ? '<a href="' . htmlspecialchars($row["tenant_document"]) . '" target="_blank">View</a>' : 'N/A') . "</td>
                                  </tr>";
                            $totalRooms++;
                        }
                    } else {
                        echo "<tr><td colspan='7'>No rental rooms with tenant details found</td></tr>";
                    }

                    echo "<tr>
                            <td colspan='1' style='text-align: left; font-weight: bold;'>Total Rooms :</td>
                            <td style='font-weight: bold;'>" . $totalRooms . "</td>
                            <td colspan='5'></td>
                          </tr>";

                    // Update tenant_room in room_details (with error handling)
                    $update_sql = "UPDATE room_details SET tenant_room = ?";
                    $update_stmt = $conn->prepare($update_sql);
                    if ($update_stmt === false) {
                        error_log("Update prepare failed: " . $conn->error);
                    } else {
                        $update_stmt->bind_param("i", $totalRooms);
                        $update_stmt->execute();
                        $update_stmt->close();
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    <!--===================== Table End ==========================-->
</div>

<!--===================== Javascript Link ==========================-->	 
<script src="js/script.js?v=<?php echo time(); ?>"></script>

<!--===================== Icons Link ==========================-->	
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>