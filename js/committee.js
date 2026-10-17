 // Open Add Modal
 function openAddModal() {
    const modal = document.getElementById('addModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);

    // Clear form fields
    document.getElementById('addForm').reset();
    document.getElementById('add_age').value = '';
}

// Close Add Modal
function closeAddModal() {
    const modal = document.getElementById('addModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

// Open Edit Modal and populate form
function openEditModal(member) {
    const modal = document.getElementById('editModal');
    modal.style.display = 'block';
    setTimeout(() => modal.classList.add('show'), 10);

    document.getElementById('edit_id').value = member.id;
    document.getElementById('name').value = member.name || '';
    document.getElementById('designation').value = member.designation || '';
    document.getElementById('dob').value = member.dob || '';
    document.getElementById('age').value = member.age || '';
    document.getElementById('email').value = member.email || '';
    document.getElementById('phone').value = member.phone || '';
    document.getElementById('join_date').value = member.join_date || '';
    const currentDocDiv = document.getElementById('currentDocument');
    if (member.document) {
        currentDocDiv.innerHTML = `<a href="${member.document}" target="_blank">View Current Document</a>`;
    } else {
        currentDocDiv.innerHTML = '';
    }
}

// Close Edit Modal
function closeEditModal() {
    const modal = document.getElementById('editModal');
    modal.classList.remove('show');
    setTimeout(() => modal.style.display = 'none', 300);
}

// Close modal when clicking outside
window.onclick = function(event) {
    const addModal = document.getElementById('addModal');
    const editModal = document.getElementById('editModal');
    if (event.target == addModal) {
        closeAddModal();
    } else if (event.target == editModal) {
        closeEditModal();
    }
}

// Image Modal Functions
function openImageModal(img) {
    const modal = document.getElementById('imageModal');
    const modalImg = document.getElementById('img01');
    modal.style.display = 'block';
    modalImg.src = img.src;
}

function closeImageModal() {
    const modal = document.getElementById('imageModal');
    modal.style.display = 'none';
}

// Validate form with DOB check
function validateForm(formType) {
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to midnight for accurate comparison

    let dobInput, dobValue;
    if (formType === 'add') {
        dobInput = document.getElementById('add_dob');
        dobValue = new Date(dobInput.value);
    } else if (formType === 'edit') {
        dobInput = document.getElementById('dob');
        dobValue = new Date(dobInput.value);
    }

    if (dobValue > today) {
        alert("Date of Birth cannot be later than today!");
        dobInput.focus();
        return false;
    }

    return true; // Proceed with form submission if valid
}

// Update age when DOB changes for Add Modal
document.getElementById('add_dob').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    const age = today.getFullYear() - dob.getFullYear();
    document.getElementById('add_age').value = age;
});

// Update age when DOB changes for Edit Modal
document.getElementById('dob').addEventListener('change', function() {
    const dob = new Date(this.value);
    const today = new Date();
    const age = today.getFullYear() - dob.getFullYear();
    document.getElementById('age').value = age;
});