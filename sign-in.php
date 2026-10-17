<?php
session_start();
include 'db_connect.php';  // Database connection

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle login
    if (isset($_POST['login'])) {
        $room_no = $_POST['room_no'];
        $password = $_POST['password'];

        // Check login credentials
        $stmt = $conn->prepare("SELECT room_no, password FROM logins WHERE room_no = ? AND password = ?");
        $stmt->bind_param("ss", $room_no, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Fetch flat_holder data including contact
            $stmt2 = $conn->prepare("SELECT room_no, fh_name, category, joining_date, contact, email FROM flat_holder WHERE room_no = ?");
            $stmt2->bind_param("s", $room_no);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows > 0) {
                $row = $result2->fetch_assoc();
                
                // Store necessary data in session including contact
                $_SESSION['room_no'] = $row['room_no'];
                $_SESSION['fh_name'] = $row['fh_name'];
                $_SESSION['category'] = $row['category'];
                $_SESSION['joining_date'] = $row['joining_date'];
                $_SESSION['contact'] = $row['contact'];
                $_SESSION['email'] = $row['email'];

                // Fetch payment details
                $stmt3 = $conn->prepare("SELECT name, room_no, category, amount, date, time FROM payment WHERE room_no = ?");
                $stmt3->bind_param("s", $room_no);
                $stmt3->execute();
                $_SESSION['payment_details'] = $stmt3->get_result()->fetch_all(MYSQLI_ASSOC);

                // Fetch complaint details
                $stmt5 = $conn->prepare("SELECT room_no, name, description, date, time, reply FROM complaint WHERE room_no = ?");
                $stmt5->bind_param("s", $room_no);
                $stmt5->execute();
                $_SESSION['complaint_details'] = $stmt5->get_result()->fetch_all(MYSQLI_ASSOC);

                // Redirect to dashboard after successful login
                header("Location: home2.php");
                exit();
            } else {
                $error_message = "No information found for this room number.";
            }
        } else {
            $error_message = "Incorrect room number or password.";
        }
    }

    // Handle password reset (baki code same hai)
    elseif (isset($_POST['reset_password'])) {
        $room_no = $_POST['reset_room_no'];
        $old_password = $_POST['old_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Verify old password
        $stmt = $conn->prepare("SELECT password FROM logins WHERE room_no = ?");
        $stmt->bind_param("s", $room_no);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            
            if ($row['password'] === $old_password) {
                if ($new_password === $confirm_password) {
                    $stmt = $conn->prepare("UPDATE logins SET password = ? WHERE room_no = ?");
                    $stmt->bind_param("ss", $new_password, $room_no);

                    if ($stmt->execute()) {
                        $success_message = "Password has been reset.";
                    } else {
                        $error_message = "Password could not be reset.";
                    }
                } else {
                    $error_message = "Passwords do not match.";
                }
            } else {
                $error_message = "Old password is incorrect.";
            }
        } else {
            $error_message = "Room number is not valid.";
        }
    }

    // Handle registration (baki code same hai)
    elseif (isset($_POST['register'])) {
        $new_room_no = trim($_POST['new_room_no']); // Extra spaces hatane ke liye
        $create_password = $_POST['create_password'];
        $reenter_password = $_POST['reenter_password'];

        // Room number format check (e.g., A/101)
        if (!preg_match('/^[A-Za-z]\/[0-9]{3}$/', $new_room_no)) {
            $error_message = "Room number format is incorrect. Use format like 'A/101'.";
        } elseif ($create_password === $reenter_password) {
            // Check if room number already exists
            $stmt = $conn->prepare("SELECT room_no FROM logins WHERE room_no = ?");
            $stmt->bind_param("s", $new_room_no);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO logins (room_no, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $new_room_no, $create_password);

                if ($stmt->execute()) {
                    $success_message = "Registration successful for room $new_room_no.";
                } else {
                    $error_message = "User could not be registered. Error: " . $conn->error;
                }
            } else {
                $error_message = "This room number '$new_room_no' already exists.";
            }
        } else {
            $error_message = "Passwords do not match.";
        }
    }

    // Handle help request (baki code same hai)
    elseif (isset($_POST['send_help'])) {
        $help_email = $_POST['help_email'];

        // Simulate sending email (live server pe mail() uncomment karna)
        $to = "mysociety.help@gmail.com";
        $subject = "Help Request from $help_email";
        $message = "A user has requested help with their account. Please assist them.";
        $headers = "From: $help_email";

        // mail($to, $subject, $message, $headers);

        $success_message = "Your request has been successfully submitted.";
    }

    // Close all prepared statements
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($stmt2)) {
        $stmt2->close();
    }
    if (isset($stmt3)) {
        $stmt3->close();
    }
    if (isset($stmt5)) {
        $stmt5->close();
    }
}

