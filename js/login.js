function pilihRole(role) {

    const roleInput = document.getElementById("role");
    const loginButton = document.querySelector(".btn-login");

    const roleCards = document.querySelectorAll(".role-card");

    roleInput.value = role;

    roleCards.forEach(card => {
        card.classList.remove("active");
    });

    if (role === "user") {

        roleCards[0].classList.add("active");

        loginButton.innerText = "Login sebagai User";

    } else {

        roleCards[1].classList.add("active");

        loginButton.innerText = "Login sebagai Admin";

    }
}


document
    .getElementById("loginForm")
    .addEventListener("submit", function (event) {

        event.preventDefault();

        const role = document.getElementById("role").value;

        if (role === "user") {

            window.location.href = "user/register.html";

        } else {

            window.location.href = "admin/dashboard.html";

        }

    });