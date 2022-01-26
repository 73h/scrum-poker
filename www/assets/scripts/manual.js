const pCards = document.querySelector("#cards");

function loadCards(onReady) {
    apiFetch("/api/cards", "GET", document.createElement("div"), function (data) {
        window.card_sets = data;
        onReady();
    })
}

window.addEventListener("DOMContentLoaded", function () {
    loadCards(function () {
        for (const [cards_key, cards_value] of Object.entries(window.card_sets)) {
            const cardset = document.createElement("p");
            const title = document.createElement("p");
            title.append(cards_key)
            const cards = document.createElement("div");
            cards.classList.add("manual-cards");
            for (const [card_key, card_value] of Object.entries(cards_value)) {
                let newCard = svgCard.cloneNode(true);
                if (card_value.value === "break") {
                    let newCafe = svgCafe.cloneNode(true);
                    newCard.append(newCafe);
                } else {
                    let span = newCard.querySelector("span");
                    span.innerHTML = card_value.value;
                    if (card_value.value.length === 2) {
                        span.style.left = 0.21 + "em";
                    }
                    if (card_value.value.length === 3) {
                        span.style.top = 0.96 + "em";
                        span.style.left = 0.18 + "em";
                        span.style.fontSize = 1.5 + "em";
                    }
                }
                newCard.querySelector("svg g g path.background").classList.add(card_value.complexity.toLowerCase());
                cards.append(newCard);
            }
            cardset.append(title);
            cardset.append(cards);
            pCards.append(cardset);
        }
    });
});
