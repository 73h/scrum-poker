const btn = document.querySelector(".scheme-toggle");
const selectCards = document.querySelector("#cards");
const inputsUserName = document.querySelectorAll("#member-start,#member-enter");
const inputSessionId = document.querySelector("#session-id");
const inputsSessionPassword = document.querySelectorAll("#session-password-start,#session-password-enter");
const divStartSession = document.querySelector(".start-session");
const divEnterSession = document.querySelector(".enter-session");
const divSession = document.querySelector(".session");
const divWelcome = document.querySelector(".welcome");
const btnStartSession = document.querySelector("#btn-start");
const btnEnterSession = document.querySelector("#btn-enter");
const btnReveal = document.querySelector("#btn-reveal");
const btnNewVote = document.querySelector("#btn-new-vote");
const btnQrcode = document.querySelector("#btn-qrcode");
const divMainError = document.querySelector(".main-error");
const pErrorStart = document.querySelector(".start-session .error");
const pErrorEnter = document.querySelector(".enter-session .error");
const pErrorSession = document.querySelector(".session .error");
const pSessionLink = document.querySelector("#session-link");
const pUsers = document.querySelector("#users");
const divOwnerButtons = [btnReveal, btnNewVote, btnQrcode];

const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

const currentTheme = localStorage.getItem("theme");
if (currentTheme === "dark") document.body.classList.toggle("dark-theme");
else if (currentTheme === "light") document.body.classList.toggle("light-theme");
const currentCards = localStorage.getItem("cards");
const currentUsername = localStorage.getItem("username");

window.addEventListener("DOMContentLoaded", function () {
    divMainError.style.display = "none";
    divStartSession.style.display = "block";
    inputsSessionPassword[1].parentElement.style.display = "none";
    loadUsername();
    loadSessionId();
    loadCards();
});

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
        apiFetch("/api/sessions/" + inputSessionId.value, "GET", pErrorSession, function (data) {
            if (data.has_password === true) inputsSessionPassword[1].parentElement.style.display = "block";
            divStartSession.style.display = "none";
            divEnterSession.style.display = "block";
        });
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
        body: payload === null ? null : JSON.stringify(payload)
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

function loadCards() {
    apiFetch("/api/cards", "GET", pErrorStart, function (data) {
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
    })
}

// start session
btnStartSession.addEventListener("click", function () {
    let payload = {user: {name: inputsUserName[0].value}}
    if (inputsSessionPassword[0].value !== "") {
        payload['session'] = {'password': inputsSessionPassword[0].value};
    }
    apiFetch("/api/sessions", "POST", pErrorStart, function (data) {
        openSession(data);
    }, payload);
});

// enter session
btnEnterSession.addEventListener("click", function () {
    let payload = {user: {name: inputsUserName[1].value}}
    if (inputsSessionPassword[1].value !== "") {
        payload['session'] = {'password': inputsSessionPassword[1].value};
    }
    apiFetch("/api/sessions/" + inputSessionId.value + "/users", "POST", pErrorEnter, function (data) {
        openSession(data);
    }, payload);
});

function openSession(session) {
    divOwnerButtons.forEach(function (btn) {
        btn.style.display = "none";
    });
    divWelcome.style.display = "none";
    divSession.style.display = "block";
    refreshSession(session);
    setSessionLink();
    setOwnerButtons();
    window.setInterval(function () {
        apiFetch("/api/sessions/" + window.session.session, "GET", pErrorSession, function (data) {
            refreshSession(data);
        }, null, window.session.token);
    }, 2000);
}

function refreshSession(session) {
    window.session = session;
    setUsers();
    //console.log(window.session);
}

function setSessionLink() {
    const link = document.URL.replace(/\/$/, "").replace(/\/[a-z0-9]{6}$/, "") + "/" + window.session.session;
    pSessionLink.innerHTML = 'session: <a href="' + link + '">' + link + '</a>'
}

function setOwnerButtons() {
    if (window.session.owner === window.session.user_id) {
        divOwnerButtons.forEach(function (btn) {
            btn.style.display = "inline";
        });
    }
}

function setUsers() {
    let arrUsers = [];
    for (const [key, value] of Object.entries(window.session.users)) {
        let name = '<div class="' + (value.alive ? 'online' : 'offline') + '"></div>' + value.name
        if (key === window.session.owner) {
            name += '<span class="small">(owner)</span>';
        }
        arrUsers.push(name);
    }
    pUsers.innerHTML = arrUsers.join(" ");
}
