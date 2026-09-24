const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

    document.getElementById("profilePhoto").textContent =
        dataUser.nama.charAt(0).toUpperCase();

    document.getElementById("namaProfil").textContent =
        dataUser.nama;

    document.getElementById("nimProfil").textContent =
        dataUser.nim;

    document.getElementById("dataNama").textContent =
        dataUser.nama;

    document.getElementById("dataNim").textContent =
        dataUser.nim;

    document.getElementById("dataFakultas").textContent =
        dataUser.fakultas;

    document.getElementById("dataJurusan").textContent =
        dataUser.jurusan;

    document.getElementById("dataAngkatan").textContent =
        dataUser.angkatan;

    document.getElementById("dataEmail").textContent =
        dataUser.email;

    document.getElementById("dataNohp").textContent =
        dataUser.nohp;

}