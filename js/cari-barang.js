document.addEventListener("DOMContentLoaded", function () {

    /* ========================================
        DATA USER
    ======================================== */

    let dataUser = null;

    try {
        dataUser = JSON.parse(
            localStorage.getItem("dataUser")
        );
    } catch (error) {
        dataUser = null;
    }

    const profileName = document.getElementById("profileName");
    const profileInitial = document.getElementById("profileInitial");

    if (dataUser && dataUser.nama) {

        if (profileName) {
            profileName.textContent = dataUser.nama;
        }

        if (profileInitial) {
            profileInitial.textContent =
                dataUser.nama.charAt(0).toUpperCase();
        }

    }

    /* ========================================
        MENGAMBIL ELEMEN
    ======================================== */

    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const statusFilter = document.getElementById("statusFilter");
    const categoryFilter = document.getElementById("categoryFilter");
    const resetFilter = document.getElementById("resetFilter");
    const barangCards = document.querySelectorAll(".barang-card");
    const noResult = document.getElementById("noResult");
    const resultCount = document.getElementById("resultCount");

    /* ========================================
        DEBOUNCE PENCARIAN
    ======================================== */

    let debounceTimer;
    const debounceDelay = 400;

    /* ========================================
        FUNGSI FILTER BARANG
    ======================================== */

    function filterBarang() {

        const keyword = searchInput.value
            .trim()
            .toLowerCase();

        const status = statusFilter.value;
        const category = categoryFilter.value;

        let jumlah = 0;

        barangCards.forEach(function (card) {

            const nama = (
                card.dataset.name ||
                card.querySelector("h3")?.textContent ||
                ""
            ).toLowerCase();

            const statusBarang = (
                card.dataset.status || ""
            ).toLowerCase();

            const kategoriBarang = (
                card.dataset.category || ""
            ).toLowerCase();

            const cocokNama = nama.includes(keyword);

            const cocokStatus =
                status === "semua" ||
                statusBarang === status;

            const cocokKategori =
                category === "semua" ||
                kategoriBarang === category;

            if (
                cocokNama &&
                cocokStatus &&
                cocokKategori
            ) {

                card.style.display = "";
                jumlah++;

            } else {

                card.style.display = "none";

            }

        });

        /* MENAMPILKAN PESAN HASIL KOSONG */

        if (noResult) {
            noResult.style.display =
                jumlah === 0 ? "block" : "none";
        }

        /* MEMPERBARUI JUMLAH HASIL */

        if (resultCount) {

            if (jumlah === 0) {

                resultCount.textContent =
                    "Tidak ada barang yang sesuai";

            } else {

                resultCount.textContent =
                    "Menampilkan " + jumlah + " barang";

            }

        }

    }

    /* ========================================
        TOMBOL X PADA INPUT
    ======================================== */

    function perbaruiTombolHapus() {

        clearSearch.hidden =
            searchInput.value.length === 0;

    }

    /* ========================================
        DEBOUNCE
    ======================================== */

    function jadwalkanPencarian() {

        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(function () {

            filterBarang();

        }, debounceDelay);

    }

    /* ========================================
        EVENT PENCARIAN
    ======================================== */

    searchInput.addEventListener("input", function () {

        perbaruiTombolHapus();
        jadwalkanPencarian();

    });

    /* ========================================
        EVENT TOMBOL X
    ======================================== */

    clearSearch.addEventListener("click", function () {

        clearTimeout(debounceTimer);
        searchInput.value = "";
        perbaruiTombolHapus();
        filterBarang();
        searchInput.focus();

    });

    /* ========================================
        EVENT FILTER STATUS
    ======================================== */

    statusFilter.addEventListener("change", function () {

        clearTimeout(debounceTimer);
        filterBarang();

    });

    /* ========================================
        EVENT FILTER KATEGORI
    ======================================== */

    categoryFilter.addEventListener("change", function () {

        clearTimeout(debounceTimer);
        filterBarang();

    });

    /* ========================================
        RESET SEMUA FILTER
    ======================================== */

    resetFilter.addEventListener("click", function () {

        clearTimeout(debounceTimer);
        searchInput.value = "";
        statusFilter.value = "semua";
        categoryFilter.value = "semua";
        perbaruiTombolHapus();
        filterBarang();
        searchInput.focus();

    });

    /* ========================================
        FILTER AWAL SAAT HALAMAN DIBUKA
    ======================================== */

    perbaruiTombolHapus();
    filterBarang();

});