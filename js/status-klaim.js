const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// PROFIL USER
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}

// AMBIL DATA KLAIM
const daftarKlaim =
    JSON.parse(
        localStorage.getItem("daftarKlaim")
    ) || [];


const klaimList =
    document.getElementById("klaimList");

const emptyClaim =
    document.getElementById("emptyClaim");

const jumlahKlaim =
    document.getElementById("jumlahKlaim");

// JUMLAH KLAIM
jumlahKlaim.textContent =
    daftarKlaim.length +
    " pengajuan klaim";

// CEK DATA
if (daftarKlaim.length === 0) {

    emptyClaim.style.display = "block";

} else {

    emptyClaim.style.display = "none";

}


// =========================
// TAMPILKAN DATA
// =========================

daftarKlaim
    .slice()
    .reverse()
    .forEach(function (klaim) {

        const card =
            document.createElement("div");

        card.className =
            "claim-card";


        card.innerHTML = `

            <div class="claim-top">

                <div class="claim-title">

                    <h3>
                        ${klaim.namaBarang}
                    </h3>

                    <p>
                        Pengajuan klaim barang
                    </p>

                </div>


                <span class="claim-badge">

                    ${klaim.status}

                </span>

            </div>


            <div class="claim-detail">

                <div class="claim-detail-item">

                    <span>
                        Kategori
                    </span>

                    <strong>
                        ${klaim.kategori}
                    </strong>

                </div>


                <div class="claim-detail-item">

                    <span>
                        Lokasi Kehilangan
                    </span>

                    <strong>
                        ${klaim.lokasiHilang}
                    </strong>

                </div>


                <div class="claim-detail-item">

                    <span>
                        Ciri Barang
                    </span>

                    <strong>
                        ${klaim.ciriBarang}
                    </strong>

                </div>


                <div class="claim-detail-item">

                    <span>
                        Keterangan
                    </span>

                    <strong>
                        ${klaim.keterangan}
                    </strong>

                </div>

            </div>

        `;


        klaimList.appendChild(card);

    });