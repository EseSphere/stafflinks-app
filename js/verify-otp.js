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