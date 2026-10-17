<?php
session_start();
// Redirect to login if the user is not logged in
if (!isset($_SESSION['room_no'])) {
    header("Location: sign-in.php");
    exit();
}

// Include the database connection
include 'db_connect.php';

// Set timezone to ensure consistency
date_default_timezone_set('Asia/Kolkata');

// Check if user is blocked (latest complaint for this room_no)
$room_no = $_SESSION['room_no'];
$check_block_sql = "SELECT blocked FROM complaint WHERE room_no = ? ORDER BY date DESC, time DESC LIMIT 1";
$stmt = $conn->prepare($check_block_sql);
$stmt->bind_param("s", $room_no);
$stmt->execute();
$result = $stmt->get_result();
$is_blocked = false;
if ($row = $result->fetch_assoc()) {
    $is_blocked = $row['blocked'];
}
$stmt->close();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_blocked) {
    $room_no = $_POST['room_no'];
    $name = $_POST['name'];
    $mobile = $_POST['mobile']; // Changed from contact to mobile
    $description = !empty($_POST['description']) ? $_POST['description'] : NULL;
    $resolve_note = !empty($_POST['resolve_note']) ? $_POST['resolve_note'] : NULL; // New field
    $date = date('Y-m-d');
    $time = date('H:i:s');
    $image_path = NULL;
    $video_path = NULL;

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == UPLOAD_ERR_OK) {
        $image = $_FILES['image'];
        $allowed_image_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($image['type'], $allowed_image_types) && $image['size'] <= 10 * 1024 * 1024) { // 10MB max
            $image_path = 'uploads/images/' . uniqid() . '_' . basename($image['name']);
            move_uploaded_file($image['tmp_name'], $image_path);
        }
    }

    // Handle video upload (1-minute max, 50MB limit)
    if (isset($_FILES['video']) && $_FILES['video']['error'] == UPLOAD_ERR_OK) {
        $video = $_FILES['video'];
        $allowed_video_types = ['video/mp4', 'video/webm', 'video/quicktime', 'video/x-msvideo'];
        $file_type = $video['type'];
        $file_size = $video['size'];

        if ($file_size > 50 * 1024 * 1024) { // 50MB max
            $size_in_mb = round($file_size / (1024 * 1024), 2);
            echo "Please upload up to 50 MB size video. Your file is $size_in_mb MB.";
            exit();
        }

        if (in_array($file_type, $allowed_video_types)) {
            $ext = pathinfo($video['name'], PATHINFO_EXTENSION);
            $video_path = 'uploads/videos/' . uniqid() . '.' . $ext;
            if (move_uploaded_file($video['tmp_name'], $video_path)) {
                // Success, no echo here
            } else {
                echo "Error moving video file.";
                error_log("Failed to move video file to: $video_path");
                exit();
            }
        } else {
            echo "Invalid video type. Allowed types: MP4, MOV, AVI, WebM.";
            error_log("Video rejected: Type: $file_type, Size: $file_size bytes");
            exit();
        }
    }

    if ($description || $image_path || $video_path) {
        $stmt = $conn->prepare("INSERT INTO complaint (room_no, name, mobile, description, date, time, image_path, video_path, resolve_note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssssss", $room_no, $name, $mobile, $description, $date, $time, $image_path, $video_path, $resolve_note);

        if ($stmt->execute()) {
            echo "<div class='success-message'><i class='fas fa-check-circle'></i> Complaint submitted successfully!</div>";
        } else {
            echo "Error: " . $stmt->error;
            error_log("Database insert error: " . $stmt->error);
        }
        $stmt->close();
    } else {
        echo "Please provide at least a description, image, or video.";
    }

    $conn->close();
    exit();
} elseif ($is_blocked && $_SERVER['REQUEST_METHOD'] == 'POST') {
    echo "You are blocked from submitting complaints.";
    exit();
}

