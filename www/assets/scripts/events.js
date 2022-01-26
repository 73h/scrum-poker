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
        document.querySelectorAll("header,footer,#session-link").forEach(function (element) {
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
