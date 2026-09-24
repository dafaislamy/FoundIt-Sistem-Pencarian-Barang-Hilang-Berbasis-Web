const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// PROFIL USER
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}

// FORM KLAIM
document
    .getElementById("klaimForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();


        const klaimBaru = {

            id: Date.now(),

            namaBarang:
                document.getElementById("namaBarang").value,

            kategori:
                document.getElementById("kategori").value,

            ciriBarang:
                document.getElementById("ciriBarang").value,

            lokasiHilang:
                document.getElementById("lokasiHilang").value,

            keterangan:
                document.getElementById("keterangan").value,

            status: "Menunggu Verifikasi"

        };


        let daftarKlaim =
            JSON.parse(
                localStorage.getItem("daftarKlaim")
            ) || [];


        daftarKlaim.push(klaimBaru);


        localStorage.setItem(
            "daftarKlaim",
            JSON.stringify(daftarKlaim)
        );


        alert(
            "Pengajuan klaim berhasil dikirim!"
        );


        window.location.href =
            "status-klaim.html";

    });