function showRoomSelection() {
    document.getElementById("roomSelectionModal").style.display = "block";
}

function fillMemberForm(room_no, bhk, sq_feet) {
    closeModal();
    document.getElementById("addMemberModal").style.display = "block";
    document.getElementById("room_no").value = room_no;
    document.getElementById("bhk").value = bhk;
    document.getElementById("sq_feet").value = sq_feet;
    document.getElementById("occupancy_status").value = "Owner"; // Default to Owner
}

function showTenantDetails(room_no) {
    document.getElementById('tenantModal').style.display = 'block';
    document.getElementById('tenant_room_no').value = room_no;

    // Fetch and populate tenant details via AJAX
    $.get('member.php', { action: 'get_tenant', room_no: room_no }, function(data) {
        var tenant = JSON.parse(data);
        if (tenant) {
            document.getElementById('tenant_name').value = tenant.tenant_name || '';
            document.getElementById('tenant_email').value = tenant.tenant_email || '';
            document.getElementById('tenant_contact').value = tenant.tenant_contact || '';
            document.getElementById('tenant_document_preview').innerHTML = tenant.tenant_document ? 
                `<a href="${tenant.tenant_document}" target="_blank">View Current Document</a>` : 'No document uploaded';
        } else {
            document.getElementById('tenant_name').value = '';
            document.getElementById('tenant_email').value = '';
            document.getElementById('tenant_contact').value = '';
            document.getElementById('tenant_document_preview').innerHTML = '';
        }
    });
}

function closeModal() {
    document.getElementById("roomSelectionModal").style.display = "none";
    document.getElementById("addMemberModal").style.display = "none";
    document.getElementById("editMemberModal").style.display = "none";
    document.getElementById("tenantModal").style.display = "none";
}

function validateForm() {
    var email = document.getElementById("email")?.value || document.getElementById("edit_email")?.value;
    var contact = document.getElementById("contact")?.value || document.getElementById("edit_contact")?.value;

    var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    var contactPattern = /^\d{10}$/;
    if (!contactPattern.test(contact)) {
        alert("Please enter a valid 10-digit contact number.");
        return false;
    }

    return true;
}

function validateTenantForm() {
    var email = document.getElementById("tenant_email").value;
    var contact = document.getElementById("tenant_contact").value;

    var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    var contactPattern = /^\d{10}$/;
    if (!contactPattern.test(contact)) {
        alert("Please enter a valid 10-digit contact number.");
        return false;
    }

    return true;
}

function searchTable() {
    var input = document.getElementById("searchInput").value.toLowerCase().trim();
    var table = document.getElementById("memberTable");
    var tbody = table.getElementsByTagName("tbody")[0];
    var rows = Array.from(tbody.getElementsByTagName("tr"));
    
    var matchedRow = null;
    rows.forEach(function(row) {
        // Adjusted indices assuming "Category" column is hidden
        var roomNo = row.cells[0].textContent.toLowerCase().trim();  // Room/Shop No (index 0)
        var name = row.cells[3].textContent.toLowerCase().trim();    // Flat Holder Name (index 3)
        var contact = row.cells[5].textContent.toLowerCase().trim(); // Contact (index 5, shifted due to Category removal)

        // Check if any of the fields include the search input
        if (roomNo.includes(input) || name.includes(input) || contact.includes(input)) {
            if (!matchedRow) {
                matchedRow = row; // Store the first matched row to move it to the top
            }
            row.style.display = ""; // Show the row
        } else {
            row.style.display = "none"; // Hide the row
        }
    });

    // Move the first matched row to the top if there is one
    if (matchedRow) {
        tbody.removeChild(matchedRow);
        tbody.insertBefore(matchedRow, tbody.firstChild);
    }
}