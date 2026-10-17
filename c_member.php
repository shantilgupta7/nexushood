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
    <link rel="stylesheet" href="css/committee.css?v=<?php echo time(); ?>">
</head>
<body>

<!--===================== sidebar start ==========================-->	 
<?php include 'admin_sidebar.php'; ?>
<!--===================== sidebar end ============================-->	

<!--===================== top bar start ==========================-->	 
<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
    </div>
<!--===================== top bar end ============================-->	
	 
    <!--===================== table Start ==========================-->
    
    <?php
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

    $errors = [];

    // Handle adding new member
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_member'])) {
        $name = trim($_POST['name']);
        $designation = trim($_POST['designation']);
        $dob = $_POST['dob'];
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $join_date = $_POST['join_date'];
        $document = $_FILES['document']['name'];

        // Server-side validations
        if (empty($name) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $name)) {
            $errors[] = "Name must be 2-50 characters long and contain only letters and spaces.";
        }
        if (empty($designation) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $designation)) {
            $errors[] = "Designation must be 2-50 characters long and contain only letters and spaces.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($phone) || !preg_match("/^\d{10}$/", $phone)) {
            $errors[] = "Phone number must be exactly 10 digits.";
        }
        if (empty($dob) || strtotime($dob) > time() || date_diff(date_create($dob), date_create('today'))->y < 18) {
            $errors[] = "DOB must be a valid past date and member must be at least 18 years old.";
        }
        if (empty($join_date) || strtotime($join_date) > time()) {
            $errors[] = "Join date must be a valid past or current date.";
        }

        if (empty($errors)) {
            $age = date_diff(date_create($dob), date_create('today'))->y;
            $image_url = $_FILES['image']['name'];
            if ($image_url) {
                $target_dir = "images/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $target_file = $target_dir . basename($image_url);
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            } else {
                $image_url = "";
            }
            if ($document) {
                $document_dir = "Uploads/documents/";
                if (!is_dir($document_dir)) {
                    mkdir($document_dir, 0777, true);
                }
                $document_file = $document_dir . time() . '_' . basename($document);
                if (!move_uploaded_file($_FILES["document"]["tmp_name"], $document_file)) {
                    $errors[] = "Error uploading document: " . $_FILES["document"]["error"];
                }
            } else {
                $document_file = "";
            }

            if (empty($errors)) {
                $sql = "INSERT INTO commitee (name, designation, dob, age, email, phone, join_date, document, image_url) 
                        VALUES ('$name', '$designation', '$dob', '$age', '$email', '$phone', '$join_date', '$document_file', '$image_url')";

                if ($conn->query($sql) === TRUE) {
                    header("Location: c_member.php");
                    exit();
                } else {
                    $errors[] = "Error adding new record: " . $conn->error;
                }
            }
        }
    }

    // Handle editing
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['edit_id'])) {
        $id = $_POST['edit_id'];
        $name = trim($_POST['name']);
        $designation = trim($_POST['designation']);
        $dob = $_POST['dob'];
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $join_date = $_POST['join_date'];
        $document = $_FILES['document']['name'];

        // Server-side validations
        if (empty($name) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $name)) {
            $errors[] = "Name must be 2-50 characters long and contain only letters and spaces.";
        }
        if (empty($designation) || !preg_match("/^[a-zA-Z\s]{2,50}$/", $designation)) {
            $errors[] = "Designation must be 2-50 characters long and contain only letters and spaces.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        }
        if (empty($phone) || !preg_match("/^\d{10}$/", $phone)) {
            $errors[] = "Phone number must be exactly 10 digits.";
        }
        if (empty($dob) || strtotime($dob) > time() || date_diff(date_create($dob), date_create('today'))->y < 18) {
            $errors[] = "DOB must be a valid past date and member must be at least 18 years old.";
        }
        if (empty($join_date) || strtotime($join_date) > time()) {
            $errors[] = "Join date must be a valid past or current date.";
        }

        if (empty($errors)) {
            $age = date_diff(date_create($dob), date_create('today'))->y;
            $image_url = $_FILES['image']['name'];
            if ($image_url) {
                $target_dir = "images/";
                if (!is_dir($target_dir)) {
                    mkdir($target_dir, 0777, true);
                }
                $target_file = $target_dir . basename($image_url);
                move_uploaded_file($_FILES["image"]["tmp_name"], $target_file);
            } else {
                $sql = "SELECT image_url FROM commitee WHERE id=$id";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $image_url = $row['image_url'];
            }
            if ($document) {
                $document_dir = "Uploads/documents/";
                if (!is_dir($document_dir)) {
                    mkdir($document_dir, 0777, true);
                }
                $document_file = $document_dir . time() . '_' . basename($document);
                if (!move_uploaded_file($_FILES["document"]["tmp_name"], $document_file)) {
                    $errors[] = "Error uploading document: " . $_FILES["document"]["error"];
                }
            } else {
                $sql = "SELECT document FROM commitee WHERE id=$id";
                $result = $conn->query($sql);
                $row = $result->fetch_assoc();
                $document_file = $row['document'];
            }

            if (empty($errors)) {
                $sql = "UPDATE commitee SET name='$name', designation='$designation', dob='$dob', age='$age', email='$email', phone='$phone', join_date='$join_date', document='$document_file', image_url='$image_url' WHERE id=$id";

                if ($conn->query($sql) === TRUE) {
                    header("Location: c_member.php");
                    exit();
                } else {
                    $errors[] = "Error updating record: " . $conn->error;
                }
            }
        }
    }

    // Handle deleting a member
    if (isset($_GET['delete'])) {
        $id = $_GET['delete'];
        $sql = "DELETE FROM commitee WHERE id=$id";
        if ($conn->query($sql) === TRUE) {
            header("Location: c_member.php");
            exit();
        } else {
            $errors[] = "Error deleting record: " . $conn->error;
        }
    }

    // Fetch committee members for display
    $sql = "SELECT * FROM commitee";
    $result = $conn->query($sql);
    ?>

    <div class="container9">
        <h2>Committee Members</h2>

        <div class="action-bar">
            <a href="#" class="add-button" onclick="openAddModal()">Add New Member</a>
        </div>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Profile Image</th>
                        <th>Name</th>
                        <th>Designation</th>
                        <th>DOB</th>
                        <th>Age</th>
                        <th>Email</th>
                        <th>Phone No</th>
                        <th>Joining Date</th>
                        <th>Document</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0) : ?>
                        <?php while ($row = $result->fetch_assoc()) : ?>
                            <?php
                            $dob = isset($row['dob']) && !empty($row['dob']) ? $row['dob'] : null;
                            $age = $dob ? date_diff(date_create($dob), date_create('today'))->y : (isset($row['age']) ? $row['age'] : 'N/A');
                            ?>
                            <tr>
                                <td><?php echo isset($row['id']) ? htmlspecialchars($row['id']) : 'N/A'; ?></td>
                                <td><img src="images/<?php echo isset($row['image_url']) ? htmlspecialchars($row['image_url']) : 'default.jpg'; ?>" class="profile-image" onclick="openImageModal(this)"></td>
                                <td><?php echo isset($row['name']) ? htmlspecialchars($row['name']) : 'N/A'; ?></td>
                                <td><?php echo isset($row['designation']) ? htmlspecialchars($row['designation']) : 'N/A'; ?></td>
                                <td>
                                    <?php
                                    if ($dob && strtotime($dob) !== false) {
                                        echo date("d-m-Y", strtotime($dob));
                                    } else {
                                        echo 'N/A';
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($age); ?></td>
                                <td><?php echo isset($row['email']) ? htmlspecialchars($row['email']) : 'N/A'; ?></td>
                                <td><?php echo isset($row['phone']) ? htmlspecialchars($row['phone']) : 'N/A'; ?></td>
                                <td>
                                    <?php
                                    $originalDate = isset($row['join_date']) ? $row['join_date'] : null;
                                    echo $originalDate ? date("d-m-Y", strtotime($originalDate)) : 'N/A';
                                    ?>
                                </td>
                                <td>
                                    <?php if (isset($row['document']) && !empty($row['document'])): ?>
                                        <a href="<?php echo htmlspecialchars($row['document']); ?>" target="_blank">View</a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="#" class="edit-button" onclick="openEditModal(<?php echo htmlspecialchars(json_encode($row)); ?>)">Edit</a>
                                        <a href="?delete=<?php echo $row['id']; ?>" class="delete-button" onclick="return confirm('Are you sure you want to delete this member?');">Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="11">No records found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Add New Member Modal -->
        <div id="addModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddModal()">×</span>
                <h3>Add New Member</h3>
                <form method="POST" enctype="multipart/form-data" id="addForm" onsubmit="return validateForm('add')">
                    <input type="hidden" name="add_member" value="true">
                    <div class="form-row">
                        <div>
                            <label for="add_name">Name:</label>
                            <input type="text" id="add_name" name="name" required>
                        </div>
                        <div>
                            <label for="add_designation">Designation:</label>
                            <input type="text" id="add_designation" name="designation" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="add_dob">DOB:</label>
                            <input type="date" id="add_dob" name="dob" required>
                        </div>
                        <div>
                            <label for="add_age">Age:</label>
                            <input type="text" id="add_age" name="age" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="add_email">Email:</label>
                            <input type="text" id="add_email" name="email" required>
                        </div>
                        <div>
                            <label for="add_phone">Phone:</label>
                            <input type="text" id="add_phone" name="phone" required>
                        </div>
                    </div>
                    <label for="add_join_date">Join Date:</label>
                    <input type="date" id="add_join_date" name="join_date" required>
                    <label for="add_document">Document:</label>
                    <input type="file" id="add_document" name="document" accept=".pdf,.doc,.docx">
                    <label for="add_image">Profile Image:</label>
                    <input type="file" id="add_image" name="image">
                    <div class="error" id="add_error">
                        <?php if (!empty($errors)): ?>
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="submit" value="Add Member">
                </form>
            </div>
        </div>

        <!-- Edit Modal -->
        <div id="editModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEditModal()">×</span>
                <h3>Edit Member</h3>
                <form method="POST" enctype="multipart/form-data" id="editForm" onsubmit="return validateForm('edit')">
                    <input type="hidden" name="edit_id" id="edit_id">
                    <div class="form-row">
                        <div>
                            <label for="name">Name:</label>
                            <input type="text" id="name" name="name" required>
                        </div>
                        <div>
                            <label for="designation">Designation:</label>
                            <input type="text" id="designation" name="designation" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="dob">DOB:</label>
                            <input type="date" id="dob" name="dob" required>
                        </div>
                        <div>
                            <label for="age">Age:</label>
                            <input type="text" id="age" name="age" readonly>
                        </div>
                    </div>
                    <div class="form-row">
                        <div>
                            <label for="email">Email:</label>
                            <input type="text" id="email" name="email" required>
                        </div>
                        <div>
                            <label for="phone">Phone:</label>
                            <input type="text" id="phone" name="phone" required>
                        </div>
                    </div>
                    <label for="join_date">Join Date:</label>
                    <input type="date" id="join_date" name="join_date" required>
                    <label for="document">Document:</label>
                    <input type="file" id="document" name="document" accept=".pdf,.doc,.docx">
                    <div id="currentDocument"></div>
                    <label for="image">Profile Image:</label>
                    <input type="file" id="image" name="image">
                    <div class="error" id="edit_error">
                        <?php if (!empty($errors)): ?>
                            <?php foreach ($errors as $error): ?>
                                <div><?php echo htmlspecialchars($error); ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <input type="submit" value="Update Member">
                </form>
            </div>
        </div>
    </div>

    <!-- Image Modal -->
    <div id="imageModal">
        <span class="close" onclick="closeImageModal()">×</span>
        <img class="modal-content" id="img01">
    </div>
    <!--===================== table end ==========================-->
</div>

<!--===================== Javascript link ==========================-->	 
<script src="js/script.js"></script>
<script src="js/committee.js?v=<?php echo time(); ?>"></script>
<script>
function validateForm(formType) {
    const prefix = formType === 'add' ? 'add_' : '';
    const name = document.getElementById(prefix + 'name').value.trim();
    const designation = document.getElementById(prefix + 'designation').value.trim();
    const dob = document.getElementById(prefix + 'dob').value;
    const email = document.getElementById(prefix + 'email').value.trim();
    const phone = document.getElementById(prefix + 'phone').value.trim();
    const join_date = document.getElementById(prefix + 'join_date').value;
    const errorDiv = document.getElementById(formType + '_error');
    errorDiv.innerHTML = '';

    let errors = [];

    // Name validation
    if (!name || !/^[a-zA-Z\s]{2,50}$/.test(name)) {
        errors.push('Name must be 2-50 characters long and contain only letters and spaces.');
    }

    // Designation validation
    if (!designation || !/^[a-zA-Z\s]{2,50}$/.test(designation)) {
        errors.push('Designation must be 2-50 characters long and contain only letters and spaces.');
    }

    // Email validation
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!email || !emailPattern.test(email)) {
        errors.push('Invalid email format.');
    }

    // Phone validation
    if (!phone || !/^\d{10}$/.test(phone)) {
        errors.push('Phone number must be exactly 10 digits.');
    }

    // DOB validation
    const dobDate = new Date(dob);
    const today = new Date();
    const ageDiff = (today - dobDate) / (1000 * 60 * 60 * 24 * 365.25);
    if (!dob || dobDate > today || ageDiff < 18) {
        errors.push('DOB must be a valid past date and member must be at least 18 years old.');
    }

    // Join date validation
    const joinDate = new Date(join_date);
    if (!join_date || joinDate > today) {
        errors.push('Join date must be a valid past or current date.');
    }

    // Display errors
    if (errors.length > 0) {
        errorDiv.innerHTML = errors.map(error => `<div>${error}</div>`).join('');
        return false;
    }

    // Update age field
    if (dob) {
        document.getElementById(prefix + 'age').value = Math.floor(ageDiff);
    }

    return true;
}

// Real-time age calculation for DOB
function updateAge(prefix) {
    const dobInput = document.getElementById(prefix + 'dob');
    const ageInput = document.getElementById(prefix + 'age');
    dobInput.addEventListener('change', () => {
        const dob = new Date(dobInput.value);
        const today = new Date();
        const age = Math.floor((today - dob) / (1000 * 60 * 60 * 24 * 365.25));
        ageInput.value = age >= 0 ? age : '';
    });
}

updateAge('add_');
updateAge('');

// Real-time validation for input fields
['add_', ''].forEach(prefix => {
    ['name', 'designation', 'email', 'phone', 'dob', 'join_date'].forEach(field => {
        const input = document.getElementById(prefix + field);
        if (input) {
            input.addEventListener('input', () => validateForm(prefix ? 'add' : 'edit'));
        }
    });
});
</script>
<!--===================== icons link ==========================-->	
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>

</body>
</html>

<?php
if (is_object($conn) && $conn->ping()) {
    $conn->close();
}
?>