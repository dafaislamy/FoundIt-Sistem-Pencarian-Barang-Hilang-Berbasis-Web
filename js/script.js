document.addEventListener("DOMContentLoaded", function() {

    const searchInput = document.querySelector(".filter input[type='text']");
    const searchButton = document.querySelector(".filter button");
    const cards = document.querySelectorAll(".grid-barang .card");

    function filterBarang() {
        const keyword = searchInput.value.toLowerCase();

        cards.forEach(card => {
            const namaBarang = card.querySelector("h3").innerText.toLowerCase();
            
            // Jika nama barang cocok, tampilkan; jika tidak, sembunyikan
            if (namaBarang.includes(keyword)) {
                card.style.display = "block";
            } else {
                card.style.display = "none";
            }
        });
    }

    // Pencarian saat tombol 'Cari' diklik
    searchButton.addEventListener("click", function(e) {
        e.preventDefault();
        filterBarang();
    });

});