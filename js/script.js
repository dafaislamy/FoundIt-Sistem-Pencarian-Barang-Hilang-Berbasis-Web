document.addEventListener("DOMContentLoaded", function() {

    /* MENGAMBIL ELEMEN FILTER */
    const searchInput = document.querySelector(".filter input[type='text']");
    const selectStatus = document.querySelectorAll(".filter select")[0];    
    const selectKategori = document.querySelectorAll(".filter select")[1];  
    const searchButton = document.querySelector(".filter button");
    const cols = document.querySelectorAll(".grid-barang .col"); 

    // FUNGSI PENCARIAN DAN FILTER BARANG
    function filterBarang() {
        const keyword = searchInput.value.toLowerCase();
        const statusValue = selectStatus.value;
        const kategoriValue = selectKategori.value;

        cols.forEach(col => {
            const card = col.querySelector(".card");
            const namaBarang = card.querySelector("h3").innerText.toLowerCase();
            const statusBarang = card.querySelector(".badge").innerText.trim();
            const kategoriBarang = card.querySelector(".kategori").innerText.trim();

            // MEMERIKSA KESESUAIAN NAMA, STATUS, DAN KATEGORI
            const matchKeyword = namaBarang.includes(keyword);

            const matchStatus = (statusValue === "Semua Status" || statusBarang === statusValue);

            const matchKategori = (kategoriValue === "Semua Kategori" || kategoriBarang === kategoriValue);

            // MENAMPILKAN BARANG YANG SESUAI DENGAN FILTER
            if (matchKeyword && matchStatus && matchKategori) {
                col.style.display = "";
            } else {
                col.style.display = "none";
            }
        });
    }

    // MENJALANKAN FILTER SAAT TOMBOL CARI DIKLIK
    searchButton.addEventListener("click", function(e) {
        e.preventDefault();
        filterBarang();
    });

});