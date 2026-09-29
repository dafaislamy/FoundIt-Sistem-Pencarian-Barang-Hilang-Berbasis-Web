// MEMILIH ROLE
function pilihRole(role) {
    const roleInput = document.getElementById("role");
    const loginButton = document.querySelector(".btn-login");
    const roleCards = document.querySelectorAll(".role-card");
    const daftarLink = document.getElementById("daftarLink");
    const registerNote = document.getElementById("registerNote");

    roleInput.value = role;

    // Menghapus status aktif dari semua kartu
    roleCards.forEach(card => {
        card.classList.remove("active");
    });

    if (role === "user") {
        roleCards[0].classList.add("active");

        loginButton.innerText = "Login sebagai User";

        // Menampilkan tautan registrasi untuk user
        registerNote.style.display = "block";
        daftarLink.innerText = "Daftar sebagai User";
        daftarLink.href = "user/register.html";

    } else {
        roleCards[1].classList.add("active");

        loginButton.innerText = "Login sebagai Admin";

        // Menyembunyikan tautan registrasi untuk admin
        registerNote.style.display = "none";
    }
}


// VALIDASI PASSWORD
const passwordInput = document.getElementById("password");

passwordInput.addEventListener("input", function () {
    const password = passwordInput.value;

    const lengthCheck = document.getElementById("lengthCheck");
    const uppercaseCheck = document.getElementById("uppercaseCheck");
    const symbolCheck = document.getElementById("symbolCheck");

    // Memeriksa persyaratan password
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasSymbol = /[^A-Za-z0-9]/.test(password);

    // Memperbarui tampilan indikator password
    lengthCheck.classList.toggle("valid", hasLength);
    uppercaseCheck.classList.toggle("valid", hasUppercase);
    symbolCheck.classList.toggle("valid", hasSymbol);

    lengthCheck.textContent =
        (hasLength ? "✓ " : "✕ ") + "Minimal 8 karakter";

    uppercaseCheck.textContent =
        (hasUppercase ? "✓ " : "✕ ") +
        "Mengandung huruf kapital (A-Z)";

    symbolCheck.textContent =
        (hasSymbol ? "✓ " : "✕ ") +
        "Mengandung simbol (!@#$%^&*)";
});


// FORM LOGIN
document.getElementById("loginForm").addEventListener("submit", function (event) {
    event.preventDefault();

    const role = document.getElementById("role").value;
    const password = passwordInput.value;

    // Memeriksa kembali persyaratan password
    const hasLength = password.length >= 8;
    const hasUppercase = /[A-Z]/.test(password);
    const hasSymbol = /[^A-Za-z0-9]/.test(password);

    if (!hasLength || !hasUppercase || !hasSymbol) {
        alert("Password belum memenuhi semua persyaratan.");
        return;
    }

    // Mengarahkan pengguna sesuai role yang dipilih
    if (role === "user") {
        window.location.href = "user/dashboard.html";
    } else {
        window.location.href = "admin/admin-dashboard.html";
    }
});