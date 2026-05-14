const pinInput = document.getElementById("pin");
const keypad = document.getElementById("keypad");
const clearBtn = document.getElementById("clearPin");
const loginBtn = document.getElementById("loginBtn");
const pinCard = document.getElementById("pinCard");
let actualPin = "";

function fastClickHandler(el, callback) {
  el.addEventListener(
    "touchstart",
    (e) => {
      e.preventDefault();
      animateButton(el);
      requestAnimationFrame(callback);
    },
    {
      passive: false,
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

async function login() {
  if (actualPin.length !== 4) {
    alert("Please enter a 4-digit PIN");
    return;
  }

  const enteredHash = await hashPin(actualPin);
  const dbRequest = indexedDB.open("stafflinks");

  dbRequest.onerror = (e) => {
    console.error("IndexedDB error:", e.target.error);
    alert("Failed to open IndexedDB");
  };

  dbRequest.onsuccess = (event) => {
    const db = event.target.result;

    if (!db.objectStoreNames.contains("tbl_team_account")) {
      alert("No user data found. Please create a PIN first.");
      window.location.href = "create-pin.php";
      return;
    }

    const transaction = db.transaction("tbl_team_account", "readonly");
    const store = transaction.objectStore("tbl_team_account");
    const getAllRequest = store.getAll();

    getAllRequest.onerror = () =>
      alert("Failed to read user data from IndexedDB");

    getAllRequest.onsuccess = function () {
      const users = getAllRequest.result;
      if (users.length === 0) {
        alert("No user found. Please create a PIN first.");
        window.location.href = "create-pin.php";
        return;
      }

      const user = users[0];

      if (!user.user_password) {
        alert("No PIN set. Please create a PIN first.");
        window.location.href = "create-pin.php";
        return;
      }

      if (user.user_password === enteredHash) {
        window.location.href = "dashboard.php";
      } else {
        shakeCard();
        clearPin();
      }
    };
  };
}
