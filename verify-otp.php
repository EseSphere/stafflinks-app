<?php require_once('header-log.php'); ?>

<style>
.btn-pop {
    transform: scale(1.08);
    transition: transform 0.04s linear;
}

:root {
    --accent: #c94a57;
    --accent2: #e88a3d;
    --card-bg: #ffffff;
    --muted: #6c757d;
    --bg1: #f7faff;
    --bg2: #eef5ff;
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

.topbar {
    z-index: 20;
    background: linear-gradient(90deg, var(--accent), var(--accent2));
    padding: 12px 0;
    border-bottom-right-radius: 15px;
    border-bottom-left-radius: 15px;
    border-bottom: 5px solid #E3C5B2;
    color: white;
    width: 100%;
    height: 200px;
}

#gsLogo {
    height: 50px;
    width: 140px;
}

@media (min-width: 769px) {
    #userDetails {
        display: none;
    }
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

input {
    border-radius: 5px;
    padding: 10px;
    width: 100%;
    height: 50px;
    background-color: inherit !important;
}

.pin-input {
    font-size: 2rem;
    letter-spacing: 0.35rem;
    text-align: center;
    border: none;
    outline: none;
    background: inherit !important;
    pointer-events: none;
}
</style>

<div class="mt-4" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card p-4 text-center mt-3">
        <h3 class="mb-3 fw-bold">Verify OTP</h3>

        <div class="alert alert-info fs-6"
            style="border-radius: 12px; border-left:10px solid #EB6F46; padding:8px; background-color:#E9E7E0; margin-bottom:30px; text-align:left;">
            Enter the 6-digit verification code sent to your email address.
        </div>

        <input
            style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.35rem; text-align:center; font-weight:bold;"
            placeholder="••••••" type="text" maxlength="6" id="otp" class="pin-input form-control-plaintext mb-4"
            readonly>

        <div id="messageBox" class="mb-3 fs-6 fw-semibold"></div>

        <div class="keypad d-grid gap-2 mt-3" id="keypad">
            <div class="row">
                <div class="col-4"><button type="button" class="btn btn-light" data-num="1">1</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="2">2</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="3">3</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button type="button" class="btn btn-light" data-num="4">4</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="5">5</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="6">6</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button type="button" class="btn btn-light" data-num="7">7</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="8">8</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="9">9</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button type="button" class="btn btn-clear" id="clearOtp">C</button></div>
                <div class="col-4"><button type="button" class="btn btn-light" data-num="0">0</button></div>
                <div class="col-4"><button type="button" class="btn btn-login" id="verifyOtp"><i
                            class="bi bi-box-arrow-in-right"></i></button></div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const dbName = "stafflinks";
    const storeName = "tbl_team_account";

    const otpInput = document.getElementById("otp");
    const messageBox = document.getElementById("messageBox");
    const verifyBtn = document.getElementById("verifyOtp");
    const clearBtn = document.getElementById("clearOtp");

    const params = new URLSearchParams(window.location.search);
    const email = params.get("email");

    function showMessage(message, color) {
        messageBox.textContent = message;
        messageBox.style.color = color;
    }

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
                            String(user.user_email_address).toLowerCase() === String(userEmail)
                            .toLowerCase()
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

    document.querySelectorAll("[data-num]").forEach(button => {
        button.addEventListener("click", function(e) {
            e.preventDefault();

            if (otpInput.value.length < 6) {
                otpInput.value += this.dataset.num;
                showMessage("", "");

                this.classList.add("btn-pop");

                setTimeout(() => {
                    this.classList.remove("btn-pop");
                }, 40);
            }
        });
    });

    clearBtn.addEventListener("click", function(e) {
        e.preventDefault();
        otpInput.value = "";
        showMessage("", "");
        verifyBtn.disabled = false;
    });

    verifyBtn.addEventListener("click", async function(e) {
        e.preventDefault();

        const enteredOtp = otpInput.value.trim();

        if (!email) {
            showMessage("Email address is missing. Please restart account setup.", "#dc3545");
            return;
        }

        if (enteredOtp.length !== 6) {
            showMessage("Please enter the complete 6-digit OTP code.", "#dc3545");
            return;
        }

        verifyBtn.disabled = true;
        showMessage("Verifying OTP...", "#0d6efd");

        try {
            const user = await getUserByEmail(email);

            if (!user) {
                showMessage("Account record not found. Please restart account setup.", "#dc3545");
                verifyBtn.disabled = false;
                return;
            }

            if (String(user.otp).trim() !== String(enteredOtp).trim()) {
                otpInput.value = "";
                showMessage("Wrong OTP code. Please check your email and try again.", "#dc3545");
                verifyBtn.disabled = false;
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

            showMessage(
                "OTP verified successfully. Login passcode sent to your email. Redirecting...",
                "#198754");

            setTimeout(() => {
                window.location.href = "create-pin.php?email=" + encodeURIComponent(email);
            }, 700);

        } catch (error) {
            console.error(error);
            showMessage(error.message ||
                "Something went wrong while verifying OTP. Please try again.", "#dc3545");
            verifyBtn.disabled = false;
        }
    });
});
</script>

<?php require_once('footer-log.php'); ?>