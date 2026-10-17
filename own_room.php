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
    $sql = "SELECT rd.room_no, rd.floor, fh.bhk, fh.sq_feet, fh.fh_name AS name, fh.contact, fh.email, fh.joining_date 
            FROM room_details rd
            INNER JOIN flat_holder fh ON rd.room_no = fh.room_no
            WHERE fh.category = 'Owner'";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Prepare failed (all): " . $conn->error);
    }
} else {
    $sql = "SELECT rd.room_no, rd.floor, fh.bhk, fh.sq_feet, fh.fh_name AS name, fh.contact, fh.email, fh.joining_date 
            FROM room_details rd
            INNER JOIN flat_holder fh ON rd.room_no = fh.room_no
            WHERE fh.category = 'Owner' AND rd.room_no LIKE ?";
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
        <h2>Owned Units</h2>
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
                        <th>Room/Shop No</th>
                        <th>Floor</th>
                        <th>BHK</th>
                        <th>Sq Feet</th>
                        <th>Owned By</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Joining Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            echo "<tr>
                                    <td>" . htmlspecialchars($row["room_no"]) . "</td>
                                    <td>" . htmlspecialchars($row["floor"]) . "</td>
                                    <td>" . htmlspecialchars($row["bhk"]) . "</td>
                                    <td>" . htmlspecialchars($row["sq_feet"]) . "</td>
                                    <td>" . htmlspecialchars($row["name"]) . "</td>
                                    <td>" . htmlspecialchars($row["contact"]) . "</td>
                                    <td>" . htmlspecialchars($row["email"]) . "</td>
                                    <td>" . date("d-m-Y", strtotime($row["joining_date"])) . "</td>
                                  </tr>";
                            $totalRooms++;
                        }
                    } else {
                        echo "<tr><td colspan='8'>No owned rooms found</td></tr>";
                    }

                    echo "<tr>
                            <td colspan='1' style='text-align: left; font-weight: bold;'>Total Rooms :</td>
                            <td style='font-weight: bold;'>" . $totalRooms . "</td>
                            <td colspan='6'></td>
                          </tr>";
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