// Handle delete action
if (isset($_GET['delete_complaint'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM complaint WHERE id = ? AND room_no = ? AND resolved = 0");
    $stmt->bind_param("is", $id, $room_no);
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo "<div class='delete-success-message'><i class='fas fa-trash-alt'></i> Complaint deleted successfully!</div>";
        } else {
            echo "Error: Complaint not found or already resolved.";
        }
    } else {
        echo "Error deleting complaint: " . $stmt->error;
    }
    $stmt->close();
    header("Location: c_details.php");
    exit();
}

// Fetch logged-in user's complaints
$query = "SELECT * FROM complaint WHERE room_no = ? ORDER BY date DESC, time DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $room_no);
$stmt->execute();
$result = $stmt->get_result();
$complaint_details = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NeXusHooD.com</title>

    <!-- CSS FILES -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;300;400;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="css/slick.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo time(); ?>">

    <style>
        .container2 {
            display: flex;
            flex-direction: row;
            gap: 10px;
        }

        .box {
            background-color: #fff;
            padding: 2px;
            margin-top: 50px;
            margin-left: 30px;
            width: 400px;
            height: 300px;
            text-align: center;
            line-height: 50px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .box:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
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
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }

        #complaint {
            display: block;
            margin: 20px auto;
            padding: 12px 30px;
            font-size: 18px;
            background: linear-gradient(135deg, #4CAF50, #81C784);
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2);
            font-family: 'Inter', sans-serif;
        }

        #complaint:hover {
            background: linear-gradient(135deg, #81C784, #66BB6A);
            transform: translateY(-5px);
            box-shadow: 0 10px 18px rgba(0, 0, 0, 0.3);
        }

        #complaint:focus {
            outline: none;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.8);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            color: #555;
            font-family: 'Inter', sans-serif;
        }

        table th {
            background: #4169E1; /* Restored to original solid color */
            color: #fff;
            font-weight: 600;
            text-transform: uppercase;
        }

        table tr:nth-child(even) {
            background: #f9f9f9;
        }

        table tr:hover {
            background: #eef2ff;
            transition: background 0.3s ease;
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
            background: linear-gradient(135deg, #f9f9f9, #ffffff);
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            position: relative;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            max-height: 80vh;
            overflow-y: auto;
            animation: popupShow 0.4s ease-out;
            font-family: 'Inter', sans-serif;
        }

        @keyframes popupShow {
            from {
                opacity: 0;
                transform: scale(0.7);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .close {
            position: absolute;
            top: 15px;
            right: 20px;
            font-size: 24px;
            color: #333;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 700;
        }

        .close:hover {
            color: #f44336;
            transform: rotate(90deg) scale(1.1);
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        form label {
            margin: 10px 0 5px;
            font-weight: 600;
            color: #333;
            font-size: 16px;
        }

        form select {
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
            transition: border-color 0.3s, box-shadow 0.3s;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%23333' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
        }

        form select:focus {
            border-color: #4CAF50;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.3);
            outline: none;
        }

        .file-upload {
            margin-bottom: 15px;
        }

        .file-upload label {
            display: block;
            margin-bottom: 5px;
        }

        .file-upload input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            background: #fff;
        }

        .file-upload input[type="file"]::-webkit-file-upload-button {
            background: linear-gradient(135deg, #4CAF50, #81C784);
            color: #fff;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .file-upload input[type="file"]::-webkit-file-upload-button:hover {
            background: linear-gradient(135deg, #81C784, #66BB6A);
        }

        form button {
            padding: 12px;
            background: linear-gradient(135deg, #4CAF50, #81C784);
            color: #fff;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.4s ease;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        form button:hover {
            background: linear-gradient(135deg, #81C784, #66BB6A);
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.3);
        }

        form button:focus {
            outline: none;
            box-shadow: 0 0 8px rgba(76, 175, 80, 0.8);
        }

        /* Success Message Style */
        .success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #4CAF50, #66BB6A);
            color: #fff;
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            font-size: 20px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            animation: bounceInOut 3s ease forwards;
            z-index: 2000;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .success-message i {
            font-size: 24px;
        }

        @keyframes bounceInOut {
            0% { opacity: 0; transform: translate(-50%, -50%) scale(0.8); }
            20% { transform: translate(-50%, -50%) scale(1.1); }
            40% { transform: translate(-50%, -50%) scale(0.95); }
            60% { transform: translate(-50%, -50%) scale(1.05); }
            80% { transform: translate(-50%, -50%) scale(0.98); }
            100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
        }

        /* Delete Confirmation Popup */
        .delete-confirmation {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            z-index: 2000;
            text-align: center;
            font-family: 'Inter', sans-serif;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translate(-50%, -60%); }
            to { opacity: 1; transform: translate(-50%, -50%); }
        }

        .delete-confirmation h3 {
            margin: 0 0 15px;
            color: #dc3545;
            font-size: 22px;
            font-weight: 700;
        }

        .delete-confirmation p {
            margin: 0 0 20px;
            color: #555;
            font-size: 16px;
        }

        .delete-confirmation .btn {
            padding: 10px 25px;
            margin: 0 10px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .delete-confirmation .btn-yes {
            background: #dc3545;
            color: #fff;
        }

        .delete-confirmation .btn-yes:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .delete-confirmation .btn-no {
            background: #6c757d;
            color: #fff;
        }

        .delete-confirmation .btn-no:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* Delete Success Message */
        .delete-success-message {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
            padding: 20px 40px;
            border-radius: 15px;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.3);
            font-size: 20px;
            font-weight: 600;
            font-family: 'Inter', sans-serif;
            animation: bounceInOut 3s ease forwards;
            z-index: 2000;
            text-align: center;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delete-success-message i {
            font-size: 24px;
        }

        @media (max-width: 768px) {
            .container4 {
                width: 100%;
                padding: 15px;
            }
            #complaint {
                font-size: 16px;
                padding: 10px 25px;
            }
            table th, table td {
                padding: 10px;
                font-size: 14px;
            }
            form select,
            .file-upload input[type="file"] {
                font-size: 14px;
            }
            form button {
                font-size: 14px;
                padding: 10px;
            }
            .popup-content {
                width: 95%;
            }
            .success-message, .delete-success-message {
                font-size: 16px;
                padding: 12px 20px;
            }
            .delete-confirmation {
                width: 90%;
                padding: 15px;
            }
            .delete-confirmation h3 {
                font-size: 18px;
            }
            .delete-confirmation p {
                font-size: 14px;
            }
            .delete-confirmation .btn {
                padding: 8px 20px;
                font-size: 14px;
            }
        }

        /* Style for resolved complaints */
        tr.resolved {
            background-color: #d3d3d3 !important;
            pointer-events: none;
        }

        tr.resolved a {
            color: #888;
            pointer-events: none;
        }

        /* Style for delete button */
        .delete-button {
            background-color: #dc3545;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 50px;
            display: inline-block;
            transition: background 0.3s;
        }

        .delete-button:hover {
            background-color: #c82333;
            transform: translateY(-2px);
        }

        .user-info p {
            font-weight: bold;
            margin: 0;
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
        <nav class="navbar navbar-expand-lg">
            <div class="container">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <a class="navbar-brand" href="home2.php">
                    <strong><span><img src="images/logo2.png" alt="logo" class="logo"></span> NeXusHooD</strong>
                </a>

                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="home2.php"><i class="fas fa-home"></i>Home</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="m_details.php"><i class="fas fa-wallet"></i>Maintenance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="c_details.php"><i class="fas fa-comments"></i>Complaint</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="about2.php"><i class="fas fa-info-circle"></i>About</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out"></i>Sign-out</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <section class="slick-slideshow">   
            <div class="slick-custom">
                <img src="images/slideshow/s2.jpg" class="img-fluid" alt="Slideshow Image 1">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slick-custom">
                <img src="images/slideshow/s12.jpg" class="img-fluid" alt="Slideshow Image 2">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="slick-custom">
                <img src="images/slideshow/s4.jpg" class="img-fluid" alt="Slideshow Image 3">
                <div class="slick-bottom">
                    <div class="container">
                        <div class="row">
                            <div class="col-lg-6 col-10">
                                <h1 class="slick-title" style="color: #4169E1;">Welcome,</h1>
                                <div class="user-info">
                                    <p class="lead text-white">Name: <?php echo htmlspecialchars($_SESSION['fh_name']); ?></p>
                                    <p class="lead text-white">Room No: <?php echo htmlspecialchars($_SESSION['room_no']); ?></p>
                                    <p class="lead text-white">Contact: <?php echo htmlspecialchars($_SESSION['contact'] ?? 'Not provided'); ?></p>
                                    <p class="lead text-white">Email: <?php echo htmlspecialchars($_SESSION['email'] ?? 'Not provided'); ?></p>
                                    <?php $joiningDate = new DateTime($_SESSION['joining_date']); ?>
                                    <p class="lead text-white">Joining Date: <?php echo $joiningDate->format('d-m-Y'); ?></p>
                                </div>
                                <p class="lead text-white mt-lg-3 mb-lg-5">You are a most <b>valuable</b> Member for us.</p>
                                <a href="logout.php" class="btn custom-btn" style="background-color: #00246b;" title="Sign out of your account">Sign-out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="container4">
            <button id="complaint" title="Open complaint form">Write A Complaint</button>
            <h2>Your Complaints</h2>
            <?php if (!empty($complaint_details)): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Room No</th>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Image</th>
                            <th>Video</th>
                            <th>Reply</th>
                            <th>Resolve Note</th> <!-- New column header -->
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($complaint_details as $complaint): ?>
                            <tr class="<?php echo $complaint['resolved'] ? 'resolved' : ''; ?>">
                                <td><?php echo htmlspecialchars($complaint['room_no']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['name']); ?></td>
                                <td><?php echo htmlspecialchars($complaint['description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars((new DateTime($complaint['date']))->format('d-m-Y')); ?></td>
                                <td><?php echo htmlspecialchars(date('h:i A', strtotime($complaint['time']))); ?></td>
                                <td>
                                    <?php if (!empty($complaint['image_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($complaint['image_path']); ?>" target="_blank" title="View complaint image">View Image</a>
                                    <?php else: ?>
                                        No Image
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($complaint['video_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($complaint['video_path']); ?>" target="_blank" title="View complaint video">View Video</a>
                                    <?php else: ?>
                                        No Video
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($complaint['reply'] ?? 'No reply yet'); ?></td>
                                <td><?php echo htmlspecialchars($complaint['resolve_note'] ?? 'Not resolved yet'); ?></td> <!-- New column data -->
                                <td>
                                    <?php if (!$complaint['resolved']): ?>
                                        <a href="#" class="delete-button" onclick="showDeleteConfirmation(<?php echo $complaint['id']; ?>)" title="Delete this complaint">Delete</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align: center; color: #666;">No complaints available.</p>
            <?php endif; ?>
        </div>

        <!-- Popup Form -->
        <div id="complaintPopup" class="popup">
            <div class="popup-content">
                <span class="close" onclick="closePopup()" title="Close complaint form">×</span>
                <h2>Submit Your Complaint</h2>
                <form id="complaintForm" method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="room_no" value="<?php echo htmlspecialchars($_SESSION['room_no']); ?>" title="Your room number (hidden)">
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($_SESSION['fh_name']); ?>" title="Your name (hidden)">
                    <input type="hidden" name="mobile" value="<?php echo htmlspecialchars($_SESSION['contact'] ?? ''); ?>" title="Your mobile number (hidden)">
                    <input type="hidden" name="date" value="<?php echo date('d-m-Y'); ?>" title="Current date (hidden)">
                    <input type="hidden" name="time" value="<?php echo date('h:i A'); ?>" title="Current time (hidden)">
                    <input type="hidden" name="resolve_note" value="" title="Resolution note (hidden, initially empty)"> <!-- New hidden field -->

                    <label for="description" title="Select a complaint description">Description:</label>
                    <select id="description" name="description" required title="Select a complaint description">
                        <option value="" disabled selected>Select a complaint description</option>
                        <!-- Lift Issues -->
                        <optgroup label="Lift">
                            <option value="Lift is not working">Lift is not working</option>
                            <option value="Lift is not working properly">Lift is not working properly</option>
                            <option value="Lift fan is not working">Lift fan is not working</option>
                            <option value="Lift light has gone out">Lift light has gone out</option>
                            <option value="Lift door is stuck">Lift door is stuck</option>
                            <option value="Lift makes unusual noise">Lift makes unusual noise</option>
                            <option value="Lift buttons are not responding">Lift buttons are not responding</option>
                        </optgroup>
                        <!-- Water Issues -->
                        <optgroup label="Water">
                            <option value="Water motor broke down">Water motor broke down</option>
                            <option value="Water is coming bad">Water is coming bad</option>
                            <option value="Tap is broken">Tap is broken</option>
                            <option value="No water supply">No water supply</option>
                            <option value="Water tank is leaking">Water tank is leaking</option>
                            <option value="Water pressure is low">Water pressure is low</option>
                            <option value="Water is dirty or contaminated">Water is dirty or contaminated</option>
                        </optgroup>
                        <!-- Garden Issues -->
                        <optgroup label="Garden">
                            <option value="Garden is not clean">Garden is not clean</option>
                            <option value="Garden swing is broken">Garden swing is broken</option>
                            <option value="Trees in the garden are drying up">Trees in the garden are drying up</option>
                            <option value="Garden lights are not working">Garden lights are not working</option>
                            <option value="Grass needs mowing">Grass needs mowing</option>
                            <option value="Garden benches are damaged">Garden benches are damaged</option>
                            <option value="Sprinkler system is broken">Sprinkler system is broken</option>
                        </optgroup>
                        <!-- Electrical Issues -->
                        <optgroup label="Electrical">
                            <option value="Power outage in the flat">Power outage in the flat</option>
                            <option value="Common area lights not working">Common area lights not working</option>
                            <option value="Fuse box is sparking">Fuse box is sparking</option>
                            <option value="Electric meter is faulty">Electric meter is faulty</option>
                            <option value="Street lights are out">Street lights are out</option>
                            <option value="Wiring issues in the flat">Wiring issues in the flat</option>
                        </optgroup>
                        <!-- Plumbing Issues -->
                        <optgroup label="Plumbing">
                            <option value="Pipe leakage in the flat">Pipe leakage in the flat</option>
                            <option value="Drainage is clogged">Drainage is clogged</option>
                            <option value="Toilet flush not working">Toilet flush not working</option>
                            <option value="Shower is leaking">Shower is leaking</option>
                            <option value="Sink is blocked">Sink is blocked</option>
                        </optgroup>
                        <!-- Security Issues -->
                        <optgroup label="Security">
                            <option value="CCTV not working">CCTV not working</option>
                            <option value="Main gate lock is broken">Main gate lock is broken</option>
                            <option value="Security guard not present">Security guard not present</option>
                            <option value="Intercom system is down">Intercom system is down</option>
                            <option value="Parking area is unsafe">Parking area is unsafe</option>
                        </optgroup>
                        <!-- Cleanliness Issues -->
                        <optgroup label="Cleanliness">
                            <option value="Garbage not collected">Garbage not collected</option>
                            <option value="Corridors are dirty">Corridors are dirty</option>
                            <option value="Staircase needs cleaning">Staircase needs cleaning</option>
                            <option value="Dustbins are overflowing">Dustbins are overflowing</option>
                        </optgroup>
                        <!-- Structural Issues -->
                        <optgroup label="Structural">
                            <option value="Wall has cracks">Wall has cracks</option>
                            <option value="Ceiling is leaking">Ceiling is leaking</option>
                            <option value="Window is broken">Window is broken</option>
                            <option value="Door is jammed">Door is jammed</option>
                            <option value="Balcony railing is loose">Balcony railing is loose</option>
                        </optgroup>
                        <!-- Miscellaneous -->
                        <optgroup label="Miscellaneous">
                            <option value="Noise disturbance from neighbors">Noise disturbance from neighbors</option>
                            <option value="Stray animals in society premises">Stray animals in society premises</option>
                            <option value="Parking space issue">Parking space issue</option>
                            <option value="Internet connection not working">Internet connection not working</option>
                            <option value="Pest infestation in flat">Pest infestation in flat</option>
                        </optgroup>
                    </select>

                    <div class="file-upload">
                        <label for="image" title="Upload an image (JPEG, PNG, GIF, max 10MB)">Upload Image:</label>
                        <input type="file" id="image" name="image" accept="image/jpeg,image/png,image/gif" title="Select an image file">
                    </div>

                    <div class="file-upload">
                        <label for="video" title="Upload a video (MP4, MOV, AVI, WebM, max 1 min, 50MB)">Upload Video:</label>
                        <input type="file" id="video" name="video" accept="video/mp4,video/quicktime,video/x-msvideo,video/webm" onchange="checkVideoSizeAndDuration(this)" title="Select a video file">
                    </div>

                    <button type="submit" title="Submit your complaint">Submit Complaint</button>
                </form>
            </div>
        </div>

        <!-- Delete Confirmation Popup -->
        <div id="deleteConfirmation" class="delete-confirmation">
            <h3>Confirm Deletion</h3>
            <p>Are you sure you want to delete this complaint?</p>
            <button class="btn btn-yes" onclick="confirmDelete()">Yes</button>
            <button class="btn btn-no" onclick="cancelDelete()">No</button>
        </div>

        <script>
            // Display popup for writing complaints
            document.getElementById('complaint').onclick = function() {
                <?php if ($is_blocked): ?>
                    alert("You are currently blocked from submitting complaints.");
                <?php else: ?>
                    document.getElementById('complaintPopup').style.display = 'flex';
                <?php endif; ?>
            };

            // Close the popup
            function closePopup() {
                document.getElementById('complaintPopup').style.display = 'none';
            }

            // Check video size and duration (max 50MB and 1 minute)
            function checkVideoSizeAndDuration(input) {
                const file = input.files[0];
                if (file) {
                    if (file.size > 50 * 1024 * 1024) {
                        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                        alert(`Please upload up to 50 MB size video. Your file is ${sizeInMB} MB.`);
                        input.value = '';
                        return;
                    }

                    const video = document.createElement('video');
                    video.preload = 'metadata';
                    video.onloadedmetadata = function() {
                        window.URL.revokeObjectURL(video.src);
                        if (video.duration > 60) {
                            alert('Video must be 1 minute or less.');
                            input.value = '';
                        }
                    };
                    video.src = URL.createObjectURL(file);
                }
            }

            // Handle form submission via AJAX with validation
            document.getElementById('complaintForm').onsubmit = function(event) {
                event.preventDefault();
                const description = document.getElementById('description').value.trim();
                const image = document.getElementById('image').files[0];
                const video = document.getElementById('video').files[0];

                if (!description && !image && !video) {
                    alert('Please provide at least a description, image, or video.');
                    return;
                }

                const formData = new FormData(this);
                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.includes("Complaint submitted successfully!")) {
                        const successDiv = document.createElement('div');
                        successDiv.className = 'success-message';
                        successDiv.innerHTML = data;
                        document.body.appendChild(successDiv);
                        setTimeout(() => {
                            successDiv.remove();
                            closePopup();
                            location.reload();
                        }, 3000);
                    } else {
                        alert(data);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while submitting the complaint.');
                });
            };

            // Delete Confirmation Functions
            let deleteId = null;
            function showDeleteConfirmation(id) {
                deleteId = id;
                document.getElementById('deleteConfirmation').style.display = 'block';
            }

            function confirmDelete() {
                if (deleteId) {
                    window.location.href = `?delete_complaint=1&id=${deleteId}`;
                }
                document.getElementById('deleteConfirmation').style.display = 'none';
            }

            function cancelDelete() {
                document.getElementById('deleteConfirmation').style.display = 'none';
            }
        </script>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>