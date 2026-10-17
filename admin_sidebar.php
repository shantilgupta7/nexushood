<!-- admin_sidebar.php -->
<div class="sidebar">
    <ul>
        <li class="logo">
            <a href="">
                <div class="icon"><img src="images/logo2.png" alt="logo_image" class="logo"></div>
                <div class="text">NeXusHood.com</div>
            </a>
        </li>
        <div class="Menulist">
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'mysociety.php' ? 'active' : ''; ?>">
                <a href="mysociety.php">
                    <div class="icon"><ion-icon name="home-outline"></ion-icon></div>
                    <div class="text">Dashboard</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'member.php' ? 'active' : ''; ?>">
                <a href="member.php">
                    <div class="icon"><ion-icon name="person-add-outline"></ion-icon></div>
                    <div class="text">Members</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'c_member.php' ? 'active' : ''; ?>">
                <a href="c_member.php">
                    <div class="icon"><ion-icon name="people-outline"></ion-icon></div>
                    <div class="text">Committee</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'maintenance.php' ? 'active' : ''; ?>">
                <a href="maintenance.php">
                    <div class="icon"><ion-icon name="clipboard-outline"></ion-icon></div>
                    <div class="text">Maint. Chart</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'report.php' ? 'active' : ''; ?>">
                <a href="report.php">
                    <div class="icon"><ion-icon name="document-text-outline"></ion-icon></div>
                    <div class="text">Report</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'payment.php' ? 'active' : ''; ?>">
                <a href="payment.php">
                    <div class="icon"><ion-icon name="wallet-outline"></ion-icon></div>
                    <div class="text">Income</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'expense.php' ? 'active' : ''; ?>">
                <a href="expense.php">
                    <div class="icon"><ion-icon name="cash-outline"></ion-icon></div>
                    <div class="text">Expense</div>
                </a>
            </li>
            <li class="<?php echo basename($_SERVER['PHP_SELF']) == 'complaint.php' ? 'active' : ''; ?>">
                <a href="complaint.php">
                    <div class="icon"><ion-icon name="chatbubble-ellipses-outline"></ion-icon></div>
                    <div class="text">Complaint</div>
                </a>
            </li>
            <li>
                <a href="logout2.php">
                    <div class="icon"><ion-icon name="log-out-outline"></ion-icon></div>
                    <div class="text">Logout</div>
                </a>
            </li>
        </div>
    </ul>
</div>

