const btnSchemeToggle = document.querySelector(".scheme-toggle");
const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");
const currentTheme = localStorage.getItem("theme");
if (currentTheme === "dark") document.body.classList.toggle("dark-theme");
else if (currentTheme === "light") document.body.classList.toggle("light-theme");

const svgCard = document.querySelector("#card");
const svgCafe = document.querySelector("#cafe");

btnSchemeToggle.addEventListener("click", function () {
    let theme;
    if (prefersDarkScheme.matches) {
        document.body.classList.toggle("light-theme");
        theme = document.body.classList.contains("light-theme")
            ? "light"
            : "dark";
    } else {
        document.body.classList.toggle("dark-theme");
        theme = document.body.classList.contains("dark-theme")
            ? "dark"
            : "light";
    }
    localStorage.setItem("theme", theme);
});

function apiFetch(route, method, pErrorElement, success, payload = null, token = null) {
    pErrorElement.innerHTML = "";
    let headers = {
        "Accept": "application/json",
        "Content-Type": "application/json"
    };
    if (token !== null) headers['token'] = token;
    fetch(route, {
        headers: headers,
        method: method,
        body: payload === null ? null : JSON.stringify(payload),
        cache: "no-store"
    })
        .then(response => {
            if (response.status > 201) {
                response.json().then(data => {
                    pErrorElement.innerHTML = data.detail !== "" ? data.detail : data.title;
                });
            }
            return response.json();
        })
        .then(data => {
            success(data);
        })
        .catch(function (error) {
            pErrorElement.innerHTML = error;
        });
}
