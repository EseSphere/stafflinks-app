// signup.js - Handles the signup process, including OTP generation and IndexedDB storage

const dbName = "stafflinks";
const storeName = "tbl_team_account";

function generateOTP() {
    return Math.floor(100000 + Math.random() * 900000).toString();
}

function openIndexedDB() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open(dbName, 1);

        request.onupgradeneeded = function (event) {
            const db = event.target.result;
            if (!db.objectStoreNames.contains(storeName)) {
                const store = db.createObjectStore(storeName, {
                    keyPath: "id"
                });
                store.createIndex("user_email_address", "user_email_address", {
                    unique: true
                });
            }
        };

        request.onsuccess = event => resolve(event.target.result);
        request.onerror = () => reject("Failed to open IndexedDB");
    });
}

async function saveOrUpdateUserInIndexedDB(user) {
    if (!user || typeof user !== "object") {
        throw new Error("Cannot save invalid user in IndexedDB.");
    }

    const db = await openIndexedDB();
    const transaction = db.transaction(storeName, "readwrite");
    const store = transaction.objectStore(storeName);

    const userToSave = { ...user };

    if (userToSave.userId != null) {
        userToSave.id = userToSave.userId;
        delete userToSave.userId;
    }

    if (!userToSave.id) {
        userToSave.id = crypto.randomUUID();
    }

    return new Promise((resolve, reject) => {
        const request = store.put(userToSave);
        request.onsuccess = () => resolve();
        request.onerror = e => reject("Failed to save/update user: " + e.target.error);
    });
}

document.getElementById("emailForm").addEventListener("submit", async function (e) {
    e.preventDefault();

    const email = document.getElementById("email").value.trim();
    if (!email) return;

    const otp = generateOTP();
    const submitBtn = document.getElementById("submitBtn");
    const loader = document.getElementById("loader");

    loader.innerText = "Sending verification code...";
    loader.style.display = "block";
    submitBtn.disabled = true;

    try {
        const response = await fetch("signup_backend.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                email: email,
                otp: otp,
                action: "send_verification_otp"
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

        if (data.exists && data.user) {
            const userWithOtp = {
                ...data.user,
                user_email_address: data.user.user_email_address || email,
                otp: otp,
                otp_verified: false,
                otp_created_at: new Date().toISOString()
            };

            await saveOrUpdateUserInIndexedDB(userWithOtp);

            window.location.href = "verify-otp.php?email=" + encodeURIComponent(email);
        } else {
            alert(data.message || "Email not found. Please sign up first.");
        }
    } catch (err) {
        console.error(err);
        alert("Something went wrong. Try again.");
    } finally {
        loader.style.display = "none";
        submitBtn.disabled = false;
    }
});