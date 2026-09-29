/* MENGAMBIL DATA USER */
const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// MENAMPILKAN INFORMASI USER
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

    document.getElementById("welcomeName").textContent =
        "Selamat datang, " + dataUser.nama + "!";
}

// MENGAMBIL DATA LAPORAN
const daftarLaporan =
    JSON.parse(
        localStorage.getItem("daftarLaporan")
    ) || [];

// MENGAMBIL DATA KLAIM
const daftarKlaim =
    JSON.parse(
        localStorage.getItem("daftarKlaim")
    ) || [];

// MENGHITUNG STATISTIK LAPORAN
const jumlahHilang =
    daftarLaporan.filter(function (laporan) {

        return laporan.status === "Hilang";

    }).length;


const jumlahDitemukan =
    daftarLaporan.filter(function (laporan) {

        return laporan.status === "Ditemukan";

    }).length;


const jumlahKlaim =
    daftarKlaim.length;

// MENAMPILKAN STATISTIK PADA DASHBOARD
document.getElementById("totalHilang").textContent =
    jumlahHilang;


document.getElementById("totalDitemukan").textContent =
    jumlahDitemukan;


document.getElementById("totalKlaim").textContent =
    jumlahKlaim;


document.getElementById("totalDikembalikan").textContent =
    0;

// MENAMPILKAN LAPORAN TERBARU
const reportList =
    document.querySelector(".report-list");


if (reportList) {

    reportList.innerHTML = "";

    // MENGAMBIL TIGA LAPORAN TERBARU
    const laporanTerbaru =
        [...daftarLaporan]
            .reverse()
            .slice(0, 3);


    if (laporanTerbaru.length === 0) {

        // MENAMPILKAN PESAN JIKA BELUM ADA LAPORAN
        reportList.innerHTML = `
            <div class="report-item">

                <div class="report-info">

                    <h3>
                        Belum Ada Laporan
                    </h3>

                    <p>
                        Kamu belum membuat laporan barang.
                    </p>

                </div>

            </div>
        `;

    } else {

        // MENAMPILKAN SETIAP LAPORAN TERBARU
        laporanTerbaru.forEach(function (laporan) {

            let icon = "📦";
            let jenisClass = "lost";

            if (laporan.status === "Ditemukan") {
                icon = "📍";
                jenisClass = "found";
            }


            let statusClass = "waiting";

            if (laporan.status === "Ditemukan") {
                statusClass = "verified";
            }


            const item = document.createElement("div");

            item.className = "report-item";


            item.innerHTML = `

                <div class="report-icon ${jenisClass}">
                    ${icon}
                </div>

                <div class="report-info">

                    <h3>
                        ${laporan.namaBarang}
                    </h3>

                    <p>
                        Dilaporkan pada
                        ${laporan.tanggal}
                    </p>

                </div>

                <span class="status ${statusClass}">
                    ${laporan.status}
                </span>

            `;

            reportList.appendChild(item);

        });

    }

}