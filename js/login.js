
// MEMILIH ROLE
function pilihRole(role) {

    const roleInput = document.getElementById("role");
    const loginButton = document.querySelector(".btn-login");
    const roleCards = document.querySelectorAll(".role-card");
    const registerNote = document.getElementById("registerNote");

    roleInput.value = role;

    // Menghapus status aktif
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
}


// VALIDASI PASSWORD
document.addEventListener("DOMContentLoaded", function () {

    const passwordInput = document.getElementById("password");
    const loginForm = document.getElementById("loginForm");

    function periksaPassword() {

        const password = passwordInput.value;

        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasSymbol = /[^A-Za-z0-9]/.test(password);

        const lengthCheck = document.getElementById("lengthCheck");
        const uppercaseCheck = document.getElementById("uppercaseCheck");
        const symbolCheck = document.getElementById("symbolCheck");

        lengthCheck.classList.toggle("valid", hasLength);
        uppercaseCheck.classList.toggle("valid", hasUppercase);
        symbolCheck.classList.toggle("valid", hasSymbol);

        lengthCheck.textContent =
            (hasLength ? "✓ " : "✕ ") +
            "Minimal 8 karakter";

        uppercaseCheck.textContent =
            (hasUppercase ? "✓ " : "✕ ") +
            "Mengandung huruf kapital (A-Z)";

        symbolCheck.textContent =
            (hasSymbol ? "✓ " : "✕ ") +
            "Mengandung simbol (!@#$%^&*)";

        return hasLength && hasUppercase && hasSymbol;
    }

    // Memperbarui indikator saat mengetik
    passwordInput.addEventListener("input", periksaPassword);

    // Validasi sebelum mengirim form
    loginForm.addEventListener("submit", function (event) {

        if (!periksaPassword()) {

            event.preventDefault();

            alert("Password belum memenuhi semua persyaratan!");

        }

        // Jika valid, form dikirim ke login.php
    });

    // Mempertahankan tampilan role setelah halaman dimuat
    const role = document.getElementById("role").value;

    pilihRole(role);

});
