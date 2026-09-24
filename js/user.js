const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// DATA USER
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

    document.getElementById("welcomeName").textContent =
        "Selamat datang, " + dataUser.nama + "!";
}

// DATA LAPORAN
const daftarLaporan =
    JSON.parse(
        localStorage.getItem("daftarLaporan")
    ) || [];

// DATA KLAIM
const daftarKlaim =
    JSON.parse(
        localStorage.getItem("daftarKlaim")
    ) || [];

// STATISTIK
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

// TAMPILKAN STATISTIK
document.getElementById("totalHilang").textContent =
    jumlahHilang;


document.getElementById("totalDitemukan").textContent =
    jumlahDitemukan;


document.getElementById("totalKlaim").textContent =
    jumlahKlaim;


document.getElementById("totalDikembalikan").textContent =
    0;

// LAPORAN TERBARU
const reportList =
    document.querySelector(".report-list");


if (reportList) {

    reportList.innerHTML = "";


    const laporanTerbaru =
        [...daftarLaporan]
            .reverse()
            .slice(0, 3);


    if (laporanTerbaru.length === 0) {

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