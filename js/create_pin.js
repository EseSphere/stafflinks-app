const pinInput = document.getElementById("pin");
const keypad = document.getElementById("keypad");
const clearBtn = document.getElementById("clearPin");
const saveBtn = document.getElementById("savePin");
const messageBox = document.getElementById("messageBox");
const pinCard = document.getElementById("pinCard");

let actualPin = "";
let isProcessing = false;

/* ── helpers ── */
function showMessage(message, color) {
    messageBox.textContent = message;
    messageBox.style.color = color;
}

function animateButton(btn) {
    btn.classList.add("btn-pop");
    setTimeout(() => btn.classList.remove("btn-pop"), 150);
}

function shakeCard() {
    pinCard.classList.add("shake");
    setTimeout(() => pinCard.classList.remove("shake"), 400);
}

function fastClickHandler(el, callback) {
    el.addEventListener("touchstart", (e) => {
        e.preventDefault();
        animateButton(el);
        requestAnimationFrame(callback);
    }, {
        passive: false
    });

    el.addEventListener("mousedown", (e) => {
        e.preventDefault();
        animateButton(el);
        requestAnimationFrame(callback);
    });
}

/* ── keypad wiring ── */
keypad.querySelectorAll("[data-num]").forEach((btn) => {
    fastClickHandler(btn, () => pressNum(btn.getAttribute("data-num")));
});

fastClickHandler(clearBtn, clearPin);
fastClickHandler(saveBtn, savePin);

/* ── input logic ── */
function pressNum(num) {
    if (actualPin.length < 4) {
        actualPin += num;
        pinInput.value = "•".repeat(actualPin.length - 1) + num;

        setTimeout(() => {
            pinInput.value = "•".repeat(actualPin.length);
        }, 300);

        showMessage("", "");
    }

    if (actualPin.length === 4) {
        setTimeout(() => savePin(), 200);
    }
}

function clearPin() {
    actualPin = "";
    pinInput.value = "";
    showMessage("", "");
    isProcessing = false;
    saveBtn.disabled = false;
}

/* ── crypto ── */
async function hashPin(pin) {
    const encoder = new TextEncoder();
    const data = encoder.encode(pin);
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    return hashArray.map(b => b.toString(16).padStart(2, "0")).join("");
}

/* ── save ── */
async function savePin() {
    if (isProcessing) return;

    if (actualPin.length !== 4) {
        showMessage("Please enter a 4-digit PIN.", "#dc3545");
        shakeCard();
        return;
    }

    isProcessing = true;
    saveBtn.disabled = true;
    showMessage("Saving your PIN...", "#0d6efd");

    try {
        const encryptedPin = await hashPin(actualPin);

        const db = await new Promise((resolve, reject) => {
            const request = indexedDB.open("stafflinks", 1);
            request.onsuccess = event => resolve(event.target.result);
            request.onerror = () => reject("Failed to open IndexedDB.");
        });

        const transaction = db.transaction("tbl_team_account", "readwrite");
        const store = transaction.objectStore("tbl_team_account");

        const users = await new Promise((resolve, reject) => {
            const req = store.getAll();
            req.onsuccess = () => resolve(req.result);
            req.onerror = () => reject("Failed to read user data from IndexedDB.");
        });

        if (!users || users.length === 0) {
            showMessage("No user found. Please restart account setup.", "#dc3545");
            shakeCard();
            clearPin();
            return;
        }

        const user = users[0];
        user.user_password = encryptedPin;
        user.status2 = "active";

        await new Promise((resolve, reject) => {
            const req = store.put(user);
            req.onsuccess = () => resolve(true);
            req.onerror = () => reject("Failed to save PIN. Please try again.");
        });

        showMessage("PIN saved successfully! Redirecting...", "#198754");

        setTimeout(() => {
            window.location.href = "./";
        }, 700);

    } catch (error) {
        console.error(error);
        showMessage(error.message || "Something went wrong. Please try again.", "#dc3545");
        shakeCard();
        clearPin();
    }
}