const sideNav = document.getElementById('sideNav'),
    overlay = document.getElementById('overlay'),
    menuBtn = document.getElementById('menuBtn'),
    closeNav = document.getElementById('closeNav'),
    datePills = document.querySelectorAll('.date-pill'),
    quickActions = document.querySelectorAll('[data-action]'),
    darkModeBtn = document.getElementById('darkModeBtn');

function openSideNav() {
    sideNav.classList.add('active');
    overlay.classList.add('active')
}

function closeSideNav() {
    sideNav.classList.remove('active');
    overlay.classList.remove('active')
}
menuBtn?.addEventListener('click', openSideNav);
closeNav?.addEventListener('click', closeSideNav);
overlay?.addEventListener('click', closeSideNav);
datePills.forEach(p => p.addEventListener('click', () => {
    datePills.forEach(i => i.classList.remove('active'));
    p.classList.add('active')
}));
quickActions.forEach(b => b.addEventListener('click', () => alert(`${b.dataset.action} section selected`)));
darkModeBtn?.addEventListener('click', () => document.body.classList.toggle('dark-mode'));

AOS.init();
const INACTIVITY_TIME = 20 * 60 * 1000;
let inactivityTimer;

function resetTimer() {
    clearTimeout(inactivityTimer);
    inactivityTimer = setTimeout(logoutUser, INACTIVITY_TIME);
}

function logoutUser() {
    window.location.href = "./logout";
}

window.onload = resetTimer;
document.onmousemove = resetTimer;
document.onkeydown = resetTimer;
document.ontouchstart = resetTimer;
document.onscroll = resetTimer;

(function () {
    const DB_NAME = "stafflinks";
    const STORE_NAME = "tbl_team_account";
    const CURRENT_USER_ID = null;

    function setUserInfo(user) {
        document.querySelector(".user-info .name").textContent = user?.user_fullname || "N/A";
        document.querySelector(".user-info .email").textContent = user?.user_email_address || "N/A";
        document.querySelector(".user-info .phone").textContent = user?.user_phone_number || "N/A";
    }

    function clearUserInfoWithMessage(msg) {
        document.querySelector(".user-info .name").textContent = msg;
        document.querySelector(".user-info .email").textContent = "";
        document.querySelector(".user-info .phone").textContent = "";
    }

    function openDatabase() {
        const req = indexedDB.open(DB_NAME);

        req.onerror = function (evt) {
            console.error("IndexedDB open error:", evt.target.error);
            clearUserInfoWithMessage("DB open error");
        };

        req.onsuccess = function (evt) {
            const db = evt.target.result;

            if (!db.objectStoreNames.contains(STORE_NAME)) {
                clearUserInfoWithMessage("Store not found");
                return;
            }

            try {
                const tx = db.transaction(STORE_NAME, "readonly");
                const store = tx.objectStore(STORE_NAME);

                if (CURRENT_USER_ID) {
                    const getReq = store.get(CURRENT_USER_ID);
                    getReq.onsuccess = function () {
                        if (getReq.result) {
                            setUserInfo(getReq.result);
                        } else {
                            fetchFirstRecord(store);
                        }
                    };
                    getReq.onerror = function (e) {
                        fetchFirstRecord(store);
                    };
                } else {
                    if (typeof store.getAll === "function") {
                        const getAllReq = store.getAll();
                        getAllReq.onsuccess = function () {
                            const users = getAllReq.result;
                            if (users.length === 0) {
                                clearUserInfoWithMessage("No users found");
                                return;
                            }
                            setUserInfo(users[0]);
                        };
                        getAllReq.onerror = function (e) {
                            fetchFirstRecord(store);
                        };
                    } else {
                        fetchFirstRecord(store);
                    }
                }

                tx.oncomplete = function () {
                    db.close();
                };
            } catch (err) {
                clearUserInfoWithMessage("Transaction error");
            }
        };

        req.onupgradeneeded = function (evt) { };
    }

    function fetchFirstRecord(store) {
        const cursorReq = store.openCursor();
        cursorReq.onsuccess = function (e) {
            const cursor = e.target.result;
            if (cursor) {
                setUserInfo(cursor.value);
            } else {
                clearUserInfoWithMessage("No users found");
            }
        };
        cursorReq.onerror = function (e) {
            clearUserInfoWithMessage("Cursor error");
        };
    }

    if (!window.indexedDB) {
        clearUserInfoWithMessage("IndexedDB not supported");
    } else {
        window.addEventListener("DOMContentLoaded", openDatabase);
    }
})();

