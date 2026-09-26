document
    .getElementById("registerForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        const dataUser = {
            nama: document.getElementById("nama").value,
            nim: document.getElementById("nim").value,
            fakultas: document.getElementById("fakultas").value,
            jurusan: document.getElementById("jurusan").value,
            angkatan: document.getElementById("angkatan").value,
            email: document.getElementById("email").value,
            nohp: document.getElementById("nohp").value
        };

        localStorage.setItem(
            "dataUser",
            JSON.stringify(dataUser)
        );

        window.location.href = "dashboard.html";

    });

document.addEventListener("DOMContentLoaded", function () {
    const nameInput = document.getElementById("nama");

    if (nameInput) {
        nameInput.addEventListener("input", function () {
            this.value = this.value.replace(/\b\w/g, function (char) {
                return char.toUpperCase();
            });
        });
    }

    const fakultasSelect = document.getElementById("fakultas");
    const prodiSelect = document.getElementById("prodi");

    const dataProdi = {
        FTI: ["Teknik Informatika", "Sistem Informasi"],
        FEB: ["Manajemen", "Akuntansi"]
    };

    if (fakultasSelect && prodiSelect) {
        fakultasSelect.addEventListener("change", function () {
            const selectedFakultas = this.value;

            prodiSelect.innerHTML = '<option value="">-- Pilih Prodi --</option>';

            if (selectedFakultas && dataProdi[selectedFakultas]) {
                prodiSelect.disabled = false;

                dataProdi[selectedFakultas].forEach(function (prodi) {
                    const option = document.createElement("option");
                    option.value = prodi;
                    option.textContent = prodi;
                    prodiSelect.appendChild(option);
                });
            } else {
                prodiSelect.disabled = true;
            }
        });
    }
});
