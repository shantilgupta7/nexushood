document.addEventListener('DOMContentLoaded', function () {
    console.log("complaint.js loaded");

    // Initialize Accordion for complaint form
    function initializeAccordion() {
        const accordionHeaders = document.querySelectorAll('.accordion-header');
        accordionHeaders.forEach(header => {
            header.addEventListener('click', function() {
                const accordion = this.parentElement;
                const content = accordion.querySelector('.accordion-content');
                
                accordion.classList.toggle('active');
                
                if (accordion.classList.contains('active')) {
                    content.style.maxHeight = content.scrollHeight + 'px';
                } else {
                    content.style.maxHeight = '0';
                }
                
                // Close other accordions
                document.querySelectorAll('.form-accordion').forEach(otherAccordion => {
                    if (otherAccordion !== accordion) {
                        otherAccordion.classList.remove('active');
                        otherAccordion.querySelector('.accordion-content').style.maxHeight = '0';
                    }
                });
            });
        });
    }

    initializeAccordion();

    // Modal Functionality for Reply
    const replyModal = document.getElementById("replyModal");
    const replyCloseBtn = replyModal.querySelector(".close");
    const replyButtons = document.querySelectorAll(".accept-button");

    replyButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const commentId = this.getAttribute('data-id');
            console.log('Reply button clicked, ID:', commentId); // Debug
            document.getElementById("reply_id").value = commentId;
            replyModal.style.display = "block";
        });
    });

    replyCloseBtn.addEventListener('click', function () {
        replyModal.style.display = "none";
    });

    // Modal Functionality for Resolve
    const resolveModal = document.getElementById("resolveModal");
    const resolveCloseBtn = resolveModal.querySelector(".close");
    const resolveButtons = document.querySelectorAll(".resolve-button");

    resolveButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const complaintId = this.getAttribute('data-id');
            console.log('Resolve button clicked, ID:', complaintId); // Debug
            document.getElementById("resolve_id").value = complaintId;
            resolveModal.style.display = "block";
        });
    });

    resolveCloseBtn.addEventListener('click', function () {
        resolveModal.style.display = "none";
    });

    // Modal Functionality for Delete Range
    const deleteRangeModal = document.getElementById("deleteRangeModal");
    const deleteRangeBtn = document.getElementById("showDeleteRange");
    const deleteRangeCloseBtn = deleteRangeModal.querySelector(".close");

    deleteRangeBtn.addEventListener('click', function(event) {
        event.preventDefault();
        deleteRangeModal.style.display = "block";
    });

    deleteRangeCloseBtn.addEventListener('click', function() {
        deleteRangeModal.style.display = "none";
    });

    // Close modals when clicking outside
    window.addEventListener('click', function (event) {
        if (event.target == replyModal) {
            replyModal.style.display = "none";
        }
        if (event.target == resolveModal) {
            resolveModal.style.display = "none";
        }
        if (event.target == deleteRangeModal) {
            deleteRangeModal.style.display = "none";
        }
    });

    // Validate date range
    const deleteRangeForm = document.getElementById('deleteRangeForm');
    deleteRangeForm.addEventListener('submit', function(event) {
        const startDate = new Date(document.getElementById('start_date').value);
        const endDate = new Date(document.getElementById('end_date').value);
        
        if (startDate > endDate) {
            event.preventDefault();
            alert('Start date must be before end date');
            return false;
        }
        
        if (endDate > new Date()) {
            event.preventDefault();
            alert('End date cannot be in the future');
            return false;
        }
    });

    // Prevent duplicate form submissions
    document.getElementById('replyForm').addEventListener('submit', function() {
        this.querySelector('button[type="submit"]').disabled = true;
        console.log('Reply form submitted'); // Debug
    });

    document.getElementById('resolveForm').addEventListener('submit', function() {
        this.querySelector('button[type="submit"]').disabled = true;
        console.log('Resolve form submitted'); // Debug
    });

    // Block/Unblock Toggle Functionality
    const blockButtons = document.querySelectorAll('.toggle-block-btn');
    blockButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const id = this.getAttribute('data-id');
            const isBlocked = this.classList.contains('unblock-btn');
            const newStatus = isBlocked ? 0 : 1;
            console.log('Toggle block clicked, ID:', id, 'New Status:', newStatus); // Debug

            fetch(`?toggle_block=1&id=${id}`, { method: 'GET' })
                .then(response => {
                    if (response.ok) {
                        this.textContent = isBlocked ? 'Block' : 'Unblock';
                        this.classList.toggle('block-btn');
                        this.classList.toggle('unblock-btn');
                    } else {
                        console.error('Failed to update block status');
                    }
                })
                .catch(error => console.error('Error:', error));
        });
    });

    // Video Size and Duration Check
    window.checkVideoSizeAndDuration = function(input) {
        const file = input.files[0];
        if (file) {
            const fileSizeMB = file.size / (1024 * 1024); // Convert to MB
            if (fileSizeMB > 50) {
                alert('Video file size exceeds 50MB limit.');
                input.value = '';
                return;
            }

            const video = document.createElement('video');
            video.preload = 'metadata';
            video.onloadedmetadata = function() {
                window.URL.revokeObjectURL(video.src);
                const duration = video.duration;
                if (duration > 60) { // 1 minute = 60 seconds
                    alert('Video duration exceeds 1 minute limit.');
                    input.value = '';
                }
            };
            video.src = URL.createObjectURL(file);
        }
    };
});