const btn = document.querySelector(".scheme-toggle");
const selectCardSet = document.querySelector("#card-set");
const inputsUserName = document.querySelectorAll("#member-start,#member-enter");
const inputSessionId = document.querySelector("#session-id");
const inputsSessionPassword = document.querySelectorAll("#session-password-start,#session-password-enter");
const divStartSession = document.querySelector(".start-session");
const divEnterSession = document.querySelector(".enter-session");
const divSession = document.querySelector(".session");
const divWelcome = document.querySelector(".welcome");
const btnStartSession = document.querySelector("#btn-start");
const btnEnterSession = document.querySelector("#btn-enter");
const btnUncover = document.querySelector("#btn-uncover");
const btnNewVote = document.querySelector("#btn-new-vote");
const btnQrcode = document.querySelector("#btn-qrcode");
const btnFullscreen = document.querySelector("#btn-fullscreen");
const btnExit = document.querySelector("#btn-exit");
const divMainError = document.querySelector(".main-error");
const pErrorStart = document.querySelector(".start-session .error");
const pErrorEnter = document.querySelector(".enter-session .error");
const pErrorSession = document.querySelector(".session .error");
const pSessionLink = document.querySelector("#session-link");
const pUserCards = document.querySelector("#user-cards");
const pUsers = document.querySelector("#users");
const divOwnerButtons = [btnUncover, btnNewVote, btnQrcode];
const svgCard = document.querySelector("#card");
const svgCafe = document.querySelector("#cafe");
const svgUserVoted = document.querySelector("#user-voted");
const svgUserNotDone = document.querySelector("#user-not-done");
const pUserVotings = document.querySelector("#user-votings");

const prefersDarkScheme = window.matchMedia("(prefers-color-scheme: dark)");

const currentTheme = localStorage.getItem("theme");
if (currentTheme === "dark") document.body.classList.toggle("dark-theme");
else if (currentTheme === "light") document.body.classList.toggle("light-theme");
const currentCards = localStorage.getItem("cards");
const currentUsername = localStorage.getItem("username");

window.addEventListener("load", function () {
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

selectCardSet.addEventListener("change", function () {
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
            selectCardSet.add(cards);
            if (currentCards === cards_key) {
                selectCardSet.value = currentCards;
            }
        }
    })
}

// start session
btnStartSession.addEventListener("click", function () {
    let payload = {user: {name: inputsUserName[0].value}, session: {card_set: selectCardSet.value}}
    if (inputsSessionPassword[0].value !== "") {
        payload['session']['password'] = inputsSessionPassword[0].value;
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
    setSessionLink(session);
    setOwnerButtons(session);
    apiFetch("/api/cards/" + session.card_set, "GET", pErrorSession, function (data) {
        window.card_set = data;
        handleSession(session);
        window.setInterval(refreshSession, 2000);
    })

}

function refreshSession() {
    apiFetch("/api/sessions/" + window.session.session, "GET", pErrorSession, function (data) {
        handleSession(data);
    }, null, window.session.token);
}

function handleSession(session) {
    window.session = session;
    setUsers();
    setUserCards();
    handleButtons();
    setUserVotings();
    console.log(window.session);
}

function setSessionLink(session) {
    const link = document.URL.replace(/\/$/, "").replace(/\/[a-z0-9]{6}$/, "") + "/" + session.session;
    pSessionLink.innerHTML = 'session: <a href="' + link + '">' + link + '</a>'
}

function setOwnerButtons(session) {
    if (session.owner === session.user_id) {
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

function setUserCards() {
    pUserCards.innerHTML = "";
    Object.entries(window.card_set).forEach(function (card) {
        let newCard = svgCard.cloneNode(true);
        newCard.style.display = "inline-block";
        if (window.session.current_vote.your_vote !== null && card[0] === window.session.current_vote.your_vote.card) {
            newCard.classList.add("selected");
        }
        if (card[1].value === "break") {
            let newCafe = svgCafe.cloneNode(true);
            newCafe.style.display = "inline-block";
            newCard.append(newCafe);
        } else {
            let span = newCard.querySelector("span");
            span.innerHTML = card[1].value;
            span.classList.add(card[1].complexity.toLowerCase());
            if (card[1].value.length === 2) {
                span.style.left = 0.21 + "em";
            }
            if (card[1].value.length === 3) {
                span.style.top = 0.96 + "em";
                span.style.left = 0.18 + "em";
                span.style.fontSize = 1.5 + "em";
            }
        }
        newCard.querySelectorAll("svg g g path").forEach(function (path) {
            path.classList.add(card[1].complexity.toLowerCase());
        });
        newCard.addEventListener("click", function () {
            let payload = {"card": card[0]};
            apiFetch("/api/sessions/" + window.session.session + "/votes/" + window.session.current_vote.key, "POST", pErrorSession, function () {
                refreshSession();
            }, payload, window.session.token);
        });
        pUserCards.append(newCard);
    });
}

function handleButtons() {
    if (window.session.current_vote.uncovered) {
        btnNewVote.disabled = false;
        btnUncover.disabled = true;
    } else {
        btnNewVote.disabled = true;
        btnUncover.disabled = false;
    }
}

btnUncover.addEventListener("click", function () {
    let payload = {"task": "uncover"};
    apiFetch("/api/sessions/" + window.session.session + "/votes/" + window.session.current_vote.key, "PUT", pErrorSession, function () {
        refreshSession();
    }, payload, window.session.token);
});

btnNewVote.addEventListener("click", function () {
    let payload = {"task": "uncover"};
    apiFetch("/api/sessions/" + window.session.session + "/votes", "POST", pErrorSession, function () {
        refreshSession();
    }, null, window.session.token);
});

function setUserVotings() {
    pUserVotings.innerHTML = "";
    for (const [key, value] of Object.entries(window.session.users)) {
        let divUser = document.createElement("div");
        divUser.classList.add("user-voting");
        if (window.session.current_vote.user_voted.includes(key)) {
            voted = svgUserVoted.cloneNode(true);
            voted.style.display = "inline-block";
            divUser.append(voted);
        } else {
            notVote = svgUserNotDone.cloneNode(true);
            notVote.style.display = "inline-block";
            divUser.append(notVote);
        }
        divUser.append(value.name);
        if (window.session.current_vote.votes !== null) {
            if (window.session.current_vote.votes.hasOwnProperty(key)) {
                card = window.card_set[window.session.current_vote.votes[key].card].value;
                divUser.append(" ... " + card);
            }
        }
        pUserVotings.append(divUser);
    }
}
