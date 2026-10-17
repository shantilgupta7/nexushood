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

// Set time zone to India
date_default_timezone_set('Asia/Kolkata');

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle admin complaint submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_complaint'])) {
    $room_no = $_POST['room_no'];
    $name = $_POST['name'];
    $mobile = $_POST['mobile'];
    $description = $_POST['description'];
    $date = date('Y-m-d'); // Current date
    $time = date('H:i:s'); // Current time in 24-hour format

    // Debug: Log the current date and time
    error_log("Submitting complaint at Date: $date, Time: $time");

    // Handle file uploads
    $image_path = '';
    $video_path = '';
    
    if (!empty($_FILES['image']['name'])) {
        $image_target = "Uploads/images/" . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $image_target)) {
            $image_path = $image_target;
        } else {
            $error_message = "Error uploading image.";
        }
    }
    
    if (!empty($_FILES['video']['name'])) {
        $video_target = "Uploads/videos/" . basename($_FILES['video']['name']);
        if (move_uploaded_file($_FILES['video']['tmp_name'], $video_target)) {
            $video_path = $video_target;
        } else {
            $error_message = "Error uploading video.";
        }
    }

    if (empty($error_message)) {
        $sql = "INSERT INTO complaint (room_no, name, mobile, description, date, time, image_path, video_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssssss", $room_no, $name, $mobile, $description, $date, $time, $image_path, $video_path);
            if ($stmt->execute()) {
                echo "<script>Swal.fire('Success', 'Complaint submitted successfully!', 'success');</script>";
                header("Location: " . $_SERVER['PHP_SELF']);
                exit();
            } else {
                $error_message = "Error submitting complaint: " . $conn->error;
            }
            $stmt->close();
        } else {
            $error_message = "Prepare statement failed: " . $conn->error;
        }
    }
}

// Handle block/unblock action
if (isset($_GET['toggle_block'])) {
    $id = $_GET['id'];
    $sql = "SELECT blocked FROM complaint WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $new_status = $row['blocked'] ? 0 : 1;

    $update_sql = "UPDATE complaint SET blocked = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("ii", $new_status, $id);
    $update_stmt->execute();
    $update_stmt->close();
    $stmt->close();

    header("Location: complaint.php");
    exit();
}

// Handle resolve action with note
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['resolve_note'])) {
    $id = $_POST['id'];
    $resolve_note = $_POST['resolve_note'];
    $update_sql = "UPDATE complaint SET resolved = 1, resolve_note = ? WHERE id = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("si", $resolve_note, $id);
    if ($stmt->execute()) {
        echo "<script>Swal.fire('Success', 'Complaint resolved successfully!', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Failed to resolve complaint.', 'error');</script>";
    }
    $stmt->close();
    header("Location: complaint.php");
    exit();
}

// Handle delete resolved complaints action with date range
if (isset($_GET['delete_resolved'])) {
    $delete_sql = "DELETE FROM complaint WHERE resolved = 1";
    
    if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
        $start_date = $_GET['start_date'];
        $end_date = $_GET['end_date'];
        $delete_sql .= " AND date BETWEEN ? AND ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("ss", $start_date, $end_date);
        $success = $stmt->execute();
        $stmt->close();
    } else {
        $success = $conn->query($delete_sql);
    }
    
    if ($success === TRUE) {
        echo "<script>Swal.fire('Success', 'Resolved complaints deleted successfully!', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Error deleting resolved complaints: " . addslashes($conn->error) . "', 'error');</script>";
    }
    header("Location: complaint.php");
    exit();
}

// Fetch complaints
$sql = "SELECT * FROM complaint ORDER BY room_no ASC, date DESC, time DESC";
$result = $conn->query($sql);