let lastTouchEnd = 0;
document.addEventListener('touchend', function (event) {
    const now = new Date().getTime();
    if (now - lastTouchEnd <= 300) {
        event.preventDefault();
    }
    lastTouchEnd = now;
}, false);

['gesturestart', 'gesturechange', 'gestureend'].forEach(evt => {
    document.addEventListener(evt, function (e) {
        e.preventDefault();
    });
});

function resetZoom() {
    const viewport = document.querySelector('meta[name="viewport"]');
    if (viewport) {
        viewport.setAttribute('content', 'width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no');
    }
}

window.addEventListener('resize', resetZoom);
window.addEventListener('orientationchange', resetZoom);

function updateClock() {
    document.getElementById('topClock').textContent = new Date().toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}
setInterval(updateClock, 1000);
updateClock();

document.addEventListener("gesturestart", function (e) {
    e.preventDefault();
    document.querySelector("meta[name=viewport]").setAttribute(
        "content",
        "width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"
    );
});

$(document).ready(function () {
    $("#btn-back").click(function () {
        window.history.back();
    });

    $("#menuBtn").click(function () {
        $("#sideNav, #overlay").toggleClass("active");
    });

    $("#overlay").click(function () {
        $("#sideNav, #overlay").removeClass("active");
    });

    var currentPath = window.location.pathname.split("/").pop();
    $("a[href]").each(function () {
        var linkPath = $(this).attr("href").split("/").pop();
        if (linkPath === currentPath) {
            $(this).css("background", "#E3C5B2");
        }
    });

});

/* -----------------------------
Profile / Avatar code
----------------------------- */
function createProfileImage(fullName, clientDp = '', diameter = 100) {
    const normalizedPath = normalizeClientDp(clientDp);
    const imageUrl = getClientDpUrl(normalizedPath);

    if (!imageUrl) return null;

    const img = document.createElement('img');
    img.src = imageUrl;
    img.alt = fullName || 'Client profile picture';
    img.style.width = `${diameter}px`;
    img.style.height = `${diameter}px`;
    img.style.minWidth = `${diameter}px`;
    img.style.minHeight = `${diameter}px`;
    img.style.borderRadius = '50%';
    img.style.objectFit = 'cover';
    img.style.display = 'block';
    img.style.margin = 'auto';
    img.style.marginBottom = '5px';
    img.style.background = '#eee';

    return img;
}

function createClientProfileAvatar(fullName, clientDp = '', diameter = 100) {
    const profileImage = createProfileImage(fullName, clientDp, diameter);

    if (!profileImage) {
        return createInitialsCircle(fullName, 2, diameter);
    }

    profileImage.onerror = function () {
        const fallback = createInitialsCircle(fullName, 2, diameter);
        fallback.id = 'clientInitials';
        profileImage.replaceWith(fallback);
    };

    return profileImage;
}

function renderClientProfileImage(client) {
    const firstName = client.client_first_name || '';
    const lastName = client.client_last_name || '';
    const fullName = `${firstName} ${lastName}`.trim() || '--';

    const currentAvatar = document.getElementById('clientInitials');
    if (!currentAvatar) return;

    const avatarNode = createClientProfileAvatar(
        fullName,
        client.client_dp || client.client_dp_url || '',
        100
    );

    avatarNode.id = 'clientInitials';
    currentAvatar.replaceWith(avatarNode);
}
/* -----------------------------
   End Profile / Avatar code
----------------------------- */