<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login, Register & Reset Password Form</title>
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
            background: url('img/login.jpg') no-repeat center center fixed;
            background-size: cover;
        }

        .container {
            background: rgba(255, 255, 255, 0.6);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 300px;
            text-align: center;
            backdrop-filter: blur(10px);
        }

        h2 {
            margin-top: 0;
            color: #333;
            font-size: 2em;
            margin-bottom: 20px;
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
            background: linear-gradient(45deg, #007bff, #00c3ff);
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1.1em;
            transition: background 0.3s, transform 0.3s;
        }

        button:hover {
            background: linear-gradient(45deg, #0056b3, #008cff);
            transform: scale(1.05);
        }

        button:active {
            background: linear-gradient(45deg, #004494, #0077d1);
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

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
            transition: opacity 0.5s ease;
        }

        .modal-content {
            background-color: #fff;
            margin: auto;
            padding: 20px;
            border-radius: 12px;
            width: 90%;
            max-width: 400px;
            position: relative;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            transform: scale(0.9);
            transition: transform 0.3s ease, opacity 0.3s ease;
            opacity: 0;
        }

        .modal-header {
            font-size: 1.5em;
            margin-bottom: 10px;
            color: #007bff;
        }

        .modal-body {
            margin-bottom: 20px;
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
            color: #007bff;
        }

        /* Modal Show Animation */
        .modal.show {
            display: flex;
            opacity: 1;
        }

        .modal.show .modal-content {
            transform: scale(1);
            opacity: 1;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form id="loginForm" method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
            <div class="forgot-password">
                <a href="#" id="showResetForm">Forgot your password?</a>
            </div>
            <div class="register-link">
                <a href="#" id="showRegisterForm">Create an account</a>
            </div>
        </form>

        <div class="error-message"><?php echo $error_message; ?></div>
        <div class="success-message"><?php echo $success_message; ?></div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeResetModal">&times;</span>
            <div class="modal-header">
                <h2>Reset Password</h2>
            </div>
            <form id="resetForm" method="POST" action="">
                <div class="form-group">
                    <label for="reset_username">Username</label>
                    <input type="text" id="reset_username" name="reset_username" required>
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
                <div class="error-message"><?php echo $error_message; ?></div>
                <div class="success-message"><?php echo $success_message; ?></div>
            </form>
        </div>
    </div>

    <!-- Register Modal -->
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeRegisterModal">&times;</span>
            <div class="modal-header">
                <h2>Register</h2>
            </div>
            <form id="registerForm" method="POST" action="">
                <div class="form-group">
                    <label for="new_username">Username</label>
                    <input type="text" id="new_username" name="new_username" required>
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
            document.getElementById('resetModal').classList.add('show');
        };

        document.getElementById('showRegisterForm').onclick = function() {
            document.getElementById('registerModal').classList.add('show');
        };

        document.getElementById('closeResetModal').onclick = function() {
            document.getElementById('resetModal').classList.remove('show');
        };

        document.getElementById('closeRegisterModal').onclick = function() {
            document.getElementById('registerModal').classList.remove('show');
        };

        // Close modals if clicked outside of the modal content
        window.onclick = function(event) {
            if (event.target == document.getElementById('resetModal')) {
                document.getElementById('resetModal').classList.remove('show');
            } else if (event.target == document.getElementById('registerModal')) {
                document.getElementById('registerModal').classList.remove('show');
            }
        };
    </script>
</body>
</html>
