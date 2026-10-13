document.addEventListener("DOMContentLoaded", function() {

    /* MENGAMBIL ELEMEN FILTER */
    const resetFilter = document.getElementById("resetFilter");
    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const selectStatus = document.getElementById("selectStatus");
    const selectKategori = document.getElementById("selectKategori");
    const cols = document.querySelectorAll(".grid-barang .col");
    const pesanKosong = document.getElementById("pesanKosong");

    /* FUNGSI PENCARIAN DAN FILTER BARANG */
    function filterBarang() {
        const keyword = searchInput.value.trim().toLowerCase();
        const statusValue = selectStatus.value;
        const kategoriValue = selectKategori.value;

        let jumlahDitemukan = 0;

        cols.forEach(function(col) {
            const card = col.querySelector(".card");

            const namaBarang = card.querySelector("h3")
                .innerText.toLowerCase();

            const statusBarang = card.querySelector(".badge")
                .innerText.trim();

            const kategoriBarang = card.querySelector(".kategori")
                .innerText.trim();

            /* MEMERIKSA KESESUAIAN NAMA, STATUS, DAN KATEGORI */
            const matchKeyword = namaBarang.includes(keyword);

            const matchStatus =
                statusValue === "Semua Status" ||
                statusBarang === statusValue;

            const matchKategori =
                kategoriValue === "Semua Kategori" ||
                kategoriBarang === kategoriValue;

            /* MENAMPILKAN BARANG YANG SESUAI */
            if (matchKeyword && matchStatus && matchKategori) {
                col.style.display = "";
                jumlahDitemukan++;
            } else {
                col.style.display = "none";
            }
        });

        /* MENAMPILKAN PESAN JIKA BARANG TIDAK DITEMUKAN */
        pesanKosong.style.display =
            jumlahDitemukan === 0 ? "block" : "none";
    }

    /* DEBOUNCE 400 MS */
    let debounceTimer;

    function debounceFilter() {
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(function() {
            filterBarang();
        }, 400);
    }

    /* LIVE SEARCH SAAT PENGGUNA MENGETIK */
    searchInput.addEventListener("input", function() {

        /* TAMPILKAN ATAU SEMBUNYIKAN TOMBOL SILANG */
        clearSearch.hidden = searchInput.value.length === 0;

        /* FILTER BERJALAN SETELAH JEDA 400 MS */
        debounceFilter();
    });

    /* TOMBOL SILANG (X) PENCARIAN */
    clearSearch.addEventListener("click", function() {
        clearTimeout(debounceTimer);

        searchInput.value = "";
        clearSearch.hidden = true;

        filterBarang();
        searchInput.focus();
    });

    /* TOMBOL RESET FILTER UTAMA */
    resetFilter.addEventListener("click", function () {
        clearTimeout(debounceTimer);

        searchInput.value = "";
        selectStatus.selectedIndex = 0;
        selectKategori.selectedIndex = 0;
        clearSearch.hidden = true;

        filterBarang();
    });

    /* FILTER STATUS BARANG */
    selectStatus.addEventListener("change", function() {
        clearTimeout(debounceTimer);
        filterBarang();
    });

    /* FILTER KATEGORI BARANG */
    selectKategori.addEventListener("change", function() {
        clearTimeout(debounceTimer);
        filterBarang();
    });

    /* FILTER AWAL SAAT HALAMAN DIBUKA */
    filterBarang();

});