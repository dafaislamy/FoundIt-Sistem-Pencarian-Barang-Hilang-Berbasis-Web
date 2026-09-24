const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

if (dataUser) {

    document.getElementById("nama").value =
        dataUser.nama;

    document.getElementById("nim").value =
        dataUser.nim;

    document.getElementById("fakultas").value =
        dataUser.fakultas;

    document.getElementById("jurusan").value =
        dataUser.jurusan;

    document.getElementById("angkatan").value =
        dataUser.angkatan;

    document.getElementById("email").value =
        dataUser.email;

    document.getElementById("nohp").value =
        dataUser.nohp;
}

document
    .getElementById("editProfileForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        const dataBaru = {

            nama: document.getElementById("nama").value,

            nim: document.getElementById("nim").value,

            fakultas:
                document.getElementById("fakultas").value,

            jurusan:
                document.getElementById("jurusan").value,

            angkatan:
                document.getElementById("angkatan").value,

            email:
                document.getElementById("email").value,

            nohp:
                document.getElementById("nohp").value
        };

        localStorage.setItem(
            "dataUser",
            JSON.stringify(dataBaru)
        );

        alert("Profil berhasil diperbarui!");

        window.location.href = "profil.html";

    });