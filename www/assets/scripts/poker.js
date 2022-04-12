const selectCardSet = document.querySelector("#card-set");
const spanCardSetDetails = document.querySelector("#card-set-details");
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
const svgUserVoted = document.querySelector("#user-voted");
const svgUserNotDone = document.querySelector("#user-not-done");
const svgOffline = document.querySelector("#offline");

const currentCards = localStorage.getItem("cards");
const currentUsername = localStorage.getItem("username");

function loadSessionId() {
    if (document.URL.match(/.+\/[a-z0-9]{6}$/)) {
        inputSessionId.value = document.URL.replace(/.+\/([a-z0-9]{6})$/, '$1');
        let session = localStorage.getItem('session');
        if (session !== null) {
            localStorage.removeItem('session');
            let payload = JSON.parse(session);
            inputsUserName[1].value = payload.user.name;
            if (payload.session.hasOwnProperty("password")) {
                inputsSessionPassword[1].value = payload.session.password;
            }
            enterSession();
            return;
        }
        apiFetch("/api/sessions/" + inputSessionId.value, "GET", pErrorStart, function (data) {
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

function loadCards(onReady) {
    apiFetch("/api/cards", "GET", pErrorStart, function (data) {
        window.card_sets = data;
        for (const [cards_key, cards_value] of Object.entries(data)) {
            const cards = document.createElement("option");
            cards.value = cards_key;
            cards.text = cards_key;
            selectCardSet.add(cards);
            if (currentCards === cards_key) {
                selectCardSet.value = currentCards;
            }
        }
        showCardSetDetails();
        onReady();
    })
}

function showCardSetDetails() {
    const cards_text = [];
    for (const [card_key, card_value] of Object.entries(window.card_sets[selectCardSet.value])) {
        cards_text.push(card_value.value);
    }
    spanCardSetDetails.innerHTML = "(" + cards_text.join(" ") + ")";
}


function openSession(session) {
    window.session = session;
    divOwnerButtons.forEach(function (btn) {
        btn.style.display = "none";
    });
    divWelcome.style.display = "none";
    divSession.style.display = "block";
    setSessionLink();
    setOwnerButtons();
    apiFetch("/api/cards/" + window.session.card_set, "GET", pErrorSession, function (data) {
        window.card_set = data;
        handleSession(window.session, true);
        window.setInterval(refreshSession, 2000);
    })

}

function refreshSession() {
    apiFetch("/api/sessions/" + window.session.session, "GET", pErrorSession, function (data) {
        handleSession(data);
    }, null, window.session.token);
}

function handleSession(session, force = false) {
    if (force || JSON.stringify(window.session) !== JSON.stringify(session)) {
        window.session = session;
        handleButtons();
        setUserCards();
        setUsers();
    }
}

function getSessionLink() {
    return document.URL.replace(/\/$/, "").replace(/\/[a-z0-9]{6}$/, "") + "/" + window.session.session;
}

function setSessionLink() {
    const link = getSessionLink();
    pSessionLink.innerHTML = '<a href="' + link + '">' + link + '</a>'
}

function setOwnerButtons() {
    if (window.session.owner === window.session.user_id) {
        divOwnerButtons.forEach(function (btn) {
            btn.style.display = "inline";
        });
    }
}

function setUserCards() {
    pUserCards.innerHTML = "";
    Object.entries(window.card_set).forEach(function (card) {
        let newCard = svgCard.cloneNode(true);
        if (window.session.users[window.session.user_id].vote === card[0]) {
            newCard.classList.add("selected");
        }
        if (card[1].value === "break") {
            let newCafe = svgCafe.cloneNode(true);
            newCard.append(newCafe);
        } else {
            let span = newCard.querySelector("span");
            span.innerHTML = card[1].value;
            if (card[1].value.length === 2) {
                span.style.left = 0.21 + "em";
            }
            if (card[1].value.length === 3) {
                span.style.top = 0.96 + "em";
                span.style.left = 0.18 + "em";
                span.style.fontSize = 1.5 + "em";
            }
        }
        newCard.querySelector("svg g g path.background").classList.add(card[1].complexity.toLowerCase());
        newCard.querySelector(".click").addEventListener("click", function () {
            if (window.session.current_vote.uncovered === null) {
                let diVCard = this.parentElement;
                let payload = {"card": card[0]};
                apiFetch("/api/sessions/" + window.session.session + "/votes/" + window.session.current_vote.key, "POST", pErrorSession, function () {
                    document.querySelectorAll(".card").forEach(function (card) {
                        card.classList.remove("selected");
                    });
                    diVCard.classList.add("selected");
                }, payload, window.session.token);
            }
        });
        pUserCards.append(newCard);
    });
}

function handleButtons() {
    btnUncover.classList.remove("click-me-notification");
    if (window.session.current_vote.uncovered !== null) {
        btnNewVote.disabled = false;
        btnUncover.disabled = true;
    } else {
        // when all users have voted
        if (!Object.values(window.session.users).some((element) => element.voted === false)) {
            btnUncover.classList.add("click-me-notification");
        }
        btnNewVote.disabled = true;
        btnUncover.disabled = false;
    }
}

function setUsers() {
    pUsers.innerHTML = ""
    for (const [key, value] of Object.entries(window.session.users)) {
        let roboIcon = document.createElement("img");
        roboIcon.src = '/assets/images/icons/robo-' + value.robo_icon + '.png';
        let divName = document.createElement("div");
        divName.append(roboIcon);
        divName.append(value.name);
        if (value.user_id === window.session.owner) {
            let spanOwner = document.createElement("span");
            spanOwner.classList.add("owner");
            spanOwner.append("Owner");
            divName.append(spanOwner);
        }
        let offline = svgOffline.cloneNode(true);
        if (value.alive) offline.style.visibility = "hidden";
        divName.append(offline);
        let divVote = document.createElement("div");
        if (window.session.current_vote.uncovered !== null) {
            if (window.session.current_vote.votes !== null && value.vote !== null) {
                divVote.classList.add(window.card_set[value.vote].complexity.toLowerCase());
                divVote.style.paddingLeft = "3em";
                divVote.style.textAlign = "right";
                divVote.style.fontWeight = "bold";
                divVote.append(window.card_set[value.vote].value);
            }
        } else {
            divVote.append(value.voted ? svgUserVoted.cloneNode(true) : svgUserNotDone.cloneNode(true));
            divVote.style.paddingLeft = "3em";
            divVote.style.textAlign = "right";
        }
        let divUser = document.createElement("div");
        divUser.classList.add("user");
        divUser.append(divName);
        divUser.append(divVote);
        pUsers.append(divUser);
    }
    if (window.session.current_vote.uncovered !== null) {
        let sum = 0;
        let cnt = 0;
        Object.values(window.session.users).forEach(function (user) {
            if (user.vote !== null) {
                let card = window.card_set[user.vote];
                if (card.value !== "?" && card.value !== "break") {
                    cnt++;
                    sum += parseInt(user.vote, 10);
                }
            }
        });
        let average = sum > 0 ? Math.round(sum / cnt) : null;
        let divAverage = document.createElement("div");
        divAverage.classList.add("user");
        let divName = document.createElement("div");
        divName.innerHTML = "average";
        let divVote = document.createElement("div");
        divVote.classList.add(average !== null ? window.card_set[average].complexity.toLowerCase() : "unknown");
        divVote.style.paddingLeft = "3em";
        divVote.style.textAlign = "right";
        divVote.style.fontWeight = "bold";
        divVote.append(average !== null ? window.card_set[average].value : "-");
        divAverage.append(divName);
        divAverage.append(divVote);
        pUsers.append(divAverage);
    }
}

function showEnterSession() {
    divStartSession.style.display = "none";
    divEnterSession.style.display = "block";
}

function startSession() {
    let payload = {user: {name: inputsUserName[0].value}, session: {card_set: selectCardSet.value}}
    if (inputsSessionPassword[0].value !== "") {
        payload['session']['password'] = inputsSessionPassword[0].value;
    }
    apiFetch("/api/sessions", "POST", pErrorStart, function (data) {
        localStorage.setItem("session", JSON.stringify(payload));
        window.location.replace("/" + data.session);
    }, payload);
}

function enterSession() {
    if (inputSessionId.value === "") {
        pErrorEnter.innerHTML = "enter a session id";
        return;
    }
    let payload = {user: {name: inputsUserName[1].value}}
    if (inputsSessionPassword[1].value !== "") {
        payload['session'] = {'password': inputsSessionPassword[1].value};
    }
    apiFetch("/api/sessions/" + inputSessionId.value + "/users", "POST", pErrorEnter, function (data) {
        openSession(data);
    }, payload);
}

function loadQrcode() {
    let divModal = document.createElement("div");
    divModal.classList.add("modal-background");
    let img = document.createElement("img");
    img.src = "https://chart.googleapis.com/chart?cht=qr&choe=UTF-8&chld=L|0&chs=250x250&chl=" + getSessionLink(window.session);
    img.classList.add("modal");
    divModal.addEventListener("click", function () {
        this.childNodes.forEach(function (n) {
            n.remove();
        })
        this.remove();
    });
    divModal.append(img)
    document.body.append(divModal);
}

function loadEvents() {

    inputsUserName.forEach(
        function (element) {
            element.addEventListener("blur", function () {
                localStorage.setItem("username", this.value);
            });
        }
    );

    selectCardSet.addEventListener("change", function () {
        localStorage.setItem("cards", this.value);
    });

    selectCardSet.addEventListener("change", showCardSetDetails);

    // start session
    btnStartSession.addEventListener("click", startSession);

    // enter session
    btnEnterSession.addEventListener("click", enterSession);

    // vote uncover
    btnUncover.addEventListener("click", function () {
        let payload = {"task": "uncover"};
        apiFetch("/api/sessions/" + window.session.session + "/votes/" + window.session.current_vote.key, "PUT", pErrorSession, function () {
            refreshSession();
        }, payload, window.session.token);
    });

    // new vote
    btnNewVote.addEventListener("click", function () {
        apiFetch("/api/sessions/" + window.session.session + "/votes", "POST", pErrorSession, function () {
            refreshSession();
        }, null, window.session.token);
    });

    btnExit.addEventListener("click", function () {
        const link = "/" + session.session;
        window.location.replace(link);
    });

    btnFullscreen.addEventListener("click", function () {
        document.querySelectorAll("header,footer,#session-link-wrapper").forEach(function (element) {
            element.style.display = window.fullscreen ? "block" : "none";
        });
        window.fullscreen = !window.fullscreen;
    });

    btnQrcode.addEventListener("click", loadQrcode);

}

window.addEventListener("DOMContentLoaded", function () {
    window.fullscreen = false;
    window.session = null;
    divMainError.style.display = "none";
    divStartSession.style.display = "block";
    inputsSessionPassword[1].parentElement.style.display = "none";
    loadCards(function () {
        loadUsername();
        loadSessionId();
        loadEvents();
    });
});
