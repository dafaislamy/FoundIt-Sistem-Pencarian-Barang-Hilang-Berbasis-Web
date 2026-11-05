
document.addEventListener("DOMContentLoaded", function () {

    // Mengubah huruf awal nama menjadi kapital
    const nameInput = document.getElementById("nama");

    if (nameInput) {
        nameInput.addEventListener("input", function () {
            this.value = this.value.replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        });
    }

    // Validasi password
    const registerForm = document.getElementById("registerForm");

    if (registerForm) {
        registerForm.addEventListener("submit", function (event) {

            const password = document.getElementById("password").value;

            const hasLength = password.length >= 8;
            const hasUppercase = /[A-Z]/.test(password);
            const hasSymbol = /[^A-Za-z0-9]/.test(password);

            if (!hasLength || !hasUppercase || !hasSymbol) {
                event.preventDefault();

                alert(
                    "Password minimal 8 karakter, " +
                    "mengandung huruf kapital dan simbol!"
                );
            }

        });
    }

});
