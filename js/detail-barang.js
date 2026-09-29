/* DATA USER */
const dataUser = JSON.parse(
    localStorage.getItem("dataUser")
);

// MENAMPILKAN NAMA DAN INISIAL PENGGUNA
if (dataUser) {

    document.getElementById("profileName").textContent =
        dataUser.nama;

    document.getElementById("profileInitial").textContent =
        dataUser.nama.charAt(0).toUpperCase();

}