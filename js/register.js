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