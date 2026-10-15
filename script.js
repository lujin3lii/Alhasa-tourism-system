function toggleMenu() {
    const sidebar = document.getElementById("sidebar");

    if (sidebar) {
        sidebar.classList.toggle("open");
    }
}

document.addEventListener("click", function (event) {
    const sidebar = document.getElementById("sidebar");
    const menuButton = document.querySelector(".menu-button");

    if (!sidebar || !menuButton) return;

    if (
        !sidebar.contains(event.target) &&
        !menuButton.contains(event.target)
    ) {
        sidebar.classList.remove("open");
    }
}); 