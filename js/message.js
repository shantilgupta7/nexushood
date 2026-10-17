function toggleMessages() {
    const messageList = document.getElementById("message-list");
    if (messageList.style.display === "block") {
        messageList.style.display = "none";
    } else {
        messageList.style.display = "block";
    }
}
