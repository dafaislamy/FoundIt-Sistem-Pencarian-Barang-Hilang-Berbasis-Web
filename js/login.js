
document.addEventListener("DOMContentLoaded", function () {

    // MEMILIH ROLE
    window.pilihRole = function (role) {

        const roleInput = document.getElementById("role");
        const loginButton = document.getElementById("loginButton");
        const roleCards = document.querySelectorAll(".role-card");
        const registerNote = document.getElementById("registerNote");

        roleInput.value = role;

        roleCards.forEach(card => {
            card.classList.remove("active");
        });

        if (role === "user") {
            roleCards[0].classList.add("active");
            loginButton.innerText = "Login sebagai User";
            registerNote.style.display = "block";
        } else {
            roleCards[1].classList.add("active");
            loginButton.innerText = "Login sebagai Admin";
            registerNote.style.display = "none";
        }
    };


    // TAMPILKAN / SEMBUNYIKAN PASSWORD
    const passwordInput = document.getElementById("password");
    const togglePassword = document.getElementById("togglePassword");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passwordInput && togglePassword && eyeIcon) {

        togglePassword.addEventListener("click", function (event) {

            event.preventDefault();

            const sedangTersembunyi = passwordInput.type === "password";

            passwordInput.type = sedangTersembunyi ? "text" : "password";

            eyeIcon.classList.toggle("bi-eye", !sedangTersembunyi);
            eyeIcon.classList.toggle("bi-eye-slash", sedangTersembunyi);

            togglePassword.setAttribute(
                "aria-label",
                sedangTersembunyi ? "Sembunyikan password" : "Tampilkan password"
            );

        });

    }


    // MEMPERTAHANKAN ROLE
    const role = document.getElementById("role").value;
    window.pilihRole(role);

});
