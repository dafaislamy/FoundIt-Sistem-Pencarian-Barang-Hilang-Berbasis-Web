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

// FORM LAPORAN
document
    .getElementById("laporDitemukanForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();


        const laporanBaru = {

            id: Date.now(),

            namaBarang:
                document.getElementById("namaBarang").value,

            kategori:
                document.getElementById("kategori").value,

            lokasi:
                document.getElementById("lokasi").value,

            tanggal:
                document.getElementById("tanggal").value,

            deskripsi:
                document.getElementById("deskripsi").value,

            status: "Ditemukan",

            jenisLaporan: "Barang Ditemukan"

        };


        let daftarLaporan =
            JSON.parse(
                localStorage.getItem("daftarLaporan")
            ) || [];


        daftarLaporan.push(laporanBaru);


        localStorage.setItem(
            "daftarLaporan",
            JSON.stringify(daftarLaporan)
        );


        alert(
            "Laporan barang ditemukan berhasil dikirim!"
        );


        window.location.href =
            "riwayat-laporan.html";

    });