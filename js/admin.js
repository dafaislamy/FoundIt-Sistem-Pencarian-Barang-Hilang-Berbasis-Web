document.addEventListener("DOMContentLoaded", function () {

    // Menampilkan pesan notifikasi pop-up singkat
    function showNotification(message, isSuccess = true) {
        const existingToast = document.querySelector(".admin-toast");
        if (existingToast) existingToast.remove();

        const toast = document.createElement("div");
        toast.className = "admin-toast";
        toast.style.borderLeft = isSuccess ? "4px solid #27ae60" : "4px solid #e74c3c";
        toast.innerHTML = `<span>${isSuccess ? "✅" : "⚠️"}</span> <span>${message}</span>`;
        document.body.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = "0";
            toast.style.transition = "opacity 0.3s ease";
            setTimeout(() => toast.remove(), 300);
        }, 2500);
    }

    // Memuat profil admin dan menghitung ringkasan statistik dari penyimpanan lokal
    const dataUser = JSON.parse(localStorage.getItem("dataUser"));
    const daftarLaporan = JSON.parse(localStorage.getItem("daftarLaporan")) || [];
    const daftarKlaim = JSON.parse(localStorage.getItem("daftarKlaim")) || [];

    if (dataUser) {
        const profileName = document.getElementById("profileName");
        const profileInitial = document.getElementById("profileInitial");
        const welcomeName = document.getElementById("welcomeName");

        if (profileName) profileName.textContent = dataUser.nama || "Administrator";
        if (profileInitial) profileInitial.textContent = (dataUser.nama || "A").charAt(0).toUpperCase();
        if (welcomeName) welcomeName.textContent = "Halo, " + dataUser.nama + " 👋";
    }

    const totalHilangElem = document.getElementById("totalHilang");
    const totalDitemukanElem = document.getElementById("totalDitemukan");
    const totalKlaimElem = document.getElementById("totalKlaim");

    if (totalHilangElem && daftarLaporan.length > 0) {
        const jumlahHilang = daftarLaporan.filter(l => l.status === "Hilang").length;
        totalHilangElem.textContent = jumlahHilang;
    }
    if (totalDitemukanElem && daftarLaporan.length > 0) {
        const jumlahDitemukan = daftarLaporan.filter(l => l.status === "Ditemukan").length;
        totalDitemukanElem.textContent = jumlahDitemukan;
    }
    if (totalKlaimElem && daftarKlaim.length > 0) {
        totalKlaimElem.textContent = daftarKlaim.length;
    }

    // Mengelola pencarian teks dan filter dropdown otomatis pada tabel
    const searchInput = document.querySelector('main input[type="text"][placeholder*="Cari"]');
    const clearSearch = document.querySelector('main .clear-search');
    const filterSelects = document.querySelectorAll('main section select');
    const resetFilterBtn = document.getElementById('resetFilter');
    const pesanKosong = document.getElementById('pesanKosong') || 
                        document.getElementById('pesanKosongUser') || 
                        document.getElementById('pesanKosongStatus');
    
    function jalankanFilterUniversal() {
        const table = document.querySelector('main table tbody');
        if (!table) return;

        const keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
        const rows = table.querySelectorAll('tr');

        const activeFilters = [];
        filterSelects.forEach(sel => {
            const val = sel.value.trim().toLowerCase();
            if (val !== '' && !val.includes('semua')) {
                activeFilters.push(val);
            }
        });

        let totalCocok = 0;

        rows.forEach(row => {
            const rowText = row.innerText.toLowerCase();

            const matchKeyword = (keyword === '') || rowText.includes(keyword);

            const matchDropdowns = activeFilters.every(filterVal => {
                return rowText.includes(filterVal);
            });

            if (matchKeyword && matchDropdowns) {
                row.style.display = '';
                totalCocok++;
            } else {
                row.style.display = 'none';
            }
        });

        if (pesanKosong) {
            pesanKosong.style.display = (totalCocok === 0) ? 'block' : 'none';
        }
    }

    let debounceTimer;
    function debounceFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(jalankanFilterUniversal, 400);
    }

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            if (clearSearch) clearSearch.hidden = (this.value.length === 0);
            debounceFilter();
        });
    }

    if (clearSearch) {
        clearSearch.addEventListener('click', function () {
            clearTimeout(debounceTimer);
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            this.hidden = true;
            jalankanFilterUniversal();
        });
    }

    filterSelects.forEach(select => {
        select.addEventListener('change', function () {
            clearTimeout(debounceTimer);
            jalankanFilterUniversal();
        });
    });

    if (resetFilterBtn) {
        resetFilterBtn.addEventListener('click', function () {
            clearTimeout(debounceTimer);
            if (searchInput) searchInput.value = '';
            if (clearSearch) clearSearch.hidden = true;
            filterSelects.forEach(sel => sel.selectedIndex = 0);
            jalankanFilterUniversal();
        });
    }

    // Mengatur pengisian data dan pembaruan status barang lewat modal
    const modalStatus = document.getElementById("modalUpdateStatus") || document.getElementById("modalStatus");
    let targetRow = null;

    if (modalStatus) {
        modalStatus.addEventListener("show.bs.modal", function (event) {
            const button = event.relatedTarget;
            if (!button) return;

            targetRow = button.closest("tr");
            const nama = button.getAttribute("data-nama");
            const id = button.getAttribute("data-id");
            const status = button.getAttribute("data-status");

            const targetInput = document.getElementById("targetBarang");
            if (targetInput && nama) targetInput.value = `${nama} (${id || ""})`;

            const select = modalStatus.querySelector("select");
            if (select && status) select.value = status;
        });

        const btnSimpanStatus = modalStatus.querySelector(".modal-footer .btn-primary");
        if (btnSimpanStatus) {
            btnSimpanStatus.addEventListener("click", function () {
                const select = modalStatus.querySelector("select");
                const selectedText = select ? select.options[select.selectedIndex].text : "Diperbarui";

                if (targetRow) {
                    const badge = targetRow.querySelector(".badge");
                    if (badge) {
                        badge.textContent = selectedText.split("(")[0].trim();
                        badge.className = "badge bg-primary";
                    }
                }

                showNotification("Status barang berhasil diperbarui!");
                const modalInstance = bootstrap.Modal.getInstance(modalStatus);
                if (modalInstance) modalInstance.hide();
            });
        }
    }

    // Menangani aksi persetujuan atau penolakan laporan barang
    document.querySelectorAll("button").forEach(btn => {
        const text = btn.innerText.trim().toLowerCase();

        if (text.includes("setujui & publikasikan")) {
            btn.addEventListener("click", function () {
                const card = this.closest(".card");
                if (confirm("Setujui laporan ini agar dapat dilihat oleh mahasiswa?")) {
                    if (card) {
                        card.style.opacity = "0.6";
                        card.style.pointerEvents = "none";
                    }
                    showNotification("Laporan berhasil disetujui & dipublikasikan!");
                }
            });
        }

        if (text === "tolak laporan") {
            btn.addEventListener("click", function () {
                const card = this.closest(".card");
                const alasan = prompt("Masukkan alasan penolakan laporan:");
                if (alasan) {
                    if (card) card.remove();
                    showNotification("Laporan telah ditolak.", false);
                }
            });
        }
    });

    // Menangani persetujuan atau penolakan pengajuan klaim barang
    document.querySelectorAll("button").forEach(btn => {
        const text = btn.innerText.trim().toLowerCase();

        if (text.includes("setujui & hubungi pemilik")) {
            btn.addEventListener("click", function () {
                const card = this.closest(".card");
                if (confirm("Klaim valid? Tandai klaim disetujui untuk serah terima.")) {
                    const badge = card?.querySelector(".badge.bg-warning");
                    if (badge) {
                        badge.className = "badge bg-success";
                        badge.innerText = "Klaim Disetujui";
                    }
                    this.disabled = true;
                    this.innerText = "Telah Disetujui ✓";
                    showNotification("Klaim disetujui!");
                }
            });
        }

        if (text === "tolak klaim") {
            btn.addEventListener("click", function () {
                const card = this.closest(".card");
                if (confirm("Tolak pengajuan klaim ini?")) {
                    if (card) card.remove();
                    showNotification("Pengajuan klaim ditolak.", false);
                }
            });
        }
    });

    // Mengatur perubahan status aktif serta penambahan pengguna baru
    document.querySelectorAll(".btn-outline-warning, .btn-outline-success").forEach(btn => {
        btn.addEventListener("click", function () {
            const row = this.closest("tr");
            const badge = row?.querySelector(".badge");
            const isWarning = this.classList.contains("btn-outline-warning");

            if (isWarning) {
                if (confirm("Nonaktifkan akun pengguna ini?")) {
                    if (badge) {
                        badge.className = "badge bg-secondary";
                        badge.innerText = "Nonaktif";
                    }
                    this.className = "btn btn-sm btn-outline-success";
                    this.innerText = "Aktifkan";
                    showNotification("Akun pengguna dinonaktifkan.");
                }
            } else {
                if (confirm("Aktifkan kembali akun ini?")) {
                    if (badge) {
                        badge.className = "badge bg-success";
                        badge.innerText = "Aktif";
                    }
                    this.className = "btn btn-sm btn-outline-warning";
                    this.innerText = "Nonaktifkan";
                    showNotification("Akun pengguna berhasil diaktifkan!");
                }
            }
        });
    });

    const modalTambahUser = document.getElementById("modalTambahUser");
    if (modalTambahUser) {
        const btnSimpan = modalTambahUser.querySelector(".btn-primary");
        btnSimpan?.addEventListener("click", function () {
            const inputs = modalTambahUser.querySelectorAll("input");
            let valid = true;
            inputs.forEach(i => { if (!i.value.trim()) valid = false; });

            if (!valid) {
                alert("Harap lengkapi semua kolom!");
                return;
            }

            const tbody = document.querySelector("table tbody");
            if (tbody) {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td><strong>${inputs[0].value}</strong><div class="text-muted small">NIM: ${inputs[1].value}</div></td>
                    <td>${inputs[2].value}<br><small class="text-muted">Mahasiswa (2026)</small></td>
                    <td>${inputs[3].value}<br><small class="text-muted">-</small></td>
                    <td><span class="badge bg-success">Aktif</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-warning">Nonaktifkan</button>
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </td>
                `;
                tbody.prepend(tr);
            }

            inputs.forEach(i => i.value = "");
            showNotification("Pengguna baru berhasil ditambahkan!");
            const modalInstance = bootstrap.Modal.getInstance(modalTambahUser);
            if (modalInstance) modalInstance.hide();
        });
    }

    // Mengelola penambahan dan pengeditan nama kategori barang
    const formKategori = document.querySelector("form");
    if (formKategori && window.location.pathname.includes("kategori")) {
        formKategori.addEventListener("submit", function (e) {
            e.preventDefault();
            const input = this.querySelector("input");
            const textarea = this.querySelector("textarea");

            if (!input?.value.trim()) return;

            const tbody = document.querySelector("table tbody");
            if (tbody) {
                const tr = document.createElement("tr");
                tr.innerHTML = `
                    <td>
                        <strong>${input.value}</strong>
                        <div class="text-muted small">${textarea.value || "-"}</div>
                    </td>
                    <td><span class="badge bg-light text-dark border">0 Barang</span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary">Edit</button>
                        <button class="btn btn-sm btn-outline-danger">Hapus</button>
                    </td>
                `;
                tbody.prepend(tr);
            }

            input.value = "";
            textarea.value = "";
            showNotification("Kategori baru berhasil disimpan!");
        });
    }

    document.addEventListener("click", function (e) {
        if (e.target.matches(".btn-outline-primary") && e.target.innerText.trim().toLowerCase() === "edit") {
            const tr = e.target.closest("tr");
            const strong = tr?.querySelector("strong");
            if (strong) {
                const baru = prompt("Ubah nama kategori:", strong.innerText);
                if (baru && baru.trim()) {
                    strong.innerText = baru.trim();
                    showNotification("Nama kategori diperbarui!");
                }
            }
        }
    });

    // Menghapus baris data dari tabel setelah konfirmasi admin
    document.addEventListener("click", function (e) {
        if (e.target.matches(".btn-outline-danger") && e.target.innerText.trim().toLowerCase() === "hapus") {
            const tr = e.target.closest("tr");
            if (confirm("Apakah Anda yakin ingin menghapus data ini secara permanen?")) {
                if (tr) tr.remove();
                showNotification("Data berhasil dihapus.", false);
            }
        }
    });

});