// Close the database connection
$conn->close();
?>


<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>MySociety.com</title>

    <!-- CSS FILES -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/slick.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">

    <style>
    body {
        justify-content: center;
        align-items: center;
        background: url('images/slideshow/s4.jpg') no-repeat center center fixed;
        background-size: cover;
    }

    .container11 {
        background: rgba(255, 255, 255, 0.5);
        padding: 30px;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        width: 100%;
        max-width: 350px;
        max-height: 500px;
        height: 100%;
        text-align: center;
        backdrop-filter: blur(10px);
        margin: 5px auto auto auto;
        
    }

    .reg {
        margin-top: 0;
        color: #333;
        font-size: 2em;
        margin-bottom: 5px;
    }

    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
        color: #555;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-sizing: border-box;
        outline: none;
        transition: border-color 0.3s, box-shadow 0.3s;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    }

    button {
        width: 100%;
        padding: 12px;
        background-color: #007bff;
        color: #fff;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 1.1em;
        transition: background-color 0.3s, transform 0.3s;
    }

    button:hover {
        background-color: #0056b3;
        transform: scale(1.02);
    }

    button:active {
        background-color: #004494;
        transform: scale(1);
    }

    .forgot-password,
    .register-link,
    .success-message,
    .error-message {
        margin-top: 15px;
        font-size: 0.9em;
    }

    .forgot-password a,
    .register-link a {
        color: #007bff;
        text-decoration: none;
    }

    .forgot-password a:hover,
    .register-link a:hover {
        text-decoration: underline;
    }

    .success-message {
        color: green;
    }

    .error-message {
        color: red;
    }

    /* Modal Overlay */
    .modalss {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.7);
        justify-content: center;
        align-items: center;
    }

    /* Modal Content */
    .modal-content2 {
        background: linear-gradient(135deg, #ffffff, #f0f0f0);
        margin: auto;
        padding: 30px;
        border-radius: 15px;
        width: 90%;
        max-width: 450px;
        position: relative;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.2);
        animation: fadeInModal 0.5s ease-in-out;
        text-align: center;
    }

    /* Modal Header */
    .modal-headers {
        margin-bottom: 20px;
    }

    .modal-headers h2 {
        font-size: 1.8em;
        color: #333;
        font-weight: bold;
        text-align: center;
        border-bottom: 2px solid #007bff;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }

    /* Form Styling */
    .form-group {
        margin-bottom: 20px;
        position: relative;
    }

    label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: #555;
    }

    input[type="text"],
    input[type="password"] {
        width: 100%;
        padding: 12px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 1em;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease-in-out;
        background: rgba(255, 255, 255, 0.8);
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
        border-color: #007bff;
        box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
        background: rgba(255, 255, 255, 1);
    }

    /* Submit Button */
    button {
        width: 100%;
        padding: 12px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 1.1em;
        cursor: pointer;
        transition: background-color 0.3s ease-in-out, transform 0.3s ease-in-out;
        box-shadow: 0 3px 15px rgba(0, 123, 255, 0.3);
    }

    button:hover {
        background-color: #0056b3;
        transform: translateY(-3px);
    }

    button:active {
        background-color: #004494;
        transform: translateY(0);
    }

    /* Close Button */
    .close {
        position: absolute;
        top: 10px;
        right: 15px;
        font-size: 1.8em;
        color: #888;
        cursor: pointer;
        transition: color 0.3s ease;
    }

    .close:hover {
        color: #ff0000;
    }

    /* Animation for Modal */
    @keyframes fadeInModal {
        from {
            transform: scale(0.9);
            opacity: 0;
        }
        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    /* Responsive Design */
    @media (max-width: 600px) {
        .modal-content2 {
            padding: 20px;
            width: 90%;
        }

        h2 {
            font-size: 1.5em;
        }

        button {
            font-size: 1em;
            padding: 10px;
        }
    }

    /* Error and Success Messages */
    .error-message,
    .success-message {
        font-size: 0.9em;
        margin: auto auto auto auto;
        text-align: center;
        padding: 10px;
        border-radius: 5px;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }

    .error-message.show,
    .success-message.show {
        opacity: 1;
    }

    .error-message {
        background-color: rgba(255, 0, 0, 0.1);
        color: red;
    }

    .success-message {
        background-color: rgba(0, 255, 0, 0.1);
        color: green;
        margin: auto auto auto auto;
    }
    </style>
</head>
    
<body>
    <section class="preloader">
        <div class="spinner">
            <span class="sk-inner-circle"></span>
        </div>
    </section>
    
    <main>
        <section class="sign-in-form section-padding">
            <div class="container11">
                <h2>Sign - In</h2>
                <form id="loginForm" method="POST" action="">
                    <div class="form-group">
                        <label for="room_no">Room No</label>
                        <input type="text" id="room_no" name="room_no" placeholder="e.g., A/101" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                    <div class="forgot-password">
                        <a href="#" id="showResetForm">Change your password</a>
                    </div>
                    <div class="register-link">
                        <a href="#" id="showRegisterForm">Create an account</a>
                    </div>
                </form>

                <div class="error-message <?php echo !empty($error_message) ? 'show' : ''; ?>">
                    <?php echo $error_message; ?>
                </div>
                <div class="success-message <?php echo !empty($success_message) ? 'show' : ''; ?>">
                    <?php echo $success_message; ?>
                </div>
            </div>

            <!-- Reset Password Modal -->
            <div id="resetModal" class="modalss" style="display: none;">
                <div class="modal-content2">
                    <span class="close" id="closeResetModal">×</span>
                    <div class="modal-headers">
                        <h2 class="reg">Reset Password</h2>
                    </div>
                    <form id="resetForm" method="POST" action="">
                        <div class="form-group">
                            <label for="reset_room_no">Room No</label>
                            <input type="text" id="reset_room_no" name="reset_room_no" placeholder="e.g., A/101" required>
                        </div>
                        <div class="form-group">
                            <label for="old_password">Old Password</label>
                            <input type="password" id="old_password" name="old_password" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" name="reset_password">Reset Password</button>
                    </form>
                </div>
            </div>

            <!-- Register Modal -->
            <div id="registerModal" class="modalss" style="display: none;">
                <div class="modal-content2">
                    <span class="close" id="closeRegisterModal">×</span>
                    <div class="modal-headers">
                        <h2 class="reg">Register</h2>
                    </div>
                    <form id="registerForm" method="POST" action="">
                        <div class="form-group">
                            <label for="new_room_no">Room No</label>
                            <input type="text" id="new_room_no" name="new_room_no" required placeholder="e.g., A/101">
                        </div>
                        <div class="form-group">
                            <label for="create_password">Password</label>
                            <input type="password" id="create_password" name="create_password" required>
                        </div>
                        <div class="form-group">
                            <label for="reenter_password">Confirm Password</label>
                            <input type="password" id="reenter_password" name="reenter_password" required>
                        </div>
                        <button type="submit" name="register">Register</button>
                    </form>
                </div>
            </div>

            <script>
                // Show/Hide Modals
                document.getElementById('showResetForm').onclick = function() {
                    document.getElementById('resetModal').style.display = 'flex';
                };

                document.getElementById('showRegisterForm').onclick = function() {
                    document.getElementById('registerModal').style.display = 'flex';
                };

                document.getElementById('closeResetModal').onclick = function() {
                    document.getElementById('resetModal').style.display = 'none';
                };

                document.getElementById('closeRegisterModal').onclick = function() {
                    document.getElementById('registerModal').style.display = 'none';
                };

                // Close modals if clicked outside of the modal content
                window.onclick = function(event) {
                    if (event.target == document.getElementById('resetModal')) {
                        document.getElementById('resetModal').style.display = 'none';
                    }
                    if (event.target == document.getElementById('registerModal')) {
                        document.getElementById('registerModal').style.display = 'none';
                    }
                };
            </script>
        </section>
    </main>

    <?php
    include 'footer.php';
    ?>

</body>
</html>