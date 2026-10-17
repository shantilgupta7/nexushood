<?php
session_start();
if (!isset($_SESSION['room_no'])) {
    header("Location: login.php");  // Redirect to login if session is not set
    exit();
}

// Fetch session data
$payment_details = isset($_SESSION['payment_details']) ? $_SESSION['payment_details'] : [];
$complaint_details = isset($_SESSION['complaint_details']) ? $_SESSION['complaint_details'] : [];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Table</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: linear-gradient(to right, #f7f7f7, #e2e2e2);
            margin: 0;
            padding: 0;
        }

        .container4 {
            width: 90%;
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #333;
            font-size: 2em;
        }

        .pay-btn {
            display: block;
            
            padding: 15px 25px;
            font-size: 18px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
        }

        .pay-btn:hover {
            background: #45a049;
            transform: scale(1.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 15px;
            text-align: center;
            color: #555;
        }

        table th {
            background: #f4f4f4;
            color: #333;
            font-weight: bold;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        table tr:hover {
            background: #f1f1f1;
        }

        .popup {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            justify-content: center;
            align-items: center;
            z-index: 1000;
            transition: opacity 0.3s;
        }

        .popup-content {
            background: #fff;
            padding: 30px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close-btn:hover {
            color: #d9534f;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        form label {
            margin: 10px 0 5px;
            font-weight: bold;
            color: #333;
        }

        form input {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
        }

        form button {
            padding: 15px;
            background: #4CAF50;
            color: #fff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s, transform 0.2s;
        }

        form button:hover {
            background: #45a049;
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .container {
                width: 100%;
                padding: 15px;
            }

            .pay-btn, form button {
                font-size: 16px;
                padding: 12px;
            }

            table th, table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container4">
        <button id="payBtn" class="pay-btn">Pay Now</button>
        <h2>Payment Records</h2>
        <?php if (!empty($payment_details)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Room No</th>
                        <th>Category</th>
                        <th>Amount</th>
                        <th>Date</th>
                        <th>Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_details as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['name']); ?></td>
                            <td><?php echo htmlspecialchars($payment['room_no']); ?></td>
                            <td><?php echo htmlspecialchars($payment['category']); ?></td>
                            <td><?php echo htmlspecialchars($payment['amount']); ?></td>
                            <?php
                            $date = new DateTime($payment['date']);
                            $formattedDate = $date->format('d-m-Y'); // Change to your preferred date format
                            $formattedTime = $date->format('g:i A'); // Change to your preferred time format
                            ?>
                            <td><?php echo htmlspecialchars($formattedDate); ?></td>
                            <td><?php echo htmlspecialchars($formattedTime); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No payment details available.</p>
        <?php endif; ?>

    <!-- Popup Form -->
    <div id="payPopup" class="popup">
        <div class="popup-content">
            <span class="close-btn">&times;</span>
            <h3>Enter Payment Details</h3>
            <form id="paymentForm">
                <label for="amount">Amount:</label>
                <input type="text" id="amount" name="amount" required>
                <label for="date">Date:</label>
                <input type="date" id="date" name="date" required>
                <label for="time">Time:</label>
                <input type="time" id="time" name="time" required>
                <button type="submit">Proceed to Pay</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const payBtn = document.getElementById('payBtn');
            const payPopup = document.getElementById('payPopup');
            const closeBtn = document.querySelector('.close-btn');

            // Show popup when 'Pay' button is clicked
            payBtn.addEventListener('click', function () {
                payPopup.style.display = 'flex';
                setTimeout(() => payPopup.style.opacity = '1', 10); // Fade in effect
            });

            // Close popup when 'X' button is clicked
            closeBtn.addEventListener('click', function () {
                payPopup.style.opacity = '0'; // Fade out effect
                setTimeout(() => payPopup.style.display = 'none', 300); // Hide after fade out
            });

            // Close popup when clicking outside of the popup
            window.addEventListener('click', function (event) {
                if (event.target === payPopup) {
                    payPopup.style.opacity = '0'; // Fade out effect
                    setTimeout(() => payPopup.style.display = 'none', 300); // Hide after fade out
                }
            });
        });
    </script>
</body>
</html>
