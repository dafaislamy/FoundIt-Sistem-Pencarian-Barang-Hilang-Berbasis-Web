/* DATA USER*/
const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}

/* ELEMENT */
const searchInput =
    document.getElementById("searchInput");

const searchButton =
    document.getElementById("searchButton");

const statusFilter =
    document.getElementById("statusFilter");

const categoryFilter =
    document.getElementById("categoryFilter");

const barangCards =
    document.querySelectorAll(".barang-card");

const noResult =
    document.getElementById("noResult");

const resultCount =
    document.getElementById("resultCount");

function filterBarang() {

    const keyword =
        searchInput.value
            .toLowerCase()
            .trim();

    const status =
        statusFilter.value;

    const category =
        categoryFilter.value;

    let jumlah = 0;


    barangCards.forEach(function (card) {

        const nama =
            card.dataset.name.toLowerCase();

        const statusBarang =
            card.dataset.status;

        const kategoriBarang =
            card.dataset.category;


        const cocokNama =
            nama.includes(keyword);

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

            card.style.display = "block";

            jumlah++;

        } else {

            card.style.display = "none";

        }

    });


    if (jumlah === 0) {

        noResult.style.display = "block";

        resultCount.textContent =
            "Tidak ada barang yang sesuai";

    } else {

        noResult.style.display = "none";

        resultCount.textContent =
            "Menampilkan " + jumlah + " barang";

    }

}

/* EVENT */
searchButton.addEventListener(
    "click",
    filterBarang
);

searchInput.addEventListener(
    "keyup",
    filterBarang
);

statusFilter.addEventListener(
    "change",
    filterBarang
);

categoryFilter.addEventListener(
    "change",
    filterBarang
);