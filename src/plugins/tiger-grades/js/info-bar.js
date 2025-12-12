document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll(".tigr-info-bar__dismiss").forEach(function(button) {
        button.addEventListener("click", function() {
            const infoBar = this.closest(".tigr-info-bar");
            infoBar.style.transition = "opacity 0.3s ease, transform 0.3s ease";
            infoBar.style.opacity = "0";
            infoBar.style.transform = "translateX(100%)";
            setTimeout(function() {
                infoBar.remove();
            }, 300);
        });
    });
}); 