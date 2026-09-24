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

// AMBIL DATA LAPORAN
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

// TAMPILKAN JUMLAH
jumlahLaporan.textContent =
    daftarLaporan.length +
    " laporan ditemukan";

// JIKA BELUM ADA LAPORAN
if (daftarLaporan.length === 0) {

    emptyHistory.style.display = "block";

} else {

    emptyHistory.style.display = "none";

}

// TAMPILKAN LAPORAN
daftarLaporan
    .slice()
    .reverse()
    .forEach(function (laporan) {

        const card =
            document.createElement("div");

        card.className =
            "history-card";


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


        riwayatList.appendChild(card);

    });