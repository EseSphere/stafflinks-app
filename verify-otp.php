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

:root {
    --accent: #c94a57;
    --accent2: #e88a3d;
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
</style>

<div class="mt-5" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card text-center" id="otpCard">
        <h3 class="mb-3 fw-bold">Verify OTP</h3>

        <div class="container">
            <div class="alert alert-info fs-6 g-1 d-flex align-items-center justify-content-center" role="alert"
                style="border-radius: 12px; border-left:10px solid #EB6F46; padding:8px; background-color:#E9E7E0; margin-bottom:20px; text-align:left;">
                Enter the 6-digit verification code sent to your email address.
            </div>

            <input
                style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
                placeholder="••••••" type="text" maxlength="6" id="otp" class="pin-input form-control-plaintext mb-4"
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
                    <div class="col-4"><button class="btn btn-clear" id="clearOtp">C</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                    <div class="col-4"><button class="btn btn-login" id="verifyOtp">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const otpInput = document.getElementById("otp");
const messageBox = document.getElementById("messageBox");
const verifyBtn = document.getElementById("verifyOtp");
const clearBtn = document.getElementById("clearOtp");
const keypad = document.getElementById("keypad");
const otpCard = document.getElementById("otpCard");

const dbName = "stafflinks";
const storeName = "tbl_team_account";

const params = new URLSearchParams(window.location.search);
const email = params.get("email");

let actualOtp = "";
let isVerifying = false;

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
    otpCard.classList.add("shake");
    setTimeout(() => otpCard.classList.remove("shake"), 400);
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

fastClickHandler(clearBtn, clearOtp);
fastClickHandler(verifyBtn, verifyOtp);

/* ── input logic ── */
function pressNum(num) {
    if (actualOtp.length < 6) {
        actualOtp += num;
        otpInput.value = "•".repeat(actualOtp.length - 1) + num;

        setTimeout(() => {
            otpInput.value = "•".repeat(actualOtp.length);
        }, 300);

        showMessage("", "");
    }

    if (actualOtp.length === 6) {
        setTimeout(() => verifyOtp(), 200);
    }
}

function clearOtp() {
    actualOtp = "";
    otpInput.value = "";
    showMessage("", "");
    isVerifying = false;
    verifyBtn.disabled = false;
}

/* ── IndexedDB helpers ── */
function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName);
        request.onsuccess = event => resolve(event.target.result);
        request.onerror = () => reject("Failed to open IndexedDB.");
    });
}

async function getUserByEmail(userEmail) {
    const db = await openIndexedDB();

    return new Promise((resolve, reject) => {
        if (!db.objectStoreNames.contains(storeName)) {
            reject("Object store not found.");
            return;
        }

        const transaction = db.transaction(storeName, "readonly");
        const store = transaction.objectStore(storeName);

        if (store.indexNames.contains("user_email_address")) {
            const index = store.index("user_email_address");
            const request = index.get(userEmail);
            request.onsuccess = () => resolve(request.result || null);
            request.onerror = () => reject("Unable to read user from email index.");
        } else {
            const request = store.openCursor();
            request.onsuccess = event => {
                const cursor = event.target.result;
                if (cursor) {
                    const user = cursor.value;
                    if (
                        user.user_email_address &&
                        String(user.user_email_address).toLowerCase() ===
                        String(userEmail).toLowerCase()
                    ) {
                        resolve(user);
                        return;
                    }
                    cursor.continue();
                } else {
                    resolve(null);
                }
            };
            request.onerror = () => reject("Unable to search user from object store.");
        }
    });
}

async function updateUserInObjectStore(user) {
    const db = await openIndexedDB();

    return new Promise((resolve, reject) => {
        const transaction = db.transaction(storeName, "readwrite");
        const store = transaction.objectStore(storeName);
        const request = store.put(user);
        request.onsuccess = () => resolve(true);
        request.onerror = () => reject("Unable to update user verification status.");
    });
}

async function generateAndSendLoginPasscode(userEmail) {
    const response = await fetch("verification_backend.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify({
            action: "send_generated_passcode",
            email: userEmail
        })
    });

    const text = await response.text();
    let data;

    try {
        data = JSON.parse(text);
    } catch (err) {
        console.error("Invalid JSON response:", text);
        throw new Error("Server returned invalid response.");
    }

    if (!data.success) {
        throw new Error(data.message || "Unable to generate and send passcode.");
    }

    return data;
}

/* ── verify ── */
async function verifyOtp() {
    if (isVerifying) return;

    const enteredOtp = actualOtp.trim();

    if (!email) {
        showMessage("Email address is missing. Please restart account setup.", "#dc3545");
        shakeCard();
        return;
    }

    if (enteredOtp.length !== 6) {
        showMessage("Please enter the complete 6-digit OTP code.", "#dc3545");
        shakeCard();
        return;
    }

    isVerifying = true;
    verifyBtn.disabled = true;
    showMessage("Verifying OTP...", "#0d6efd");

    try {
        const user = await getUserByEmail(email);

        if (!user) {
            showMessage("Account record not found. Please restart account setup.", "#dc3545");
            shakeCard();
            clearOtp();
            return;
        }

        if (String(user.otp).trim() !== String(enteredOtp).trim()) {
            showMessage("Wrong OTP code. Please check your email and try again.", "#dc3545");
            shakeCard();
            clearOtp();
            return;
        }

        user.otp_verified = true;
        user.otp_verified_at = new Date().toISOString();
        user.passcode_email_sent = false;

        await updateUserInObjectStore(user);

        showMessage("OTP verified. Generating your login passcode...", "#0d6efd");

        await generateAndSendLoginPasscode(email);

        user.passcode_email_sent = true;
        user.passcode_email_sent_at = new Date().toISOString();

        await updateUserInObjectStore(user);

        showMessage("OTP verified successfully. Login passcode sent to your email. Redirecting...", "#198754");

        setTimeout(() => {
            window.location.href = "create-pin.php?email=" + encodeURIComponent(email);
        }, 700);

    } catch (error) {
        console.error(error);
        showMessage(error.message || "Something went wrong while verifying OTP. Please try again.", "#dc3545");
        shakeCard();
        clearOtp();
    }
}
</script>

<?php require_once('footer-log.php'); ?>