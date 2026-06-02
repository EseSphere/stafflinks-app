<?php require_once('header-log.php'); ?>

<style>
.btn-pop {
    transform: scale(1.2);
    transition: transform 0.15s ease;
}

@keyframes shake {
    0% {
        transform: translateX(0);
    }

    20% {
        transform: translateX(-10px);
    }

    40% {
        transform: translateX(10px);
    }

    60% {
        transform: translateX(-10px);
    }

    80% {
        transform: translateX(10px);
    }

    100% {
        transform: translateX(0);
    }
}

.shake {
    animation: shake 0.4s;
}

body {
    margin: 0;
    padding: 0;
    font-size: 18px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #fff !important;
    overscroll-behavior: none;
}

html {
    overscroll-behavior: none;
    background-color: #fff !important;
}

.card {
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, .1), 0 1px 2px 0 rgba(0, 0, 0, .06);
    position: relative;
    display: flex;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 0 solid rgba(0, 0, 0, .125);
    border-radius: .25rem;
}

.pin-input {
    font-size: 3rem;
    letter-spacing: 0.6rem;
    text-align: center;
    border: none;
    outline: none;
    background: inherit !important;
    pointer-events: none;
}

.keypad button {
    width: 50px;
    height: 50px;
    margin-bottom: 8px;
    background-color: rgba(220, 221, 225, .5);
    color: rgba(47, 54, 64, .9);
    font-size: 16px;
    font-weight: 800;
}

.keypad button:active {
    transform: scale(0.95);
}

.btn-login {
    background-color: #0d6efd;
    color: #fff;
    font-size: 1.5rem;
}

.btn-login:hover {
    background-color: #0b5ed7;
    color: #fff;
}

.btn-clear {
    background-color: #dc3545;
    color: #fff;
}

.create-account-box {
    margin-top: 1rem;
    padding: 1.25rem;
    border-radius: 18px;
    background: rgba(0, 0, 0, 0.03);
    border: 1px solid rgba(0, 0, 0, 0.08);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.create-account-text {
    color: #6c757d;
    font-size: 0.95rem;
    font-weight: 500;
}

.create-account-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0.65rem 1.2rem;
    border-radius: 999px;
    background: #111;
    color: #fff;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 700;
    transition: all 0.2s ease;
    box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
}

.create-account-btn:hover {
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
}

@media (max-width: 576px) {
    .create-account-box {
        padding: 1rem;
    }

    .create-account-btn {
        width: 100%;
    }
}
</style>

<div class="mt-5" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card text-center" id="pinCard">
        <h3 class="mb-3 fw-bold">Register PIN</h3>

        <div class="container">
            <input
                style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
                placeholder="••••" type="text" maxlength="4" id="pin" class="pin-input form-control-plaintext mb-4"
                readonly>

            <div id="messageBox" class="mb-2 fs-6 fw-semibold"></div>

            <div class="keypad d-grid gap-1 mt-2" id="keypad">
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="1">1</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="2">2</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="3">3</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="4">4</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="5">5</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="6">6</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="7">7</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="8">8</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="9">9</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-clear" id="clearPin">C</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                    <div class="col-4"><button class="btn btn-login" id="savePin">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button></div>
                </div>
            </div>

            <div class="create-account-box">
                <span class="create-account-text">Already have an account?</span>
                <a href="./" class="create-account-btn">
                    Login
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script>
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
</script>

<?php require_once('footer-log.php'); ?>