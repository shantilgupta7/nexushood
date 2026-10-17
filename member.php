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

// Handle adding new member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
    $room_no = $_POST['room_no'];
    $bhk = $_POST['bhk'];
    $sq_feet = $_POST['sq_feet'];
    $fh_name = $_POST['fh_name'];
    $category = $_POST['category'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $alternate_number = $_POST['alternate_number'];
    $no_of_persons = $_POST['no_of_persons'];
    $joining_date = $_POST['joining_date'];
    $occupancy_status = $_POST['occupancy_status'];
    
    $documents = [];
    if (isset($_FILES['documents']) && count($_FILES['documents']['name']) <= 10) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] == UPLOAD_ERR_OK) {
                $doc_path = $upload_dir . time() . '_' . basename($name);
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $doc_path)) {
                    $documents[] = $doc_path;
                }
            }
        }
    }
    $documents_json = json_encode($documents);

    $sql = "INSERT INTO flat_holder (room_no, bhk, sq_feet, fh_name, category, email, contact, alternate_number, no_of_persons, joining_date, documents, occupancy_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssssssisss", $room_no, $bhk, $sq_feet, $fh_name, $category, $email, $contact, $alternate_number, $no_of_persons, $joining_date, $documents_json, $occupancy_status);
    
    if ($stmt->execute()) {
        $update_sql = "UPDATE room_details SET status = 'Occupied' WHERE room_no = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $room_no);
        $update_stmt->execute();
        $update_stmt->close();
        $stmt->close();
        header("Location: member.php");
        exit();
    } else {
        echo "Error adding new member: " . $conn->error;
    }
    $stmt->close();
}

// Handle editing member
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_member'])) {
    $room_no = $_POST['room_no'];
    $room_no_original = $_POST['room_no_original'];
    $bhk = $_POST['bhk'];
    $sq_feet = $_POST['sq_feet'];
    $fh_name = $_POST['fh_name'];
    $category = $_POST['category'];
    $email = $_POST['email'];
    $contact = $_POST['contact'];
    $alternate_number = $_POST['alternate_number'];
    $no_of_persons = $_POST['no_of_persons'];
    $joining_date = $_POST['joining_date'];
    $occupancy_status = $_POST['occupancy_status'];
    
    $existing_documents = json_decode($_POST['existing_documents'] ?? '[]', true);
    $documents = $existing_documents;
    
    if (isset($_FILES['documents']) && count($_FILES['documents']['name']) > 0) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        foreach ($_FILES['documents']['name'] as $key => $name) {
            if ($_FILES['documents']['error'][$key] == UPLOAD_ERR_OK && count($documents) < 10) {
                $doc_path = $upload_dir . time() . '_' . basename($name);
                if (move_uploaded_file($_FILES['documents']['tmp_name'][$key], $doc_path)) {
                    $documents[] = $doc_path;
                }
            }
        }
    }
    $documents_json = json_encode($documents);

    $sql = "UPDATE flat_holder SET room_no = ?, bhk = ?, sq_feet = ?, fh_name = ?, category = ?, email = ?, contact = ?, alternate_number = ?, no_of_persons = ?, joining_date = ?, documents = ?, occupancy_status = ?";
    $params = [$room_no, $bhk, $sq_feet, $fh_name, $category, $email, $contact, $alternate_number, $no_of_persons, $joining_date, $documents_json, $occupancy_status];
    $types = "ssssssssisss";
    
    $sql .= " WHERE room_no = ?";
    $params[] = $room_no_original;
    $types .= "s";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        if ($room_no !== $room_no_original) {
            $update_sql = "UPDATE room_details SET status = 'Occupied' WHERE room_no = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("s", $room_no);
            $update_stmt->execute();
            $update_stmt->close();
        }
        $stmt->close();
        header("Location: member.php");
        exit();
    } else {
        echo "Error updating member: " . $conn->error;
    }
    $stmt->close();
}

// Handle deleting member
if (isset($_GET['delete'])) {
    $room_no = $_GET['delete'];
    $sql = "DELETE FROM flat_holder WHERE room_no=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room_no);
    if ($stmt->execute()) {
        $update_sql = "UPDATE room_details SET status = 'Empty' WHERE room_no = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("s", $room_no);
        $update_stmt->execute();
        $update_stmt->close();
        header("Location: member.php");
        exit();
    } else {
        echo "Error deleting record: " . $conn->error;
    }
    $stmt->close();
}

