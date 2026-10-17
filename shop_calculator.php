<?php
// Database connection
$servername = "localhost";
$username = "root"; // Change to your database username
$password = "";     // Change to your database password
$dbname = "mysociety";

try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    die();
}

// Handle form submission for update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update'])) {
    $facilities = [
        'electricity' => $_POST['electricity'] ?? 0,
        'water' => $_POST['water'] ?? 0,
        'security' => $_POST['security'] ?? 0,
        'cleaning' => $_POST['cleaning'] ?? 0,
        'parking' => $_POST['parking'] ?? 0,
        'waste' => $_POST['waste'] ?? 0,
        'repairs' => $_POST['repairs'] ?? 0,
        'signage' => $_POST['signage'] ?? 0,
        'admin' => $_POST['admin'] ?? 0,
        'sinking' => $_POST['sinking'] ?? 0
    ];
    
    $total = array_sum($facilities);
    $shops = $_POST['shops'] ?? 0; // Number of shops
    $per_shop_cost = $shops > 0 ? $total / $shops : 0;

    try {
        $updatedCount = 0;
        foreach ($facilities as $facility => $amount) {
            // Check if record exists
            $checkSql = "SELECT COUNT(*) FROM shop_maintenance WHERE facility = :facility";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':facility' => $facility]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                // Update existing record
                $sql = "UPDATE shop_maintenance 
                        SET amount = :amount, 
                            total_maintenance = :total, 
                            per_shop_cost = :per_shop_cost,
                            num_shops = :num_shops 
                        WHERE facility = :facility";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':facility' => $facility,
                    ':amount' => $amount,
                    ':total' => $total,
                    ':per_shop_cost' => $per_shop_cost,
                    ':num_shops' => $shops // Save number of shops
                ]);
                $updatedCount++;
            } else if ($amount > 0) {
                // Insert new record if it doesn't exist and amount > 0
                $sql = "INSERT INTO shop_maintenance (facility, amount, total_maintenance, per_shop_cost, num_shops) 
                        VALUES (:facility, :amount, :total, :per_shop_cost, :num_shops)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':facility' => $facility,
                    ':amount' => $amount,
                    ':total' => $total,
                    ':per_shop_cost' => $per_shop_cost,
                    ':num_shops' => $shops // Save number of shops
                ]);
                $updatedCount++;
            }
        }
        // If update or insert is successful, redirect to maintenance.php
        if ($updatedCount > 0) {
            header("Location: maintenance.php");
            exit();
        }
    } catch(PDOException $e) {
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Maintenance Calculator (Society)</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6d365, #fda085);
            margin: 0;
            padding: 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
            animation: fadeIn 0.5s ease-in-out;
            position: relative;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        h1 {
            text-align: center;
            color: #e65100;
            font-size: 28px;
            margin-bottom: 25px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .row {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .column {
            width: 48%;
        }
        .input-box {
            margin: 15px 0;
            padding: 15px;
            background: #fff8e1;
            border-radius: 10px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .input-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        label {
            font-size: 14px;
            color: #333;
            display: flex;
            align-items: center;
            margin-bottom: 8px;
            font-weight: 500;
        }
        label i {
            margin-right: 8px;
            color: #e65100;
        }
        input {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
            transition: border-color 0.3s ease;
        }
        input:focus {
            border-color: #e65100;
            outline: none;
        }
        button {
            width: 100%;
            padding: 15px;
            background: linear-gradient(to right, #e65100, #ff8f00);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin-top: 25px;
            transition: transform 0.2s ease, background 0.3s ease;
        }
        button:hover {
            transform: scale(1.02);
            background: linear-gradient(to right, #bf360c, #e65100);
        }
        #result {
            margin-top: 25px;
            text-align: center;
            font-size: 20px;
            color: #2e7d32;
            font-weight: bold;
            background: #e8f5e9;
            padding: 15px;
            border-radius: 10px;
            animation: slideIn 0.5s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .message {
            margin-top: 20px;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
        .error {
            background: #f2dede;
            color: #a94442;
        }
        @media (max-width: 700px) {
            .column {
                width: 100%;
            }
        }
        .close-btn {
            position: absolute;
            top: 15px;
            right: 15px;
            font-size: 24px;
            color: #666;
            cursor: pointer;
            transition: color 0.3s ease, transform 0.3s ease;
        }
        .close-btn:hover {
            color: #e91e63;
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Shop Maintenance Calculator</h1>
        <span class="close-btn" onclick="window.location.href='maintenance.php'">×</span>
        
        <form method="POST" id="maintenanceForm">
            <div class="input-box">
                <label for="shops"><i class="fas fa-store"></i> Number of Shops:</label>
                <input type="number" id="shops" name="shops" min="1" placeholder="e.g., 10" required>
            </div>

            <div class="row">
                <div class="column">
                    <div class="input-box">
                        <label for="electricity"><i class="fas fa-bolt"></i> Common Electricity:</label>
                        <input type="number" id="electricity" name="electricity" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="water"><i class="fas fa-tint"></i> Water Supply:</label>
                        <input type="number" id="water" name="water" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="security"><i class="fas fa-shield-alt"></i> Security:</label>
                        <input type="number" id="security" name="security" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="cleaning"><i class="fas fa-broom"></i> Cleaning:</label>
                        <input type="number" id="cleaning" name="cleaning" min="0" placeholder="₹ per month">
                    </div>
                </div>
                <div class="column">
                    <div class="input-box">
                        <label for="parking"><i class="fas fa-parking"></i> Parking:</label>
                        <input type="number" id="parking" name="parking" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="waste"><i class="fas fa-trash-alt"></i> Waste Disposal:</label>
                        <input type="number" id="waste" name="waste" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="repairs"><i class="fas fa-tools"></i> Common Repairs:</label>
                        <input type="number" id="repairs" name="repairs" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="signage"><i class="fas fa-sign"></i> Signage & Lights:</label>
                        <input type="number" id="signage" name="signage" min="0" placeholder="₹ per month">
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="column">
                    <div class="input-box">
                        <label for="admin"><i class="fas fa-file-invoice"></i> Admin Costs:</label>
                        <input type="number" id="admin" name="admin" min="0" placeholder="₹ per month">
                    </div>
                </div>
                <div class="column">
                    <div class="input-box">
                        <label for="sinking"><i class="fas fa-piggy-bank"></i> Sinking Fund:</label>
                        <input type="number" id="sinking" name="sinking" min="0" placeholder="₹ per month">
                    </div>
                </div>
            </div>

            <button type="button" onclick="calculate()">Calculate Now</button>
            <button type="submit" name="update">Update</button>
            <div id="result"></div>
            
            <?php if (isset($message)): ?>
                <div class="message error">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        function calculate() {
            const shops = parseInt(document.getElementById('shops').value) || 0;
            const electricity = parseFloat(document.getElementById('electricity').value) || 0;
            const water = parseFloat(document.getElementById('water').value) || 0;
            const security = parseFloat(document.getElementById('security').value) || 0;
            const cleaning = parseFloat(document.getElementById('cleaning').value) || 0;
            const parking = parseFloat(document.getElementById('parking').value) || 0;
            const waste = parseFloat(document.getElementById('waste').value) || 0;
            const repairs = parseFloat(document.getElementById('repairs').value) || 0;
            const signage = parseFloat(document.getElementById('signage').value) || 0;
            const admin = parseFloat(document.getElementById('admin').value) || 0;
            const sinking = parseFloat(document.getElementById('sinking').value) || 0;

            const totalCost = electricity + water + security + cleaning + parking + 
                             waste + repairs + signage + admin + sinking;
            const costPerShop = shops > 0 ? (totalCost / shops).toFixed(2) : 0;

            const resultDiv = document.getElementById('result');
            if (shops <= 0) {
                resultDiv.innerHTML = "Please enter a valid number of shops!";
            } else {
                resultDiv.innerHTML = `Total Monthly Cost: ₹${totalCost.toLocaleString('en-IN')}<br>Cost Per Shop: ₹${costPerShop}`;
            }
        }
    </script>
</body>
</html>