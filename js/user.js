// MEMBUAT DATA CONTOH OTOMATIS DENGAN GAMBAR
if (!localStorage.getItem("daftarLaporan")) {
    const dataAwal = [
        { 
            id: 1, 
            namaBarang: "Flashdisk SanDisk 64GB", 
            kategori: "Elektronik", 
            lokasi: "Fakultas Teknik", 
            tanggal: "2026-10-13", 
            status: "Hilang",
            gambar: "https://images.pexels.com/photos/18641665/pexels-photo-18641665.png?auto=compress&cs=tinysrgb&w=600"
        },
        { 
            id: 2, 
            namaBarang: "Kunci Motor Yamaha", 
            kategori: "Aksesoris", 
            lokasi: "Parkir Kampus", 
            tanggal: "2026-10-12", 
            status: "Ditemukan",
            gambar: "https://motosneno.cdn.magazord.com.br/img/2025/09/produto/48777/5sl8251108.jpg" 
        },
        { 
            id: 3, 
            namaBarang: "Handphone", 
            kategori: "Elektronik", 
            lokasi: "Kantin Kampus", 
            tanggal: "2026-10-11", 
            status: "Hilang",
            gambar: "https://m.media-amazon.com/images/I/41Hh3xwzdFL._SL500_.jpg"
        }
    ];
    localStorage.setItem("daftarLaporan", JSON.stringify(dataAwal));
}

document.addEventListener("DOMContentLoaded", function () {

    /* ========================================
        MENGAMBIL DATA USER
    ======================================== */
    let dataUser = null;

    try {
        dataUser = JSON.parse(
            localStorage.getItem("dataUser")
        );
    } catch (error) {
        dataUser = null;
    }

    // MENAMPILKAN INFORMASI USER
    if (dataUser && dataUser.nama) {
        const profileName = document.getElementById("profileName");
        const profileInitial = document.getElementById("profileInitial");
        const welcomeName = document.getElementById("welcomeName");

        if (profileName) {
            profileName.textContent = dataUser.nama;
        }

        if (profileInitial) {
            profileInitial.textContent = dataUser.nama.charAt(0).toUpperCase();
        }

        if (welcomeName) {
            welcomeName.textContent = "Selamat datang, " + dataUser.nama + "!";
        }
    }

    /* ========================================
        MENGAMBIL DATA LAPORAN & KLAIM
    ======================================== */
    const daftarLaporan = JSON.parse(
        localStorage.getItem("daftarLaporan")
    ) || [];

    const daftarKlaim = JSON.parse(
        localStorage.getItem("daftarKlaim")
    ) || [];

    /* ========================================
        MENGHITUNG STATISTIK LAPORAN
    ======================================== */
    const jumlahHilang = daftarLaporan.filter(function (laporan) {
        return laporan.status === "Hilang";
    }).length;

    const jumlahDitemukan = daftarLaporan.filter(function (laporan) {
        return laporan.status === "Ditemukan";
    }).length;

    const jumlahKlaim = daftarKlaim.length;

    // MENAMPILKAN STATISTIK PADA DASHBOARD
    const totalHilang = document.getElementById("totalHilang");
    const totalDitemukan = document.getElementById("totalDitemukan");
    const totalKlaim = document.getElementById("totalKlaim");
    const totalDikembalikan = document.getElementById("totalDikembalikan");

    if (totalHilang) totalHilang.textContent = jumlahHilang;
    if (totalDitemukan) totalDitemukan.textContent = jumlahDitemukan;
    if (totalKlaim) totalKlaim.textContent = jumlahKlaim;
    if (totalDikembalikan) totalDikembalikan.textContent = 0;

    /* ========================================
        MENAMPILKAN LAPORAN TERBARU
    ======================================== */
    const reportList = document.querySelector(".report-list");

    if (reportList) {
        reportList.innerHTML = "";

        // MENGAMBIL TIGA LAPORAN TERBARU
        const laporanTerbaru = [...daftarLaporan]
            .reverse()
            .slice(0, 3);

        if (laporanTerbaru.length === 0) {
            reportList.innerHTML = `
                <div class="report-item p-3 text-center text-muted">
                    <div class="report-info">
                        <h3>Belum Ada Laporan</h3>
                        <p class="mb-0">Kamu belum membuat laporan barang.</p>
                    </div>
                </div>
            `;
        } else {
            // MENAMPILKAN SETIAP LAPORAN TERBARU DENGAN FOTO
            laporanTerbaru.forEach(function (laporan) {
                let statusClass = "waiting";

                if (laporan.status === "Ditemukan") {
                    statusClass = "verified";
                }

                // Mengambil gambar dari data laporan atau menggunakan placeholder jika kosong
                let fotoBarang = laporan.gambar ? laporan.gambar : "https://via.placeholder.com/80";

                const item = document.createElement("div");
                item.className = "report-item d-flex align-items-center justify-content-between p-3 border-bottom";

                item.innerHTML = `
                    <div class="d-flex align-items-center gap-3">
                        <img src="${fotoBarang}" alt="${laporan.namaBarang}" style="width: 55px; height: 55px; object-fit: cover; border-radius: 8px;">
                        <div class="report-info">
                            <h6 class="mb-1 fw-bold">${laporan.namaBarang}</h6>
                            <small class="text-muted">Dilaporkan pada ${laporan.tanggal}</small>
                        </div>
                    </div>

                    <span class="status ${statusClass}">
                        ${laporan.status}
                    </span>
                `;

                reportList.appendChild(item);
            });
        }
    }

});