// Handle occupancy status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_occupancy'])) {
    $room_no = $_POST['room_no'];
    $occupancy_status = $_POST['occupancy_status'];
    $no_of_persons = $_POST['no_of_persons'] ?? 0;

    $sql = "UPDATE flat_holder SET occupancy_status = ?, no_of_persons = ? WHERE room_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sis", $occupancy_status, $no_of_persons, $room_no);

    if ($stmt->execute()) {
        if ($occupancy_status == 'Owner') {
            $delete_tenant_sql = "DELETE FROM tenant_details WHERE room_no = ?";
            $delete_stmt = $conn->prepare($delete_tenant_sql);
            $delete_stmt->bind_param("s", $room_no);
            $delete_stmt->execute();
            $delete_stmt->close();
        }
        $stmt->close();
        header("Location: member.php");
        exit();
    } else {
        echo "Error updating occupancy status: " . $conn->error;
    }
    $stmt->close();
}

// Handle adding tenant details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_tenant'])) {
    $room_no = $_POST['room_no'];
    $tenant_name = $_POST['tenant_name'];
    $tenant_email = $_POST['tenant_email'];
    $tenant_contact = $_POST['tenant_contact'];
    $tenant_alternate_number = $_POST['tenant_alternate_number'];
    $tenant_no_of_persons = $_POST['tenant_no_of_persons'];
    $tenant_joining_date = $_POST['tenant_joining_date'];
    
    $tenant_documents = [];
    if (isset($_FILES['tenant_documents']) && count($_FILES['tenant_documents']['name']) <= 10) {
        $upload_dir = 'Uploads/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        foreach ($_FILES['tenant_documents']['name'] as $key => $name) {
            if ($_FILES['tenant_documents']['error'][$key] == UPLOAD_ERR_OK) {
                $doc_path = $upload_dir . time() . '_' . basename($name);
                if (move_uploaded_file($_FILES['tenant_documents']['tmp_name'][$key], $doc_path)) {
                    $tenant_documents[] = $doc_path;
                }
            }
        }
    }
    $tenant_documents_json = json_encode($tenant_documents);

    $check_sql = "SELECT COUNT(*) AS count FROM tenant_details WHERE room_no = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $room_no);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    $row = $check_result->fetch_assoc();
    $exists = $row['count'] > 0;
    $check_stmt->close();

    if ($exists) {
        $sql = "UPDATE tenant_details SET tenant_name = ?, tenant_email = ?, tenant_contact = ?, tenant_alternate_number = ?, tenant_no_of_persons = ?, tenant_documents = ?, tenant_joining_date = ? WHERE room_no = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisss", $tenant_name, $tenant_email, $tenant_contact, $tenant_alternate_number, $tenant_no_of_persons, $tenant_documents_json, $tenant_joining_date, $room_no);
    } else {
        $sql = "INSERT INTO tenant_details (room_no, tenant_name, tenant_email, tenant_contact, tenant_alternate_number, tenant_no_of_persons, tenant_documents, tenant_joining_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisss", $room_no, $tenant_name, $tenant_email, $tenant_contact, $tenant_alternate_number, $tenant_no_of_persons, $tenant_documents_json, $tenant_joining_date);
    }

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: member.php");
        exit();
    } else {
        echo "Error adding/updating tenant: " . $conn->error;
    }
    $stmt->close();
}

// AJAX endpoint to fetch tenant details
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action']) && $_GET['action'] == 'get_tenant' && isset($_GET['room_no'])) {
    $room_no = $_GET['room_no'];
    $sql = "SELECT tenant_name, tenant_email, tenant_contact, tenant_alternate_number, tenant_no_of_persons, tenant_documents, tenant_joining_date FROM tenant_details WHERE room_no = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $room_no);
    $stmt->execute();
    $result = $stmt->get_result();
    $tenant = $result->fetch_assoc();
    echo json_encode($tenant);
    $stmt->close();
    exit();
}

// Fetch empty rooms
$empty_rooms_sql = "SELECT room_no, bhk, sq_feet FROM room_details WHERE status = 'Empty'";
$empty_rooms_result = $conn->query($empty_rooms_sql);

