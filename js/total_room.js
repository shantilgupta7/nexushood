function showInsertModal() {
    const modal = document.getElementById("insertModal");
    modal.style.display = "block";
    setTimeout(() => modal.classList.add("show"), 10);
}

function showEditModal(room_no, floor, bhk, sq_feet, furniture) {
    const modal = document.getElementById("editModal");
    modal.style.display = "block";
    setTimeout(() => modal.classList.add("show"), 10);
    document.getElementById("edit_room_no").value = room_no;
    document.getElementById("edit_floor").value = floor;
    document.getElementById("edit_bhk").value = bhk;
    document.getElementById("edit_sq_feet").value = sq_feet;
    document.getElementById("edit_furniture").value = furniture;
}

function closeModal() {
    const modals = document.querySelectorAll(".modal");
    modals.forEach(modal => {
        modal.classList.remove("show");
        setTimeout(() => modal.style.display = "none", 300);
    });
}