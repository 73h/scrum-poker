const btn = document.querySelector(".scheme-toogle");
const selectCards = document.querySelector("#cards");
const inputsUserName = document.querySelectorAll("#member-enter,#member-start");
const inputSessionId = document.querySelector("#session-id");
const divStartSession = document.querySelector(".start-session");
const divEnterSession = document.querySelector(".enter-session");
const divSession = document.querySelector(".session");
const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

const currentTheme = localStorage.getItem("theme");
if (currentTheme === "dark") {
    document.body.classList.toggle("dark-theme");
} else if (currentTheme === "light") {
    document.body.classList.toggle("light-theme");
}

const currentCards = localStorage.getItem("cards");
const currentUsername = localStorage.getItem("username");

btn.addEventListener("click", function () {
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

window.addEventListener("DOMContentLoaded", function () {
    divSession.style.display = "none";
    loadCards();
    loadUsername();
    loadSessionId();
});

selectCards.addEventListener("change", function () {
    localStorage.setItem("cards", this.value);
});

inputsUserName.forEach(
    function (element) {
        element.addEventListener("blur", function () {
            localStorage.setItem("username", this.value);
        });
    }
);

function loadSessionId() {
    if (document.URL.match(/.+\/[a-z0-9]{6}$/)) {
        inputSessionId.value = document.URL.replace(/.+\/([a-z0-9]{6})$/, '$1');
        divStartSession.style.display = "none";
    } else {
        divEnterSession.style.display = "none";
    }
}

function loadUsername() {
    if (currentUsername !== null) {
        inputsUserName.forEach(
            function (element) {
                element.value = currentUsername;
            }
        );
    }
}

function loadCards() {
    fetch('/api/cards')
        .then(response => response.json())
        .then(data => {
            for (const [cards_key, cards_value] of Object.entries(data)) {
                const cards = document.createElement("option");
                cards.value = cards_key;
                const cards_text = [];
                for (const [card_key, card_value] of Object.entries(cards_value)) {
                    cards_text.push(card_value.value);
                }
                cards.text = cards_key + " (" + cards_text.join(",") + ")";
                selectCards.add(cards);
                if (currentCards === cards_key) {
                    selectCards.value = currentCards;
                }
            }
        });
}