// Fetch flat holders with payment details
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

// Function to get per_flat_cost based on building
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

$current_date = new DateTime();
$current_year = $current_date->format('Y');
$current_month = $current_date->format('m');
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
    <link rel="stylesheet" href="css/member.css?v=<?php echo time(); ?>">
    <style>
        .document-select {
            width: 100%;
            padding: 5px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <div class="main"> 
        <div class="topber">
            <div class="toggle">
                <ion-icon name="menu-outline"></ion-icon>
            </div>
        </div>

        <div class="container9">
            <h2>Unit Holder Details</h2>
            <div class="action-bar">
                <div class="search-container">
                    <input type="text" id="searchInput" placeholder="Search by Room No, Name, or Contact..." onkeyup="searchTable()">
                </div>
                <button class="add-button" onclick="showRoomSelection()">Add New Member</button>
            </div>

            <div class="table-container">
                <table id="memberTable">
                    <thead>
                        <tr>
                            <th>Room/Shop No</th>
                            <th>BHK</th>
                            <th>Sq Feet</th>
                            <th>Flat Holder Name</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Alternate Contact</th>
                            <th>No. of Persons</th>
                            <th>Documents</th>
                            <th>Joining Date</th>
                            <th>Maintenance</th>
                            <th>Occupancy</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($flat_holders_result && $flat_holders_result->num_rows > 0) : ?>
                            <?php while ($row = $flat_holders_result->fetch_assoc()) : ?>
                                <?php
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
                                    $display_amount = $due_amount;
                                    $amount_class = 'due-amount';
                                    $amount_label = ' (Due)';
                                } elseif ($advance_amount > 0) {
                                    $display_amount = $advance_amount;
                                    $amount_class = 'advance-amount';
                                    $amount_label = ' (Advance)';
                                } else {
                                    $display_amount = 0;
                                    $amount_class = '';
                                    $amount_label = '';
                                }
                                $documents = json_decode($row['documents'] ?? '[]', true);
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['room_no']); ?></td>
                                    <td><?php echo htmlspecialchars($row['bhk']); ?></td>
                                    <td><?php echo htmlspecialchars($row['sq_feet']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fh_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['contact']); ?></td>
                                    <td><?php echo htmlspecialchars($row['alternate_number'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_of_persons']); ?></td>
                                    <td>
                                        <select class="document-select" style="border-radius:20px"/>
                                            <option value="" disabled selected>documents</option>
                                            <?php if (count($documents) > 0) : ?>
                                                <?php foreach ($documents as $doc) : ?>
                                                    <option value="<?php echo htmlspecialchars($doc); ?>">
                                                        <?php echo htmlspecialchars(basename($doc)); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                                <option value="" disabled>No documents</option>
                                            <?php endif; ?>
                                        </select>
                                    </td>
                                    <td><?php echo date("d-m-Y", strtotime($row['joining_date'])); ?></td>
                                    <td class="<?php echo $amount_class; ?>">
                                        <?php echo '₹' . number_format($display_amount, 2) . $amount_label; ?>
                                    </td>
                                    <td class="occupancy-cell">
                                        <div class="custom-select">
                                            <form method="POST" style="display:inline; margin: 0;">
                                                <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($row['room_no']); ?>">
                                                <input type="hidden" name="no_of_persons" value="<?php echo htmlspecialchars($row['no_of_persons']); ?>">
                                                <select name="occupancy_status" onchange="this.form.submit()">
                                                    <option value="Owner" <?php echo $row['occupancy_status'] == 'Owner' ? 'selected' : ''; ?>>Owned</option>
                                                    <option value="Rented" <?php echo $row['occupancy_status'] == 'Rented' ? 'selected' : ''; ?>>Rented</option>
                                                </select>
                                                <input type="hidden" name="update_occupancy" value="true">
                                            </form>
                                        </div>
                                        <?php if ($row['occupancy_status'] == 'Rented') : ?>
                                            <button class="tenant-btn" onclick="showTenantDetails('<?php echo htmlspecialchars($row['room_no']); ?>')">View/Edit Tenant</button>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="#" class="edit-button" onclick='showEditForm(<?php echo json_encode($row); ?>)'>Edit</a>
                                            <a href="?delete=<?php echo htmlspecialchars($row['room_no']); ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this member?');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else : ?>
                            <tr><td colspan="13">No records found</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Room Selection Modal -->
            <div id="roomSelectionModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">×</span>
                    <h2>Select Unit</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Room/Shop No</th>
                                <th>BHK</th>
                                <th>Sq Feet</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($room = $empty_rooms_result->fetch_assoc()) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($room['room_no']); ?></td>
                                    <td><?php echo htmlspecialchars($room['bhk']); ?></td>
                                    <td><?php echo htmlspecialchars($room['sq_feet']); ?></td>
                                    <td><button onclick="fillMemberForm('<?php echo htmlspecialchars($room['room_no']); ?>', '<?php echo htmlspecialchars($room['bhk']); ?>', '<?php echo htmlspecialchars($room['sq_feet']); ?>')">Select</button></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Add Member Modal -->
            <div id="addMemberModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">×</span>
                    <h2>Add New Member</h2>
                    <form method="POST" enctype="multipart/form-data" class="enhanced-form" onsubmit="return validateForm()">
                        <input type="hidden" name="add_member" value="true">
                        <div class="form-group">
                            <label for="room_no">Room/Shop No:</label>
                            <input type="text" id="room_no" name="room_no" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="bhk">BHK:</label>
                            <input type="text" id="bhk" name="bhk" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="sq_feet">Sq Feet:</label>
                            <input type="text" id="sq_feet" name="sq_feet" readonly required>
                        </div>
                        <div class="form-group">
                            <label for="fh_name">Flat Holder Name:</label>
                            <input type="text" id="fh_name" name="fh_name" required>
                        </div>
                        <div class="form-group">
                            <label for="category">Category:</label>
                            <input type="text" name="category" value="Owner" readonly>
                        </div>
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="contact">Contact:</label>
                            <input type="text" id="contact" name="contact" required>
                        </div>
                        <div class="form-group">
                            <label for="alternate_number">Alternate Contact:</label>
                            <input type="text" id="alternate_number" name="alternate_number">
                        </div>
                        <div class="form-group">
                            <label for="no_of_persons">No. of Persons:</label>
                            <input type="number" id="no_of_persons" name="no_of_persons" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="documents">Documents (Max 10):</label>
                            <input type="file" id="documents" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                        </div>
                        <div class="form-group">
                            <label for="joining_date">Joining Date:</label>
                            <input type="date" id="joining_date" name="joining_date" required>
                        </div>
                        <div class="form-group">
                            <label for="occupancy_status">Occupancy Status:</label>
                            <select id="occupancy_status" name="occupancy_status" required>
                                <option value="Owner">Owned</option>
                                <option value="Rented">Rented</option>
                            </select>
                        </div>
                        <input type="submit" value="Add Member" class="submit-button">
                    </form>
                </div>
            </div>

            <!-- Edit Member Modal -->
            <div id="editMemberModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">×</span>
                    <h2>Edit Member</h2>
                    <form method="POST" enctype="multipart/form-data" class="enhanced-form" onsubmit="return validateForm()">
                        <input type="hidden" name="edit_member" value="true">
                        <input type="hidden" id="edit_room_no_original" name="room_no_original">
                        <div class="form-group">
                            <label for="edit_room_no">Room/Shop No:</label>
                            <input type="text" id="edit_room_no" name="room_no" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_bhk">BHK:</label>
                            <input type="text" id="edit_bhk" name="bhk" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_sq_feet">Sq Feet:</label>
                            <input type="text" id="edit_sq_feet" name="sq_feet" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_fh_name">Flat Holder Name:</label>
                            <input type="text" id="edit_fh_name" name="fh_name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_category">Category:</label>
                            <input id="edit_category" type="text" name="category" value="Owner" readonly>  
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email:</label>
                            <input type="email" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_contact">Contact:</label>
                            <input type="text" id="edit_contact" name="contact" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_alternate_number">Alternate Contact:</label>
                            <input type="text" id="edit_alternate_number" name="alternate_number">
                        </div>
                        <div class="form-group">
                            <label for="edit_no_of_persons">No. of Persons:</label>
                            <input type="number" id="edit_no_of_persons" name="no_of_persons" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_documents">Documents (Max 10):</label>
                            <input type="file" id="edit_documents" name="documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                            <input type="hidden" id="edit_existing_documents" name="existing_documents">
                            <select id="edit_document_preview" class="document-select">
                                <option value="" disabled selected>Select a document</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="edit_joining_date">Joining Date:</label>
                            <input type="date" id="edit_joining_date" name="joining_date" readonly>
                        </div>
                        <div class="form-group">
                            <label for="edit_occupancy_status">Occupancy Status:</label>
                            <select id="edit_occupancy_status" name="occupancy_status" required>
                                <option value="Owner">Owner</option>
                                <option value="Rented">Rented</option>
                            </select>
                        </div>
                        <input type="submit" value="Update Member" class="submit-button">
                    </form>
                </div>
            </div>

            <!-- Tenant Details Modal -->
            <div id="tenantModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeModal()">×</span>
                    <h2>Tenant Details</h2>
                    <form method="POST" enctype="multipart/form-data" class="enhanced-form" onsubmit="return validateTenantForm()">
                        <input type="hidden" name="room_no" id="tenant_room_no">
                        <div class="form-group">
                            <label for="tenant_name">Tenant Name:</label>
                            <input type="text" id="tenant_name" name="tenant_name" required>
                        </div>
                        <div class="form-group">
                            <label for="tenant_email">Tenant Email:</label>
                            <input type="email" id="tenant_email" name="tenant_email" required>
                        </div>
                        <div class="form-group">
                            <label for="tenant_contact">Tenant Contact:</label>
                            <input type="text" id="tenant_contact" name="tenant_contact" required>
                        </div>
                        <div class="form-group">
                            <label for="tenant_alternate_number">Tenant Alternate Contact:</label>
                            <input type="text" id="tenant_alternate_number" name="tenant_alternate_number">
                        </div>
                        <div class="form-group">
                            <label for="tenant_no_of_persons">No. of Persons:</label>
                            <input type="number" id="tenant_no_of_persons" name="tenant_no_of_persons" min="1" required>
                        </div>
                        <div class="form-group">
                            <label for="tenant_documents">Tenant Documents (Max 10):</label>
                            <input type="file" id="tenant_documents" name="tenant_documents[]" multiple accept=".pdf,.doc,.docx,.jpg,.png">
                            <select id="tenant_document_preview" class="document-select">
                                <option value="" disabled selected>Select a document</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="tenant_joining_date">Tenant Joining Date:</label>
                            <input type="date" id="tenant_joining_date" name="tenant_joining_date" required>
                        </div>
                        <input type="submit" value="Save Tenant" class="submit-button" name="add_tenant">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/member.js?v=<?php echo time(); ?>"></script>
    <script src="js/script.js?v=<?php echo time(); ?>"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
    <script>
        function showEditForm(member) {
            document.getElementById('edit_room_no_original').value = member.room_no;
            document.getElementById('edit_room_no').value = member.room_no;
            document.getElementById('edit_bhk').value = member.bhk;
            document.getElementById('edit_sq_feet').value = member.sq_feet;
            document.getElementById('edit_fh_name').value = member.fh_name;
            document.getElementById('edit_category').value = member.category;
            document.getElementById('edit_email').value = member.email;
            document.getElementById('edit_contact').value = member.contact;
            document.getElementById('edit_alternate_number').value = member.alternate_number || '';
            document.getElementById('edit_no_of_persons').value = member.no_of_persons;
            document.getElementById('edit_joining_date').value = member.joining_date;
            document.getElementById('edit_existing_documents').value = member.documents || '[]';
            
            const documents = JSON.parse(member.documents || '[]');
            const docSelect = document.getElementById('edit_document_preview');
            docSelect.innerHTML = '<option value="" disabled selected>Select a document</option>';
            if (documents.length > 0) {
                documents.forEach(doc => {
                    const option = document.createElement('option');
                    option.value = doc;
                    option.textContent = doc.split('/').pop();
                    docSelect.appendChild(option);
                });
            } else {
                docSelect.innerHTML = '<option value="" disabled>No documents</option>';
            }
            
            document.getElementById('edit_occupancy_status').value = member.occupancy_status;
            document.getElementById('editMemberModal').style.display = 'block';
        }

        function showTenantDetails(room_no) {
            document.getElementById('tenantModal').style.display = 'block';
            document.getElementById('tenant_room_no').value = room_no;

            $.get('member.php', { action: 'get_tenant', room_no: room_no }, function(data) {
                var tenant = JSON.parse(data);
                if (tenant) {
                    document.getElementById('tenant_name').value = tenant.tenant_name || '';
                    document.getElementById('tenant_email').value = tenant.tenant_email || '';
                    document.getElementById('tenant_contact').value = tenant.tenant_contact || '';
                    document.getElementById('tenant_alternate_number').value = tenant.tenant_alternate_number || '';
                    document.getElementById('tenant_no_of_persons').value = tenant.tenant_no_of_persons || 0;
                    document.getElementById('tenant_joining_date').value = tenant.tenant_joining_date || '';
                    
                    const documents = JSON.parse(tenant.tenant_documents || '[]');
                    const docSelect = document.getElementById('tenant_document_preview');
                    docSelect.innerHTML = '<option value="" disabled selected>Select a document</option>';
                    if (documents.length > 0) {
                        documents.forEach(doc => {
                            const option = document.createElement('option');
                            option.value = doc;
                            option.textContent = doc.split('/').pop();
                            docSelect.appendChild(option);
                        });
                    } else {
                        docSelect.innerHTML = '<option value="" disabled>No documents</option>';
                    }
                } else {
                    document.getElementById('tenant_name').value = '';
                    document.getElementById('tenant_email').value = '';
                    document.getElementById('tenant_contact').value = '';
                    document.getElementById('tenant_alternate_number').value = '';
                    document.getElementById('tenant_no_of_persons').value = 0;
                    document.getElementById('tenant_joining_date').value = '';
                    document.getElementById('tenant_document_preview').innerHTML = '<option value="" disabled>No documents</option>';
                }
            });
        }

        function validateForm() {
            const noOfPersons = document.getElementById('no_of_persons')?.value || 
                               document.getElementById('edit_no_of_persons')?.value;
            if (parseInt(noOfPersons) < 1) {
                alert('Number of persons must be at least 1');
                return false;
            }
            return true;
        }

        function validateTenantForm() {
            const noOfPersons = document.getElementById('tenant_no_of_persons').value;
            if (parseInt(noOfPersons) < 1) {
                alert('Number of persons cannot be less than 1 for Tenant');
                return false;
            }
            return true;
        }

        // Function to restrict file selection to a maximum of 10 files
        function restrictFileSelection(input, maxFiles = 10) {
            if (input.files.length > maxFiles) {
                alert(`You can only upload a maximum of ${maxFiles} files.`);
                input.value = ''; // Clear the file input
                return false;
            }
            return true;
        }

        // Add event listeners for file inputs and document dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            // For Add Member form
            const addDocumentsInput = document.getElementById('documents');
            if (addDocumentsInput) {
                addDocumentsInput.addEventListener('change', function() {
                    restrictFileSelection(this);
                });
            }

            // For Edit Member form
            const editDocumentsInput = document.getElementById('edit_documents');
            if (editDocumentsInput) {
                editDocumentsInput.addEventListener('change', function() {
                    const existingDocuments = JSON.parse(document.getElementById('edit_existing_documents').value || '[]');
                    const totalFiles = existingDocuments.length + this.files.length;
                    if (totalFiles > 10) {
                        alert(`Total documents (existing + new) cannot exceed 10. You already have ${existingDocuments.length} documents.`);
                        this.value = ''; // Clear the file input
                    }
                });
            }

            // For Tenant Details form
            const tenantDocumentsInput = document.getElementById('tenant_documents');
            if (tenantDocumentsInput) {
                tenantDocumentsInput.addEventListener('change', function() {
                    restrictFileSelection(this);
                });
            }

            // Document dropdowns
            const dropdowns = document.querySelectorAll('.document-select');
            dropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function() {
                    const selectedValue = this.value;
                    if (selectedValue && selectedValue !== '') {
                        window.open(selectedValue, '_blank');
                        this.value = ''; // Reset to default option
                    }
                });
            });
        });
    </script>
    <?php $conn->close(); ?>
</body>
</html>