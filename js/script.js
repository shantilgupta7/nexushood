document.addEventListener('DOMContentLoaded', function () {
    // === Sidebar Toggle Functionality ===
    // Select sidebar toggle elements
    const toggle = document.querySelector(".toggle");
    const sidebar = document.querySelector(".sidebar");
    const main = document.querySelector(".main");

    // Check if elements exist to avoid errors
    if (toggle && sidebar && main) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle("active"); // Toggle sidebar active class
            main.classList.toggle("active"); // Toggle main content active class
        });
    }
});