const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}


document
    .getElementById("laporHilangForm")
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

            status: "Hilang",

            jenisLaporan: "Barang Hilang"

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
            "Laporan barang hilang berhasil dikirim!"
        );


        window.location.href =
            "riwayat-laporan.html";

    });