/* MENGAMBIL DATA PENGGUNA */
const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// MENAMPILKAN PROFIL USER
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}

// FORM LAPORAN BARANG HILANG
document
    .getElementById("laporHilangForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        // MENYIMPAN DATA LAPORAN BARU
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

            status: "Hilang",

            jenisLaporan: "Barang Hilang"

        };

        // MENGAMBIL DAFTAR LAPORAN
        let daftarLaporan =
            JSON.parse(
                localStorage.getItem("daftarLaporan")
            ) || [];

        daftarLaporan.push(laporanBaru);

        // MENYIMPAN LAPORAN KE LOCAL STORAGE
        localStorage.setItem(
            "daftarLaporan",
            JSON.stringify(daftarLaporan)
        );

        alert(
            "Laporan barang hilang berhasil dikirim!"
        );

        window.location.href =
            "riwayat-laporan.html";

    });