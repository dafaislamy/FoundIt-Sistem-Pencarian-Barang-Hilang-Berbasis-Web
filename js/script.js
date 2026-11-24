document.addEventListener("DOMContentLoaded", function () {

    /* ========================================
       MENGAMBIL ELEMEN FILTER
    ======================================== */
    const filterForm = document.getElementById("filterForm");
    const resetFilter = document.getElementById("resetFilter");
    const searchInput = document.getElementById("searchInput");
    const clearSearch = document.getElementById("clearSearch");
    const selectStatus = document.getElementById("selectStatus");
    const selectKategori = document.getElementById("selectKategori");
    const cols = document.querySelectorAll(".grid-barang .col");
    const pesanKosong = document.getElementById("pesanKosong");

    /* ========================================
       FUNGSI PENCARIAN DAN FILTER BARANG
    ======================================== */
    function filterBarang() {
        if (!searchInput || !selectStatus || !selectKategori) return;

        const keyword = searchInput.value.trim().toLowerCase();
        const statusRaw = selectStatus.value.trim().toLowerCase();
        const kategoriRaw = selectKategori.value.trim().toLowerCase();

        let jumlahDitemukan = 0;

        cols.forEach(function (col) {
            const card = col.querySelector(".card");
            if (!card) return;

            const namaBarang = (
                col.dataset.name ||
                card.querySelector("h3")?.innerText ||
                ""
            ).toLowerCase();

            const statusBarang = (
                col.dataset.status ||
                card.querySelector(".badge-status, .badge")?.innerText ||
                ""
            ).toLowerCase();

            const kategoriId = (col.dataset.kategoriId || "").toLowerCase();
            const kategoriNama = (
                col.dataset.kategoriName ||
                card.querySelector(".kategori")?.innerText ||
                ""
            ).toLowerCase();

            /* 1. KECOCOKAN KATA KUNCI */
            const matchKeyword = (keyword === "" || namaBarang.includes(keyword));

            /* 2. KECOCOKAN STATUS */
            let matchStatus = true;
            if (statusRaw !== "" && statusRaw !== "semua status") {
                if (statusRaw === "hilang" || statusRaw === "lost") {
                    matchStatus = (statusBarang === "hilang" || statusBarang === "lost");
                } else if (statusRaw === "ditemukan" || statusRaw === "found") {
                    matchStatus = (statusBarang === "ditemukan" || statusBarang === "found");
                } else {
                    matchStatus = (statusBarang === statusRaw);
                }
            }

            /* 3. KECOCOKAN KATEGORI */
            let matchKategori = true;
            if (kategoriRaw !== "" && kategoriRaw !== "semua kategori") {
                matchKategori = (
                    kategoriId === kategoriRaw ||
                    kategoriNama === kategoriRaw
                );
            }

            /* TAMPILKAN / SEMBUNYIKAN KARTU */
            if (matchKeyword && matchStatus && matchKategori) {
                col.style.display = "";
                jumlahDitemukan++;
            } else {
                col.style.display = "none";
            }
        });

        /* TAMPILKAN EMPTY STATE BILA HASIL 0 */
        if (pesanKosong) {
            pesanKosong.style.display = (jumlahDitemukan === 0) ? "block" : "none";
        }
    }

    /* ========================================
       DEBOUNCE UNTUK SEARCH REAL-TIME
    ======================================== */
    let debounceTimer;

    function debounceFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            filterBarang();
        }, 250);
    }

    /* ========================================
       EVENT LISTENERS
    ======================================== */
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            if (clearSearch) {
                clearSearch.hidden = searchInput.value.length === 0;
            }
            debounceFilter();
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener("click", function () {
            clearTimeout(debounceTimer);
            if (searchInput) {
                searchInput.value = "";
                searchInput.focus();
            }
            clearSearch.hidden = true;
            filterBarang();
        });
    }

    if (selectStatus) {
        selectStatus.addEventListener("change", function () {
            clearTimeout(debounceTimer);
            filterBarang();
        });
    }

    if (selectKategori) {
        selectKategori.addEventListener("change", function () {
            clearTimeout(debounceTimer);
            filterBarang();
        });
    }

    if (resetFilter) {
        resetFilter.addEventListener("click", function () {
            clearTimeout(debounceTimer);

            if (searchInput) {
                searchInput.value = "";
            }
            if (clearSearch) {
                clearSearch.hidden = true;
            }
            if (selectStatus) {
                selectStatus.selectedIndex = 0;
            }
            if (selectKategori) {
                selectKategori.selectedIndex = 0;
            }

            // Bersihkan URL parameter tanpa reload halaman
            if (window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname + "#barang";
                window.history.replaceState(null, "", cleanUrl);
            }

            filterBarang();

            if (searchInput) {
                searchInput.focus();
            }
        });
    }

    if (filterForm) {
        filterForm.addEventListener("submit", function (e) {
            e.preventDefault();
            clearTimeout(debounceTimer);
            filterBarang();
        });
    }

    // Jalankan filter saat halaman siap
    filterBarang();
});
