/* MENGAMBIL DATA PENGGUNA */
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

// MENGAMBIL DATA LAPORAN
const daftarLaporan =
    JSON.parse(
        localStorage.getItem("daftarLaporan")
    ) || [];

const riwayatList =
    document.getElementById("riwayatList");

const emptyHistory =
    document.getElementById("emptyHistory");

const jumlahLaporan =
    document.getElementById("jumlahLaporan");

// MENAMPILKAN JUMLAH LAPORAN
jumlahLaporan.textContent =
    daftarLaporan.length +
    " laporan ditemukan";

// MENAMPILKAN PESAN JIKA BELUM ADA LAPORAN
if (daftarLaporan.length === 0) {

    emptyHistory.style.display = "block";

} else {

    emptyHistory.style.display = "none";

}

// MENAMPILKAN RIWAYAT LAPORAN
daftarLaporan
    .slice()
    .reverse()
    .forEach(function (laporan) {

        const card =
            document.createElement("div");

        card.className =
            "history-card";

        // MEMBUAT KARTU BERISI DETAIL LAPORAN
        card.innerHTML = `

            <div class="history-top">

                <div class="history-title">

                    <h3>
                        ${laporan.namaBarang}
                    </h3>

                    <p>
                        ${laporan.jenisLaporan}
                    </p>

                </div>

                <span class="history-status">
                    ${laporan.status}
                </span>

            </div>


            <div class="history-detail">

                <div class="history-detail-item">

                    <span>
                        Kategori
                    </span>

                    <strong>
                        ${laporan.kategori}
                    </strong>

                </div>


                <div class="history-detail-item">

                    <span>
                        Lokasi
                    </span>

                    <strong>
                        ${laporan.lokasi}
                    </strong>

                </div>


                <div class="history-detail-item">

                    <span>
                        Tanggal
                    </span>

                    <strong>
                        ${laporan.tanggal}
                    </strong>

                </div>

            </div>

        `;

        // MENAMBAHKAN KARTU KE DAFTAR RIWAYAT
        riwayatList.appendChild(card);

    });