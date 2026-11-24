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
    const pesanKosong = document.getElementById("pesanKosong");

    /* ========================================
       FUNGSI PENCARIAN DAN FILTER BARANG
    ======================================== */
    function filterBarang() {
        const currentSearchInput = document.getElementById("searchInput");
        const currentSelectStatus = document.getElementById("selectStatus");
        const currentSelectKategori = document.getElementById("selectKategori");
        const currentPesanKosong = document.getElementById("pesanKosong");
        const cols = document.querySelectorAll(".grid-barang .col");

        if (!currentSearchInput || !currentSelectStatus || !currentSelectKategori) return;

        const keyword = currentSearchInput.value.trim().toLowerCase();
        const statusRaw = currentSelectStatus.value.trim().toLowerCase();
        const kategoriRaw = currentSelectKategori.value.trim().toLowerCase();

        // Ambil juga teks label dari opsi kategori yang aktif (misal "Buku", "Aksesoris")
        const selectedCatOpt = currentSelectKategori.options[currentSelectKategori.selectedIndex];
        const kategoriText = selectedCatOpt ? selectedCatOpt.text.trim().toLowerCase() : "";

        let jumlahDitemukan = 0;

        cols.forEach(function (col) {
            const card = col.querySelector(".card");
            if (!card) return;

            // Nama barang dari data-name atau heading kartu
            const namaBarang = (
                col.getAttribute("data-name") ||
                col.dataset.name ||
                card.querySelector("h3")?.innerText ||
                ""
            ).toLowerCase();

            // Status barang dari data-status atau badge
            const statusBarang = (
                col.getAttribute("data-status") ||
                col.dataset.status ||
                card.querySelector(".badge-status, .badge")?.innerText ||
                ""
            ).toLowerCase().trim();

            // Kategori barang (ID dan Nama)
            const kategoriAttr = (
                col.getAttribute("data-kategori") ||
                col.dataset.kategori ||
                ""
            ).toLowerCase().trim();

            const kategoriId = (
                col.getAttribute("data-kategori-id") ||
                col.dataset.kategoriId ||
                ""
            ).toLowerCase().trim();

            const kategoriNama = (
                col.getAttribute("data-kategori-name") ||
                col.dataset.kategoriName ||
                card.querySelector(".kategori")?.innerText ||
                ""
            ).toLowerCase().trim();

            /* 1. KECOCOKAN KATA KUNCI (NAMA BARANG / TEKS KARTU) */
            const cardAllText = (card.innerText || "").toLowerCase();
            const matchKeyword = (keyword === "" || namaBarang.includes(keyword) || cardAllText.includes(keyword));

            /* 2. KECOCOKAN STATUS (HILANG / DITEMUKAN - CASE-INSENSITIVE) */
            let matchStatus = true;
            if (statusRaw !== "" && statusRaw !== "semua status") {
                if (statusRaw === "hilang" || statusRaw === "lost") {
                    matchStatus = (statusBarang.includes("hilang") || statusBarang.includes("lost"));
                } else if (statusRaw === "ditemukan" || statusRaw === "found") {
                    matchStatus = (statusBarang.includes("ditemukan") || statusBarang.includes("found"));
                } else {
                    matchStatus = (statusBarang === statusRaw);
                }
            }

            /* 3. KECOCOKAN KATEGORI (ID MAUPUN NAMA KATEGORI) */
            let matchKategori = true;
            if (kategoriRaw !== "" && kategoriRaw !== "semua kategori") {
                matchKategori = (
                    kategoriId === kategoriRaw ||
                    kategoriNama === kategoriRaw ||
                    kategoriAttr === kategoriRaw ||
                    kategoriNama === kategoriText ||
                    kategoriAttr === kategoriText ||
                    (kategoriText !== "" && (kategoriNama.includes(kategoriText) || kategoriAttr.includes(kategoriText)))
                );
            }

            /* TAMPILKAN ATAU SEMBUNYIKAN KARTU (DENGAN KELAS & DISPLAY !IMPORTANT) */
            if (matchKeyword && matchStatus && matchKategori) {
                col.classList.remove("filter-hidden", "d-none");
                col.style.setProperty("display", "flex", "important");
                jumlahDitemukan++;
            } else {
                col.classList.add("filter-hidden", "d-none");
                col.style.setProperty("display", "none", "important");
            }
        });

        /* TAMPILKAN PESAN KOSONG JIKA TIDAK ADA BARANG YANG COCOK */
        if (currentPesanKosong) {
            if (jumlahDitemukan === 0) {
                currentPesanKosong.classList.remove("d-none");
                currentPesanKosong.style.setProperty("display", "block", "important");
            } else {
                currentPesanKosong.classList.add("d-none");
                currentPesanKosong.style.setProperty("display", "none", "important");
            }
        }
    }

    // Ekspor fungsi ke window agar dapat dipanggil dari inline event handler
    window.filterBarang = filterBarang;

    /* ========================================
       DEBOUNCE UNTUK SEARCH REAL-TIME
    ======================================== */
    let debounceTimer;

    function debounceFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function () {
            filterBarang();
        }, 150);
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
                selectStatus.value = "";
            }
            if (selectKategori) {
                selectKategori.selectedIndex = 0;
                selectKategori.value = "";
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
