document.addEventListener("DOMContentLoaded", function() {

    const searchInput = document.querySelector(".filter input[type='text']");
    const selectStatus = document.querySelectorAll(".filter select")[0];    
    const selectKategori = document.querySelectorAll(".filter select")[1];  
    const searchButton = document.querySelector(".filter button");
    const cols = document.querySelectorAll(".grid-barang .col"); 

    function filterBarang() {
        const keyword = searchInput.value.toLowerCase();
        const statusValue = selectStatus.value;
        const kategoriValue = selectKategori.value;

        cols.forEach(col => {
            const card = col.querySelector(".card");
            const namaBarang = card.querySelector("h3").innerText.toLowerCase();
            const statusBarang = card.querySelector(".badge").innerText.trim();
            const kategoriBarang = card.querySelector(".kategori").innerText.trim();

            // Cek kondisi pencarian teks
            const matchKeyword = namaBarang.includes(keyword);

            // cocok jika pilih "Semua Status" atau teks badge sama persis
            const matchStatus = (statusValue === "Semua Status" || statusBarang === statusValue);

            // cocok jika pilih "Semua Kategori" atau teks kategori sama persis
            const matchKategori = (kategoriValue === "Semua Kategori" || kategoriBarang === kategoriValue);

            // jika ketiganya cocok
            if (matchKeyword && matchStatus && matchKategori) {
                col.style.display = ""; // Tampilkan kembali
            } else {
                col.style.display = "none"; // Sembunyikan
            }
        });
    }

    // Jalankan filter saat tombol 'Cari' diklik
    searchButton.addEventListener("click", function(e) {
        e.preventDefault();
        filterBarang();
    });

});