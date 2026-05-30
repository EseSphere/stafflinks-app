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
    },
    { passive: false }
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

    request.onupgradeneeded = function (event) {
      const db = event.target.result;

      if (!db.objectStoreNames.contains(storeName)) {
        db.createObjectStore(storeName, {
          keyPath: "id",
        });
      }
    };

    request.onsuccess = function (event) {
      const db = event.target.result;

      if (db.objectStoreNames.contains(storeName)) {
        resolve(db);
        return;
      }

      const newVersion = db.version + 1;
      db.close();

      const upgradeRequest = indexedDB.open(dbName, newVersion);

      upgradeRequest.onupgradeneeded = function (event) {
        const upgradedDb = event.target.result;

        if (!upgradedDb.objectStoreNames.contains(storeName)) {
          upgradedDb.createObjectStore(storeName, {
            keyPath: "id",
          });
        }
      };

      upgradeRequest.onsuccess = function (event) {
        resolve(event.target.result);
      };

      upgradeRequest.onerror = function (event) {
        reject(event.target.error);
      };
    };

    request.onerror = function (event) {
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

    getAllRequest.onerror = function () {
      alert("Failed to read user data from IndexedDB");
    };

    getAllRequest.onsuccess = function () {
      const users = getAllRequest.result;

      if (!users || users.length === 0) {
        alert("No user found. Please create a PIN first.");
        window.location.href = "./signup";
        return;
      }

      const matchedUser = users.find(
        (u) => u.user_password && u.user_password === enteredHash
      );

      if (!matchedUser) {
        shakeCard();
        clearPin();
        return;
      }

      sessionStorage.setItem("loggedInUserId", matchedUser.id);
      sessionStorage.setItem(
        "loggedInUser",
        JSON.stringify({
          id: matchedUser.id,
          user_fullname: matchedUser.user_fullname,
          user_email_address: matchedUser.user_email_address,
          user_phone_number: matchedUser.user_phone_number,
          team_dp: matchedUser.team_dp,
          user_special_Id: matchedUser.user_special_Id,
          col_company_Id: matchedUser.col_company_Id,
          col_cookies_identifier: matchedUser.col_cookies_identifier,
        })
      );

      const specialId = encodeURIComponent(matchedUser.user_special_Id || "");
      const companyId = encodeURIComponent(matchedUser.col_company_Id || "");
      window.location.href = `./dashboard?user_special_Id=${specialId}&col_company_Id=${companyId}`;
    };
  } catch (error) {
    console.error("IndexedDB error:", error);
    alert("Failed to open IndexedDB");
  }
}