const pinInput = document.getElementById("pin");
    const keypad = document.getElementById("keypad");
    const clearBtn = document.getElementById("clearPin");
    const saveBtn = document.getElementById("savePin");
    let actualPin = "";

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

    function animateButton(btn) {
        btn.classList.add("btn-pop");
        setTimeout(() => btn.classList.remove("btn-pop"), 150);
    }

    keypad.querySelectorAll("[data-num]").forEach(btn => {
        fastClickHandler(btn, () => pressNum(btn.getAttribute("data-num")));
    });

    fastClickHandler(clearBtn, clearPin);
    fastClickHandler(saveBtn, savePin);

    function pressNum(num) {
        if (actualPin.length < 4) {
            actualPin += num;
            pinInput.value = "•".repeat(actualPin.length - 1) + num;
            setTimeout(() => {
                pinInput.value = "•".repeat(actualPin.length);
            }, 300);
        }
        if (actualPin.length === 4) {
            setTimeout(() => savePin(), 200);
        }
    }

    function clearPin() {
        actualPin = "";
        pinInput.value = "";
    }

    async function hashPin(pin) {
        const encoder = new TextEncoder();
        const data = encoder.encode(pin);
        const hashBuffer = await crypto.subtle.digest('SHA-256', data);
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        return hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    }

    async function savePin() {
        if (actualPin.length !== 4) {
            alert("Please enter a 4-digit PIN");
            return;
        }

        const encryptedPin = await hashPin(actualPin);
        const dbRequest = indexedDB.open("stafflinks", 1);

        dbRequest.onerror = () => alert("Failed to open IndexedDB");

        dbRequest.onsuccess = (event) => {
            const db = event.target.result;
            const transaction = db.transaction("tbl_team_account", "readwrite");
            const store = transaction.objectStore("tbl_team_account");
            const getAllRequest = store.getAll();

            getAllRequest.onsuccess = function() {
                const users = getAllRequest.result;
                if (users.length === 0) {
                    alert("No user found in IndexedDB");
                    return;
                }

                const user = users[0];
                user.user_password = encryptedPin;
                user.status2 = "active";

                const updateRequest = store.put(user);
                updateRequest.onsuccess = () => window.location.href = "./";
                updateRequest.onerror = () => alert("Failed to save PIN. Try again.");
            };

            getAllRequest.onerror = () => alert("Failed to read user data from IndexedDB");
        };
    }