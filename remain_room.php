<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}
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
    <h2>Remain Units</h2>
    <!-- Filter Buttons -->
    <div class="button-container">
        <div class="filter-buttons">
            <a href="?filter=all" class="filter-btn <?php echo (isset($_GET['filter']) && $_GET['filter'] === 'all') || !isset($_GET['filter']) ? 'active' : ''; ?>">All</a>
            <a href="?filter=A" class="filter-btn <?php echo isset($_GET['filter']) && $_GET['filter'] === 'A' ? 'active' : ''; ?>">A</a>
            <a href="?filter=B" class="filter-btn <?php echo isset($_GET['filter']) && $_GET['filter'] === 'B' ? 'active' : ''; ?>">B</a>
            <a href="?filter=C" class="filter-btn <?php echo isset($_GET['filter']) && $_GET['filter'] === 'C' ? 'active' : ''; ?>">C</a>
            <a href="?filter=D" class="filter-btn <?php echo isset($_GET['filter']) && $_GET['filter'] === 'D' ? 'active' : ''; ?>">D</a>
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
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php
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

                // Filter logic with prepared statements for security
                $filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
                if ($filter === 'all') {
                    $sql = "SELECT room_no, floor, bhk, sq_feet, status FROM room_details WHERE status = ?";
                    $stmt = $conn->prepare($sql);
                    $status = "Empty";
                    $stmt->bind_param("s", $status);
                } else {
                    $sql = "SELECT room_no, floor, bhk, sq_feet, status FROM room_details WHERE status = ? AND room_no LIKE ?";
                    $stmt = $conn->prepare($sql);
                    $status = "Empty";
                    $filterParam = $filter . "%";
                    $stmt->bind_param("ss", $status, $filterParam);
                }

                $stmt->execute();
                $result = $stmt->get_result();

                // Initialize total rooms counter
                $totalRooms = 0;

                if ($result->num_rows > 0) {
                    // Output data for each row
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                                <td>" . htmlspecialchars($row["room_no"]) . "</td>
                                <td>" . htmlspecialchars($row["floor"]) . "</td>
                                <td>" . htmlspecialchars($row["bhk"]) . "</td>
                                <td>" . htmlspecialchars($row["sq_feet"]) . "</td>
                                <td>" . htmlspecialchars($row["status"]) . "</td>
                              </tr>";
                        $totalRooms++;
                    }
                } else {
                    echo "<tr><td colspan='5'>No rooms available</td></tr>";
                }

                // Display the total rooms count at the end of the table
                echo "<tr>
                        <td colspan='1'><strong>Total Rooms :</strong></td>
                        <td><strong>" . $totalRooms . "</strong></td>
                        <td colspan='3'></td>
                      </tr>";

                // Close the statement and connection
                $stmt->close();
                $conn->close();
                ?>
            </tbody>
        </table>
    </div>
</div>
<!--===================== Table End ==========================-->
</div>

<!--===================== Javascript Files ==========================-->	 
<script src="js/script.js?v=<?php echo time(); ?>"></script>

<!--===================== Icons Link ==========================-->	
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>