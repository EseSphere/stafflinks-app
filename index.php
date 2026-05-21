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

.create-account-box {
    margin-top: 2rem;
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
        <h3 class="mb-3 fw-bold">Signin</h3>

        <div class="container">
            <input
                style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
                placeholder="••••" type="text" maxlength="4" id="pin" class="pin-input form-control-plaintext mb-4"
                readonly>

            <div class="keypad d-grid gap-2 mt-3" id="keypad">
                <div class="row">
                    <div class="col-4"><button class="btn btn-light" data-num="1">1</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="2">2</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="3">3</button></div>
                </div>
                <div class="row">
                    <div class="col-4"><button class="btn btn-light" data-num="4">4</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="5">5</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="6">6</button></div>
                </div>
                <div class="row">
                    <div class="col-4"><button class="btn btn-light" data-num="7">7</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="8">8</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="9">9</button></div>
                </div>
                <div class="row">
                    <div class="col-4"><button class="btn btn-clear" id="clearPin">C</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                    <div class="col-4"><button class="btn btn-login" id="loginBtn"><i
                                class="bi bi-box-arrow-in-right"></i></button></div>
                </div>
            </div>

            <div class="create-account-box">
                <span class="create-account-text">Don’t have an account?</span>
                <a href="./signup" class="create-account-btn">
                    Create Account
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
const loginBtn = document.getElementById("loginBtn");
const pinCard = document.getElementById("pinCard");

let actualPin = "";

const dbName = "stafflinks";
const storeName = "tbl_team_account";

function fastClickHandler(el, callback) {
    el.addEventListener(
        "touchstart",
        (e) => {
            e.preventDefault();
            animateButton(el);
            requestAnimationFrame(callback);
        }, {
            passive: false
        }
    );

    el.addEventListener("mousedown", (e) => {
        e.preventDefault();
        animateButton(el);
        requestAnimationFrame(callback);
    });
}

function animateButton(btn) {
    btn.classList.add("btn-pop");
    setTimeout(() => btn.classList.remove("btn-pop"), 150);
}

keypad.querySelectorAll("[data-num]").forEach((btn) => {
    fastClickHandler(btn, () => pressNum(btn.getAttribute("data-num")));
});

fastClickHandler(clearBtn, clearPin);
fastClickHandler(loginBtn, login);

function pressNum(num) {
    if (actualPin.length < 4) {
        actualPin += num;
        pinInput.value = "•".repeat(actualPin.length - 1) + num;

        setTimeout(() => {
            pinInput.value = "•".repeat(actualPin.length);
        }, 300);
    }

    if (actualPin.length === 4) {
        setTimeout(() => login(), 200);
    }
}

function clearPin() {
    actualPin = "";
    pinInput.value = "";
}

async function hashPin(pin) {
    const encoder = new TextEncoder();
    const data = encoder.encode(pin);
    const hashBuffer = await crypto.subtle.digest("SHA-256", data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));

    return hashArray.map((b) => b.toString(16).padStart(2, "0")).join("");
}

function shakeCard() {
    pinCard.classList.add("shake");
    setTimeout(() => pinCard.classList.remove("shake"), 400);
}

function openDatabase() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName);

        request.onupgradeneeded = function(event) {
            const db = event.target.result;

            if (!db.objectStoreNames.contains(storeName)) {
                db.createObjectStore(storeName, {
                    keyPath: "id",
                });
            }
        };

        request.onsuccess = function(event) {
            const db = event.target.result;

            if (db.objectStoreNames.contains(storeName)) {
                resolve(db);
                return;
            }

            const newVersion = db.version + 1;
            db.close();

            const upgradeRequest = indexedDB.open(dbName, newVersion);

            upgradeRequest.onupgradeneeded = function(event) {
                const upgradedDb = event.target.result;

                if (!upgradedDb.objectStoreNames.contains(storeName)) {
                    upgradedDb.createObjectStore(storeName, {
                        keyPath: "id",
                    });
                }
            };

            upgradeRequest.onsuccess = function(event) {
                resolve(event.target.result);
            };

            upgradeRequest.onerror = function(event) {
                reject(event.target.error);
            };
        };

        request.onerror = function(event) {
            reject(event.target.error);
        };
    });
}

async function login() {
    if (actualPin.length !== 4) {
        alert("Please enter a 4-digit PIN");
        return;
    }

    try {
        const enteredHash = await hashPin(actualPin);
        const db = await openDatabase();

        const transaction = db.transaction(storeName, "readonly");
        const store = transaction.objectStore(storeName);
        const getAllRequest = store.getAll();

        getAllRequest.onerror = function() {
            alert("Failed to read user data from IndexedDB");
        };

        getAllRequest.onsuccess = function() {
            const users = getAllRequest.result;

            if (!users || users.length === 0) {
                alert("No user found. Please create a PIN first.");
                window.location.href = "./signup";
                return;
            }

            const user = users[0];

            if (!user.user_password) {
                alert("No PIN set. Please create a PIN first.");
                window.location.href = "./signup";
                return;
            }

            if (user.user_password === enteredHash) {
                window.location.href = "dashboard.php";
            } else {
                shakeCard();
                clearPin();
            }
        };
    } catch (error) {
        console.error("IndexedDB error:", error);
        alert("Failed to open IndexedDB");
    }
}
</script>

<!-- <script src="./js/login.js?v=<?php echo time(); ?>"></script> -->

<?php require_once('footer-log.php'); ?>