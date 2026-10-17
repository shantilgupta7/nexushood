<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
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
        'lift' => $_POST['lift'] ?? 0,
        'security' => $_POST['security'] ?? 0,
        'cleaning' => $_POST['cleaning'] ?? 0,
        'gardening' => $_POST['gardening'] ?? 0,
        'pool' => $_POST['pool'] ?? 0,
        'gym' => $_POST['gym'] ?? 0,
        'clubhouse' => $_POST['clubhouse'] ?? 0,
        'parking' => $_POST['parking'] ?? 0,
        'waste' => $_POST['waste'] ?? 0,
        'repairs' => $_POST['repairs'] ?? 0,
        'playground' => $_POST['playground'] ?? 0,
        'sinking' => $_POST['sinking'] ?? 0,
        'admin' => $_POST['admin'] ?? 0
    ];
    
    $total = array_sum($facilities);
    $units = $_POST['units'] ?? 0;
    $per_flat_cost = $units > 0 ? $total / $units : 0;

    try {
        $updatedCount = 0;
        foreach ($facilities as $facility => $amount) {
            $checkSql = "SELECT COUNT(*) FROM building_a_flat_maintenance WHERE facility = :facility";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->execute([':facility' => $facility]);
            $exists = $checkStmt->fetchColumn();

            if ($exists) {
                $sql = "UPDATE building_a_flat_maintenance 
                        SET amount = :amount, 
                            total_maintenance = :total, 
                            per_flat_cost = :per_flat_cost,
                            num_flats = :num_flats 
                        WHERE facility = :facility";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':facility' => $facility,
                    ':amount' => $amount,
                    ':total' => $total,
                    ':per_flat_cost' => $per_flat_cost,
                    ':num_flats' => $units
                ]);
                $updatedCount++;
            } else if ($amount > 0) {
                $sql = "INSERT INTO building_a_flat_maintenance (facility, amount, total_maintenance, per_flat_cost, num_flats) 
                        VALUES (:facility, :amount, :total, :per_flat_cost, :num_flats)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([
                    ':facility' => $facility,
                    ':amount' => $amount,
                    ':total' => $total,
                    ':per_flat_cost' => $per_flat_cost,
                    ':num_flats' => $units
                ]);
                $updatedCount++;
            }
        }
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
    <title>Building A Maintenance Calculator</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #a1c4fd, #c2e9fb);
            margin: 0;
            padding: 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 900px;
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
            color: #1a73e8;
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
            background: #f8fafc;
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
            color: #1a73e8;
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
            border-color: #1a73e8;
            outline: none;
        }
        .button-container {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            margin-top: 25px;
        }
        button {
            flex: 1;
            padding: 15px;
            background: linear-gradient(to right, #1a73e8, #4285f4);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease, background 0.3s ease;
        }
        button:hover {
            transform: scale(1.02);
            background: linear-gradient(to right, #1557b0, #3267d6);
        }
        #result {
            margin-top: 25px;
            text-align: center;
            font-size: 20px;
            color: #e91e63;
            font-weight: bold;
            background: #fff3f6;
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
            .button-container {
                flex-direction: column;
                gap: 15px;
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
        <h1>Building A Maintenance Calculator</h1>
        <span class="close-btn" onclick="window.location.href='maintenance.php'">×</span>
        
        <form method="POST" id="maintenanceForm">
            <div class="input-box">
                <label for="units"><i class="fas fa-home"></i> Number of Flats:</label>
                <input type="number" id="units" name="units" min="1" placeholder="e.g., 50" required>
            </div>

            <div class="row">
                <div class="column">
                    <div class="input-box">
                        <label for="electricity"><i class="fas fa-bolt"></i> Electricity (Common Areas):</label>
                        <input type="number" id="electricity" name="electricity" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="water"><i class="fas fa-tint"></i> Water Supply:</label>
                        <input type="number" id="water" name="water" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="lift"><i class="fas fa-elevator"></i> Lift Maintenance:</label>
                        <input type="number" id="lift" name="lift" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="security"><i class="fas fa-shield-alt"></i> Security Services:</label>
                        <input type="number" id="security" name="security" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="cleaning"><i class="fas fa-broom"></i> Cleaning & Housekeeping:</label>
                        <input type="number" id="cleaning" name="cleaning" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="gardening"><i class="fas fa-leaf"></i> Gardening:</label>
                        <input type="number" id="gardening" name="gardening" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="pool"><i class="fas fa-swimming-pool"></i> Swimming Pool:</label>
                        <input type="number" id="pool" name="pool" min="0" placeholder="₹ per month">
                    </div>
                </div>
                <div class="column">
                    <div class="input-box">
                        <label for="gym"><i class="fas fa-dumbbell"></i> Gym Maintenance:</label>
                        <input type="number" id="gym" name="gym" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="clubhouse"><i class="fas fa-users"></i> Clubhouse:</label>
                        <input type="number" id="clubhouse" name="clubhouse" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="parking"><i class="fas fa-parking"></i> Parking Maintenance:</label>
                        <input type="number" id="parking" name="parking" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="waste"><i class="fas fa-trash-alt"></i> Waste Management:</label>
                        <input type="number" id="waste" name="waste" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="repairs"><i class="fas fa-tools"></i> Repairs (Common Areas):</label>
                        <input type="number" id="repairs" name="repairs" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="playground"><i class="fas fa-futbol"></i> Playground:</label>
                        <input type="number" id="playground" name="playground" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="sinking"><i class="fas fa-piggy-bank"></i> Sinking Fund:</label>
                        <input type="number" id="sinking" name="sinking" min="0" placeholder="₹ per month">
                    </div>
                    <div class="input-box">
                        <label for="admin"><i class="fas fa-file-invoice"></i> Administrative Costs:</label>
                        <input type="number" id="admin" name="admin" min="0" placeholder="₹ per month">
                    </div>
                </div>
            </div>

            <div class="button-container">
                <button type="button" onclick="calculate()">Calculate Now</button>
                <button type="submit" name="update">Update</button>
            </div>
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
            const units = parseInt(document.getElementById('units').value) || 0;
            const electricity = parseFloat(document.getElementById('electricity').value) || 0;
            const water = parseFloat(document.getElementById('water').value) || 0;
            const lift = parseFloat(document.getElementById('lift').value) || 0;
            const security = parseFloat(document.getElementById('security').value) || 0;
            const cleaning = parseFloat(document.getElementById('cleaning').value) || 0;
            const gardening = parseFloat(document.getElementById('gardening').value) || 0;
            const pool = parseFloat(document.getElementById('pool').value) || 0;
            const gym = parseFloat(document.getElementById('gym').value) || 0;
            const clubhouse = parseFloat(document.getElementById('clubhouse').value) || 0;
            const parking = parseFloat(document.getElementById('parking').value) || 0;
            const waste = parseFloat(document.getElementById('waste').value) || 0;
            const repairs = parseFloat(document.getElementById('repairs').value) || 0;
            const playground = parseFloat(document.getElementById('playground').value) || 0;
            const sinking = parseFloat(document.getElementById('sinking').value) || 0;
            const admin = parseFloat(document.getElementById('admin').value) || 0;

            const totalCost = electricity + water + lift + security + cleaning + gardening + 
                            pool + gym + clubhouse + parking + waste + repairs + playground + 
                            sinking + admin;
            const costPerFlat = units > 0 ? (totalCost / units).toFixed(2) : 0;

            const resultDiv = document.getElementById('result');
            if (units <= 0) {
                resultDiv.innerHTML = "Please enter a valid number of flats!";
            } else {
                resultDiv.innerHTML = `Total Monthly Cost: ₹${totalCost.toLocaleString('en-IN')}<br>Cost Per Flat: ₹${costPerFlat}`;
            }
        }
    </script>
</body>
</html>