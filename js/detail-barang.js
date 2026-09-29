// DATA BARANG
const daftarBarang = {
    flashdisk: {
        nama: "Flashdisk SanDisk 64GB",
        status: "Hilang",
        kategori: "Elektronik",
        lokasi: "Sekitar Fakultas Teknik",
        tanggal: "18 September 2026",
        deskripsi: "Flashdisk SanDisk berkapasitas 64GB hilang di sekitar Fakultas Teknik. Pemilik dapat mengajukan laporan jika barang ditemukan.",
        gambar: src="https://images.unsplash.com/photo-1587145820098-23e484e69816?auto=format&fit=crop&w=600&q=80"
    },

    kunci: {
        nama: "Kunci Motor Yamaha",
        status: "Ditemukan",
        kategori: "Aksesoris",
        lokasi: "Area Parkir Kampus",
        tanggal: "18 September 2026",
        deskripsi: "Kunci motor ditemukan di area parkir kampus. Pemilik dapat mengajukan klaim dengan memberikan informasi yang sesuai.",
        gambar: "https://motosneno.cdn.magazord.com.br/img/2025/09/produto/48777/5sl8251108.jpg"
    },

    handphone: {
        nama: "Handphone",
        status: "Hilang",
        kategori: "Elektronik",
        lokasi: "Area Kantin Kampus",
        tanggal: "18 September 2026",
        deskripsi: "Handphone hilang di area kantin kampus. Pemilik dapat mengajukan klaim jika barang ditemukan.",
        gambar: "https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=600"
    },

    dompet: {
        nama: "Dompet Kulit",
        status: "Ditemukan",
        kategori: "Aksesoris",
        lokasi: "Sekitar Perpustakaan",
        tanggal: "18 September 2026",
        deskripsi: "Dompet hitam ditemukan di sekitar perpustakaan. Pemilik dapat mengajukan klaim dengan memberikan informasi yang sesuai.",
        gambar: "https://images.unsplash.com/photo-1627123424574-724758594e93?w=600"
    }
};


// MENGAMBIL ID BARANG DARI URL
const params = new URLSearchParams(window.location.search);
const idBarang = params.get("id");


// MENGAMBIL DATA BARANG
const barang = daftarBarang[idBarang];


// MENAMPILKAN DETAIL BARANG
if (barang) {

    // MENAMPILKAN GAMBAR
    const detailImage = document.getElementById("detailImage");

    detailImage.src = barang.gambar;
    detailImage.alt = barang.nama;

    // MENAMPILKAN NAMA DAN DESKRIPSI
    document.getElementById("detailName").textContent =
        barang.nama;

    document.getElementById("detailDescription").textContent =
        barang.deskripsi;

    // MENAMPILKAN INFORMASI BARANG
    document.getElementById("detailCategory").textContent =
        barang.kategori;

    document.getElementById("detailLocation").textContent =
        barang.lokasi;

    document.getElementById("detailDate").textContent =
        barang.tanggal;

    // MENAMPILKAN STATUS
    const detailStatus = document.getElementById("detailStatus");

    detailStatus.textContent = barang.status;

    const detailStatusText =
        document.getElementById("detailStatusText");

    detailStatusText.textContent = barang.status;

    // MENGATUR WARNA STATUS
    if (barang.status === "Ditemukan") {

        detailStatus.classList.add("ditemukan");
        detailStatusText.classList.add("text-found");

    } else {

        detailStatus.classList.add("hilang");
        detailStatusText.classList.add("text-lost");

    }

    // MENYESUAIKAN TOMBOL KLAIM
    const claimButton = document.getElementById("claimButton");

    if (barang.status === "Hilang") {
        claimButton.style.display = "none";
    }

} else {

    // JIKA ID BARANG TIDAK VALID
    document.getElementById("detailContainer").innerHTML = `
        <div class="no-result">
            <h3>Barang tidak ditemukan</h3>
            <p>Data barang yang kamu cari tidak tersedia.</p>
            <br>
            <a href="cari-barang.html" class="btn-primary">
                Kembali ke Cari Barang
            </a>
        </div>
    `;

}


// DATA USER
const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);


// MENAMPILKAN NAMA DAN INISIAL PENGGUNA
if (dataUser && dataUser.nama) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}