// Handle reply form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reply'])) {
    $id = $_POST['id'];
    $reply = $_POST['reply'];
    $sql = "UPDATE complaint SET reply = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $reply, $id);
    if ($stmt->execute()) {
        echo "<script>Swal.fire('Success', 'Reply submitted successfully!', 'success');</script>";
    } else {
        echo "<script>Swal.fire('Error', 'Failed to submit reply.', 'error');</script>";
    }
    $stmt->close();
    header("Location: complaint.php");
    exit();
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
    <link rel="stylesheet" href="css/complaintnew.css?v=<?php echo time(); ?>">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main">
        <div class="topber">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <!-- Complaint Form Accordion -->
        <div class="form-accordion">
            <div class="accordion-header">
                <h2>File New Complaint</h2>
                <span class="accordion-toggle">+</span>
            </div>
            <div class="accordion-content">
                <?php if (isset($error_message)): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="room_no">Room No:</label>
                            <input type="text" id="room_no" name="room_no" placeholder="Enter room number..." required>
                        </div>
                        <div class="form-group">
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" placeholder="Enter member name..." required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="image">Image:</label>
                            <input type="file" id="image" name="image" accept="image/*">
                        </div>
                        <div class="form-group">
                            <label for="video">Video:</label>
                            <input type="file" id="video" name="video" accept="video/*" onchange="checkVideoSizeAndDuration(this)">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="description">Description:</label>
                            <textarea id="description" name="description" placeholder="Enter complaint description..." required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label for="mobile">Mobile:</label>
                            <input type="text" id="mobile" name="mobile" placeholder="Enter mobile number..." required>
                        </div>
                        <div class="form-group button-group">
                            <button type="submit" name="submit_complaint" class="submit-btn">Submit Complaint</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="container9">
            <div class="header-with-button">
                <h2>Complaints</h2>
                <button class="delete-resolved-btn" id="showDeleteRange">Delete Resolved</button>
            </div>
            <div id="deleteRangeModal" class="modal delete-modal">
                <div class="modal-content">
                    <span class="close">×</span>
                    <h2>Delete Resolved Complaints</h2>
                    <p class="modal-subtitle">Select a date range to delete resolved complaints</p>
                    <form id="deleteRangeForm" method="GET" action="">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_date">Start Date:</label>
                                <input type="date" name="start_date" id="start_date" required>
                            </div>
                            <div class="form-group">
                                <label for="end_date">End Date:</label>
                                <input type="date" name="end_date" id="end_date" required>
                            </div>
                        </div>
                        <div class="button-group">
                            <button type="submit" name="delete_resolved" value="1" class="delete-btn">Delete</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Sr. No.</th>
                            <th>Room No</th>
                            <th>Name</th>
                            <th>Mobile No</th>
                            <th>Description</th>
                            <th>Image</th>
                            <th>Video</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Reply</th>
                            <th>Resolve Note</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && $result->num_rows > 0) : ?>
                            <?php
                            $groupedComplaints = [];
                            while ($row = $result->fetch_assoc()) {
                                $roomNo = $row['room_no'];
                                if (!isset($groupedComplaints[$roomNo])) {
                                    $groupedComplaints[$roomNo] = [];
                                }
                                $groupedComplaints[$roomNo][] = $row;
                            }
                            $globalIndex = 1;
                            ?>
                            <?php foreach ($groupedComplaints as $roomNo => $complaints) : ?>
                                <?php
                                $latestComplaint = reset($complaints);
                                $blocked = $latestComplaint['blocked'];
                                $latestId = $latestComplaint['id'];
                                ?>
                                <tr class="room-header">
                                    <td colspan="11" style="background-color: #f1f1f1; font-weight: bold; padding-left: 190px;">
                                        Room No: <?php echo htmlspecialchars($roomNo); ?>
                                    </td>
                                    <td colspan="1" style="background-color: #f1f1f1; text-align: center;">
                                        <button class="toggle-block-btn <?php echo $blocked ? 'unblock-btn' : 'block-btn'; ?>" data-id="<?php echo $latestId; ?>">
                                            <?php echo $blocked ? 'Unblock' : 'Block'; ?>
                                        </button>
                                    </td>
                                </tr>
                                <?php foreach ($complaints as $row) : ?>
                                    <tr class="<?php echo $row['resolved'] ? 'resolved' : ''; ?>">
                                        <td><?php echo $globalIndex++; ?></td>
                                        <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['mobile'] ?? 'Not provided'); ?></td>
                                        <td><?php echo htmlspecialchars($row['description'] ?? ''); ?></td>
                                        <td>
                                            <?php if (!empty($row['image_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['image_path']); ?>" target="_blank">View Image</a>
                                            <?php else: ?>
                                                No Image
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($row['video_path'])): ?>
                                                <a href="<?php echo htmlspecialchars($row['video_path']); ?>" target="_blank">View Video</a>
                                            <?php else: ?>
                                                No Video
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo (new DateTime($row['date']))->format('d-m-Y'); ?></td>
                                        <td><?php echo date('h:i A', strtotime($row['time'])); ?></td>
                                        <td><?php echo htmlspecialchars($row['reply'] ?? 'Not replied yet'); ?></td>
                                        <td><?php echo htmlspecialchars($row['resolve_note'] ?? 'Not resolved yet'); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="javascript:void(0)" class="accept-button" data-id="<?php echo $row['id']; ?>">Reply</a>
                                                <?php if (!$row['resolved']) : ?>
                                                    <a href="javascript:void(0)" class="resolve-button" data-id="<?php echo $row['id']; ?>">Resolve</a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr><td colspan="12">No records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Reply Modal -->
        <div id="replyModal" class="modal">
            <div class="modal-content">
                <span class="close">×</span>
                <h2>Send Reply</h2>
                <form id="replyForm" method="POST" action="">
                    <input type="hidden" name="id" id="reply_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="reply">Reply:</label>
                            <textarea name="reply" id="reply" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group button-group">
                            <button type="submit" name="reply" class="submit-btn">Submit Reply</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Resolve Modal -->
        <div id="resolveModal" class="modal">
            <div class="modal-content">
                <span class="close">×</span>
                <h2>Add Resolve Note</h2>
                <form id="resolveForm" method="POST" action="">
                    <input type="hidden" name="id" id="resolve_id">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="resolve_note">Resolve Note:</label>
                            <textarea name="resolve_note" id="resolve_note" required></textarea>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group button-group">
                            <button type="submit" name="resolve_note" class="submit-btn">Resolve Complaint</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/script.js?v=<?php echo time(); ?>"></script>
    <script src="js/complaint.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>