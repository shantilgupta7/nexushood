<?php
session_start();
if (!isset($_SESSION['room_no'])) {
    header("Location: sign-in.php");  // Redirect to login if session is not set
    exit();
} 

// Fetch session data
$payment_details = isset($_SESSION['payment_details']) ? $_SESSION['payment_details'] : [];
$complaint_details = isset($_SESSION['complaint_details']) ? $_SESSION['complaint_details'] : [];
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
                        <a class="nav-link" href="home2.php">
                            <i class="fas fa-home"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="m_details.php">
                            <i class="fas fa-wallet"></i>Maintenance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="c_details.php">
                            <i class="fas fa-comments"></i>Complaint
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about2.php">
                            <i class="fas fa-info-circle"></i>About
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out"></i>Sign-out
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <header class="site-header section-padding-img site-header-image">
                <div class="container">
                    <div class="row">

                        <div class="col-lg-6 col-12 header-info">
                            <h1>
                                <span class="d-block text-primary">NeXusHooD.com</span>
                                <span class="d-block text-dark">Your Society Management Solution.</span>
                            </h1>
                        </div>
                    </div>
                </div>

                <img src="images/header/h2.jpg" class="header-image img-fluid" alt="">
            </header>

            <section class="team section-padding">
            <div class="container">
                    <div class="row">

                        <div class="col-12 text-center">
                            <h2 class="mb-5">Your <span>Society</span> Management Solution.</h2>
                        </div>

                        <div class="col-lg-2 col-12 mt-auto mb-auto">
                            <ul class="nav nav-pills mb-5 mx-auto justify-content-center align-items-center" id="pills-tab" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="pills-home-tab" data-bs-toggle="pill" data-bs-target="#pills-home" type="button" role="tab" aria-controls="pills-home" aria-selected="true">Introduction</button>
                                </li>

                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="pills-youtube-tab" data-bs-toggle="pill" data-bs-target="#pills-youtube" type="button" role="tab" aria-controls="pills-youtube" aria-selected="true">How we work?</button>
                                </li>
                            </ul>
                        </div>

                        <div class="col-lg-10 col-12">
                            <div class="tab-content mt-2" id="pills-tabContent">
                                <div class="tab-pane fade show active" id="pills-home" role="tabpanel" aria-labelledby="pills-home-tab">

                                    <div class="row">
                                        <div class="col-lg-7 col-12">
                                            <img src="images/slideshow/s9.png" class="img-fluid" alt="">
                                        </div>

                                        <div class="col-lg-5 col-12">
                                            <div class="d-flex flex-column h-100 ms-lg-4 mt-lg-0 mt-5">
                                                <h4 class="mb-3"><span>NeXusHooD</span>.com <br>Your <span>Society</span> Management Solution.</h4>

                                                <p>NeXusHooD.com is an innovative society management system designed to simplify and enhance community living.</p>

                                                <p>With its user-friendly interface, residents can easily manage their daily activities, from tracking maintenance payments to addressing complaints, all in one place.</p>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="tab-pane fade" id="pills-youtube" role="tabpanel" aria-labelledby="pills-youtube-tab">

                                    <div class="row">
                                        <div class="col-lg-7 col-12">
                                           
                                            <img src="images/slideshow/s10.png" class="img-fluid" alt="">
                                            
                                        </div>

                                        <div class="col-lg-5 col-12">
                                            <div class="d-flex flex-column h-100 ms-lg-4 mt-lg-0 mt-5">
                                                <h4 class="mb-3">NeXusHooD.com</h4>

                                                <p>The platform offers a comprehensive dashboard that allows users to view payment histories, upcoming dues, and maintenance schedules, ensuring transparency and convenience. NeXusHooD.com also supports property management, providing tools for booking common areas, generating expense reports, and maintaining records effortlessly.</p>

                                                <p>It brings communities closer with communication features, enabling residents to stay informed and connected while promoting a harmonious living environment.</p>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>      
            </section>

        </main>

    <?php
    include 'footer.php';
    ?>

    </body>
</html>
