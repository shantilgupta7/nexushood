<?php
// Start the session
session_start();

// Database connection
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

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle login
    if (isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Server-side validation: Ensure username contains only letters and numbers
        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $error_message = "Username must contain only letters and numbers.";
        } else {
            // Prepare and bind for login
            $stmt = $conn->prepare("SELECT * FROM login WHERE username = ? AND password = ?");
            $stmt->bind_param("ss", $username, $password);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Successful login, set session
                $_SESSION['username'] = $username;
                header("Location: mysociety.php");
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }

            $stmt->close();
        }
    } 
    // Handle reset password
    elseif (isset($_POST['reset_password'])) {
        $username = $_POST['reset_username'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Server-side validation: Ensure username contains only letters and numbers
        if (!preg_match('/^[a-zA-Z0-9]+$/', $username)) {
            $error_message = "Username must contain only letters and numbers.";
        } else {
            // Check if username exists in the database
            $stmt = $conn->prepare("SELECT * FROM login WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                if ($new_password === $confirm_password) {
                    // Update the password
                    $stmt = $conn->prepare("UPDATE login SET password = ? WHERE username = ?");
                    $stmt->bind_param("ss", $new_password, $username);

                    if ($stmt->execute()) {
                        $success_message = "Password updated successfully.";
                    } else {
                        $error_message = "Failed to update password.";
                    }
                } else {
                    $error_message = "Passwords do not match.";
                }
            } else {
                $error_message = "Username not valid.";
            }

            $stmt->close();
        }
    }
    // Handle account creation
    elseif (isset($_POST['create_account'])) {
        $new_username = $_POST['new_username'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Server-side validation: Ensure username contains only letters and numbers
        if (!preg_match('/^[a-zA-Z0-9]+$/', $new_username)) {
            $error_message = "Username must contain only letters and numbers.";
        } elseif ($new_password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT * FROM login WHERE username = ?");
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error_message = "Username already exists.";
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO login (username, password) VALUES (?, ?)");
                $stmt->bind_param("ss", $new_username, $new_password);

                if ($stmt->execute()) {
                    $success_message = "Account created successfully. Please login.";
                } else {
                    $error_message = "Failed to create account.";
                }
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login & Create Account</title>
    <style>
        /* General Styles */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: url('img/login4.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .flip-container {
            perspective: 1000px;
            width: 100%;
            max-width: 238px; /* Reduced by 30% from 340px */
            margin: 0 auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .flipper {
            position: relative;
            width: 100%;
            height: 420px;
            transition: transform 0.6s ease-in-out;
            transform-style: preserve-3d;
        }

        .flipper.flipped {
            transform: rotateX(180deg);
        }

        .front, .back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            background: rgba(255, 255, 255, 0.2);
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            text-align: center;
            backdrop-filter: blur(10px);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .back {
            transform: rotateX(180deg);
        }

        h2 {
            margin: 0 0 15px;
            color: #333;
            font-size: 1.8em;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #555;
            font-size: 0.9em;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            box-sizing: border-box;
            outline: none;
            transition: border-color 0.3s, box-shadow 0.3s;
            font-size: 0.9em;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
        }

        button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1em;
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

        .links-container {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.85em;
        }

        .forgot-password a,
        .create-account a,
        .back-to-login a {
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password a:hover,
        .create-account a:hover,
        .back-to-login a:hover {
            text-decoration: underline;
        }

        .success-message {
            color: green;
            margin-top: 10px;
            font-size: 0.85em;
        }

        .error-message {
            color: red;
            margin-top: 10px;
            font-size: 0.85em;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: hidden; /* Changed to hidden to remove scrolling */
            background-color: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        .modal.show {
            opacity: 1;
        }

        .modal-content {
            background-color: #fff;
            margin: 15% auto;
            padding: 20px;
            border-radius: 8px;
            width: 80%;
            max-width: 238px; /* Matches existing width */
            position: relative;
            transform: translateY(-50px);
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
            opacity: 0;
            overflow: hidden; /* Added to prevent internal scrolling */
            box-sizing: border-box; /* Ensure padding is included in width */
        }

        .modal.show .modal-content {
            transform: translateY(0);
            opacity: 1;
        }

        .modal-header {
            font-size: 1.5em;
            margin-bottom: 10px;
        }

        .modal-body {
            margin-bottom: 15px;
        }

        .modal-footer {
            text-align: right;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 20px;
            font-size: 1.5em;
            font-weight: bold;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="flip-container">
        <div class="flipper" id="flipper">
            <!-- Front: Login Form -->
            <div class="front">
                <h2>Login</h2>
                <form id="loginForm" method="POST" action="">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" pattern="[a-zA-Z0-9]+" title="Username must contain only letters and numbers" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <button type="submit" name="login">Login</button>
                    <div class="links-container">
                        <div class="forgot-password">
                            <a href="#" id="showResetForm">Forgot your password?</a>
                        </div>
                        <div class="create-account">
                            <a href="#" id="showCreateAccountForm">Create new account</a>
                        </div>
                    </div>
                </form>
                <div class="error-message"><?php echo $error_message; ?></div>
                <div class="success-message"><?php echo $success_message; ?></div>
            </div>
            <!-- Back: Create Account Form -->
            <div class="back">
                <h2>Create Account</h2>
                <form id="createAccountForm" method="POST" action="">
                    <div class="form-group">
                        <label for="new_username">Username</label>
                        <input type="text" id="new_username" name="new_username" pattern="[a-zA-Z0-9]+" title="Username must contain only letters and numbers" required>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" name="create_account">Create Account</button>
                    <div class="back-to-login">
                        <a href="#" id="backToLogin">Back to Login</a>
                    </div>
                </form>
                <div class="error-message"><?php echo $error_message; ?></div>
                <div class="success-message"><?php echo $success_message; ?></div>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeResetModal">×</span>
            <div class="modal-header">
                <h2>Reset Password</h2>
            </div>
            <form id="resetForm" method="POST" action="">
                <div class="form-group">
                    <label for="reset_username">Username</label>
                    <input type="text" id="reset_username" name="reset_username" pattern="[a-zA-Z0-9]+" title="Username must contain only letters and numbers" required>
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

    <script>
        // JavaScript for modal and flip functionality
        // Reset Password Modal
        document.getElementById('showResetForm').addEventListener('click', function(event) {
            event.preventDefault();
            const modal = document.getElementById('resetModal');
            modal.style.display = 'flex';
            setTimeout(() => modal.classList.add('show'), 10); // Delay to trigger transition
        });

        document.getElementById('closeResetModal').addEventListener('click', function() {
            const modal = document.getElementById('resetModal');
            modal.classList.remove('show');
            setTimeout(() => modal.style.display = 'none', 300); // Wait for transition to complete
        });

        // Flip effect for Create Account
        const flipper = document.getElementById('flipper');
        document.getElementById('showCreateAccountForm').addEventListener('click', function(event) {
            event.preventDefault();
            flipper.classList.add('flipped');
        });

        document.getElementById('backToLogin').addEventListener('click', function(event) {
            event.preventDefault();
            flipper.classList.remove('flipped');
        });

        // Client-side validation for username
        const usernameInputs = document.querySelectorAll('input[name="username"], input[name="reset_username"], input[name="new_username"]');
        usernameInputs.forEach(input => {
            input.addEventListener('input', function() {
                const value = this.value;
                if (!/^[a-zA-Z0-9]*$/.test(value)) {
                    this.setCustomValidity("Username must contain only letters and numbers.");
                } else {
                    this.setCustomValidity("");
                }
            });
        });
    </script>
</body>
</html>