<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: adm_home.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "mysociety";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Insert new room details
if (isset($_POST['insert'])) {
    $room_no = $_POST['room_no'];
    $floor = $_POST['floor'];
    $bhk = $_POST['bhk'];
    $sq_feet = $_POST['sq_feet'];

    $sql = "INSERT INTO room_details (room_no, floor, bhk, sq_feet, status) VALUES (?, ?, ?, ?, 'Empty')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssi", $room_no, $floor, $bhk, $sq_feet);
    if ($stmt->execute()) {
        header("Location: total_room.php");
        exit();
    }
    $stmt->close();
}

// Update room details
if (isset($_POST['update'])) {
    $room_no = $_POST['room_no'];
    $floor = $_POST['floor'];
    $bhk = $_POST['bhk'];
    $sq_feet = $_POST['sq_feet'];

    $sql = "UPDATE room_details SET floor=?, bhk=?, sq_feet=? WHERE room_no=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $floor, $bhk, $sq_feet, $room_no);
    if ($stmt->execute()) {
        header("Location: total_room.php");
        exit();
    }
    $stmt->close();
}

// Delete room details
if (isset($_GET['delete'])) {
    $room_no = $_GET['delete'];
    $sql = "DELETE FROM room_details WHERE room_no=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room_no);
    if ($stmt->execute()) {
        header("Location: total_room.php");
        exit();
    }
    $stmt->close();
}

// Filter logic with prepared statements
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

if ($filter === 'all') {
    $sql = "SELECT * FROM room_details";
    $stmt = $conn->prepare($sql);
} else {
    $sql = "SELECT * FROM room_details WHERE room_no LIKE ?";
    $stmt = $conn->prepare($sql);
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

<!-- Sidebar -->
<?php include 'admin_sidebar.php'; ?>

<!-- Main Content -->
<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
        <a href="mysociety.php" class="back-btn">
            <ion-icon name="arrow-back-outline"></ion-icon>
        </a>
    </div>

    <div class="container3">
        <h2>Units Detail</h2> 

        <!-- Buttons Container -->
        <div class="button-container">
            <button class="styled-button" onclick="showInsertModal()">Add</button>
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
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($row['room_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['floor']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['bhk']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['sq_feet']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>";
                            echo "<button class='edit-btn' onclick=\"showEditModal('" . htmlspecialchars($row['room_no']) . "', '" . htmlspecialchars($row['floor']) . "', '" . htmlspecialchars($row['bhk']) . "', '" . htmlspecialchars($row['sq_feet']) . "')\">Edit</button>";
                            echo "<a href='?delete=" . htmlspecialchars($row['room_no']) . "' class='delete-btn' onclick='return confirm(\"Are you sure you want to delete this room?\")'>Delete</a>";
                            echo "</td>";
                            echo "</tr>";
                            $totalRooms++;
                        }
                    }
                    ?>
                    <tr>
                        <td colspan="2"><strong>Total Rooms :</strong></td>
                        <td><strong><?php echo $totalRooms; ?></strong></td>
                        <td colspan="3"></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Insert Modal -->
    <div id="insertModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Insert Room Details</h2>
            <form method="post" class="enhanced-form">
                <div class="form-group">
                    <label for="room_no">Room No:</label>
                    <input type="text" name="room_no" id="room_no" required>
                </div>
                <div class="form-group">
                    <label for="floor">Floor:</label>
                    <input type="number" name="floor" id="floor">
                </div>
                <div class="form-group">
                    <label for="bhk">BHK:</label>
                    <input type="number" name="bhk" id="bhk">
                </div>
                <div class="form-group">
                    <label for="sq_feet">Sq Feet:</label>
                    <input type="number" name="sq_feet" id="sq_feet" required>
                </div>
                <input class="styled-button" type="submit" name="insert" value="Insert">
            </form>
        </div>
    </div>

    <!-- Edit Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">×</span>
            <h2>Edit Room Details</h2>
            <form method="post" class="enhanced-form">
                <div class="form-group">
                    <label for="edit_room_no">Room No:</label>
                    <input type="text" name="room_no" id="edit_room_no" readonly>
                </div>
                <div class="form-group">
                    <label for="edit_floor">Floor:</label>
                    <input type="number" name="floor" id="edit_floor">
                </div>
                <div class="form-group">
                    <label for="edit_bhk">BHK:</label>
                    <input type="number" name="bhk" id="edit_bhk">
                </div>
                <div class="form-group">
                    <label for="edit_sq_feet">Sq Feet:</label>
                    <input type="number" name="sq_feet" id="edit_sq_feet" required>
                </div>
                <input class="styled-button" type="submit" name="update" value="Update">
            </form>
        </div>
    </div>

    <!-- Javascript files -->
    <script src="js/script.js?v=<?php echo time(); ?>"></script>
    <script src="js/total_room.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</div>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>