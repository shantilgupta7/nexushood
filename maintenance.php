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
    <title>NeXusHooD.com - Maintenance Dashboard</title>
    <link rel="stylesheet" href="css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/maintenance.css?v=<?php echo time(); ?>">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>
<div class="main"> 
    <div class="topber">
        <div class="toggle">
            <ion-icon name="menu-outline"></ion-icon>
        </div>
    </div>
    
    <div class="dashboard-container">
        <?php
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "mysociety";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Building A Maintenance
        $building_a_sql = "SELECT facility, amount, total_maintenance, per_flat_cost, num_flats 
                          FROM building_a_flat_maintenance";
        $building_a_result = $conn->query($building_a_sql);

        $a_total = 0;
        $a_per_flat_cost = 0;
        $a_num_flats = 0;
        $a_data = [];
        if ($building_a_result && $building_a_result->num_rows > 0) {
            while ($row = $building_a_result->fetch_assoc()) {
                $a_data[] = $row;
                $a_total += $row['amount'];
                $a_per_flat_cost = $row['per_flat_cost'];
                $a_num_flats = $row['num_flats'];
            }
        }

        // Buildings BCD Maintenance
        $building_bcd_sql = "SELECT facility, amount, total_maintenance, per_flat_cost, num_flats 
                            FROM building_bcd_flat_maintenance";
        $building_bcd_result = $conn->query($building_bcd_sql);

        $bcd_total = 0;
        $bcd_per_flat_cost = 0;
        $bcd_num_flats = 0;
        $bcd_data = [];
        if ($building_bcd_result && $building_bcd_result->num_rows > 0) {
            while ($row = $building_bcd_result->fetch_assoc()) {
                $bcd_data[] = $row;
                $bcd_total += $row['amount'];
                $bcd_per_flat_cost = $row['per_flat_cost'];
                $bcd_num_flats = $row['num_flats'];
            }
        }

        $conn->close();
        ?>

        <!-- Building A Section -->
        <div class="building-section">
            <div class="building-header">
                <h2>Building A Maintenance</h2>
                <button class="calculator-btn" onclick="window.location.href='flat_calculator_a.php'">
                    Calculate
                </button>
            </div>
            <p><strong>Number of Flats:</strong> <?php echo $a_num_flats > 0 ? $a_num_flats : 'Not specified'; ?></p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($a_data)) : ?>
                            <?php foreach ($a_data as $row) : ?>
                                <tr>
                                    <td><?php echo ucfirst($row['facility']); ?></td>
                                    <td><?php echo number_format($row['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="2">No maintenance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total Cost</td>
                            <td><?php echo number_format($a_total, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Per Flat Cost</td>
                            <td><?php echo number_format($a_per_flat_cost, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Buildings BCD Section -->
        <div class="building-section">
            <div class="building-header">
                <h2>Buildings B, C, D Maintenance</h2>
                <button class="calculator-btn" onclick="window.location.href='flat_calculator_bcd.php'">
                    Calculate
                </button>
            </div>
            <p><strong>Number of Flats:</strong> <?php echo $bcd_num_flats > 0 ? $bcd_num_flats : 'Not specified'; ?></p>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Facility</th>
                            <th>Amount (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($bcd_data)) : ?>
                            <?php foreach ($bcd_data as $row) : ?>
                                <tr>
                                    <td><?php echo ucfirst($row['facility']); ?></td>
                                    <td><?php echo number_format($row['amount'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="2">No maintenance records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td>Total Cost</td>
                            <td><?php echo number_format($bcd_total, 2); ?></td>
                        </tr>
                        <tr>
                            <td>Per Flat Cost</td>
                            <td><?php echo number_format($bcd_per_flat_cost, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="js/script.js"></script>
<script src="js/maintenance.js"></script>
<script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
<script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>