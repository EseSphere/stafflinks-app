function calculateAge(dob) {
        if (!dob) return '--';
        const birthDate = new Date(dob);
        const today = new Date();
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        const dayDiff = today.getDate() - birthDate.getDate();
        if (monthDiff < 0 || (monthDiff === 0 && dayDiff < 0)) age--;
        return age;
    }

    async function openDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('stafflinks');
            request.onsuccess = e => resolve(e.target.result);
            request.onerror = e => reject(e.target.error);
        });
    }

    function getQueryParam(param) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(param);
    }

    function createInitialsCircle(fullName, fontSize = 2, diameter = 100) {
        if (!fullName) fullName = '--';
        const names = fullName.split(' ');
        const initials = ((names[0]?.charAt(0) || '') + (names[1]?.charAt(0) || '')).toUpperCase();
        const colors = ["#6c757d", "#0d6efd", "#198754", "#dc3545", "#ffc107", "#6f42c1", "#fd7e14"];
        const charCodeSum = (initials.charCodeAt(0) || 0) + (initials.charCodeAt(1) || 0);
        const bgColor = colors[charCodeSum % colors.length];

        const div = document.createElement('div');
        div.textContent = initials;
        div.style.width = `${diameter}px`;
        div.style.height = `${diameter}px`;
        div.style.borderRadius = '50%';
        div.style.display = 'flex';
        div.style.alignItems = 'center';
        div.style.justifyContent = 'center';
        div.style.fontSize = `${fontSize}rem`;
        div.style.fontWeight = 'bold';
        div.style.color = 'white';
        div.style.backgroundColor = bgColor;
        div.style.marginBottom = '5px';
        return div;
    }

    async function getClientDetails(uryyToeSS4) {
        const db = await openDB();
        if (!db.objectStoreNames.contains('tbl_general_client_form')) return null;

        return new Promise((resolve, reject) => {
            const tx = db.transaction('tbl_general_client_form', 'readonly');
            const store = tx.objectStore('tbl_general_client_form');
            const req = store.getAll();
            req.onsuccess = e => {
                const client = e.target.result.find(c => c.uryyToeSS4 == uryyToeSS4);
                resolve(client || null);
            };
            req.onerror = e => reject(e.target.error);
        });
    }

    async function getFuturePlanning(uryyToeSS4) {
        const db = await openDB();
        if (!db.objectStoreNames.contains('tbl_future_planning')) return null;

        return new Promise((resolve, reject) => {
            const tx = db.transaction('tbl_future_planning', 'readonly');
            const store = tx.objectStore('tbl_future_planning');
            const req = store.getAll();
            req.onsuccess = e => {
                const plan = e.target.result.find(p => p.uryyToeSS4 == uryyToeSS4);
                resolve(plan || null);
            };
            req.onerror = e => reject(e.target.error);
        });
    }

    async function renderClientProfile() {
        const uryyToeSS4 = getQueryParam('uryyToeSS4');
        if (!uryyToeSS4) return;

        const client = await getClientDetails(uryyToeSS4);
        if (client) {
            const firstName = client.client_first_name || '';
            const lastName = client.client_last_name || '';
            const initialsDiv = document.getElementById('clientInitials');
            const initialsCircle = createInitialsCircle(`${firstName} ${lastName}`, 2, 100);
            initialsDiv.replaceWith(initialsCircle);
            initialsCircle.id = 'clientInitials';

            document.getElementById('clientName').textContent = `${firstName} ${lastName}`;
            document.getElementById('clientAge').textContent = `Age: ${calculateAge(client.client_date_of_birth)}`;
            document.getElementById('dnacprBtn').href = `health.php?uryyToeSS4=${client.uryyToeSS4}`;
            document.getElementById('allergiesBtn').href = `emergency.php?uryyToeSS4=${client.uryyToeSS4}`;

            const highlightDiv = document.getElementById('highlight');
            if (client.client_highlights) {
                const paragraphs = client.client_highlights.split(/\n\s*\n/);
                highlightDiv.innerHTML = paragraphs.map(p => `<p>${p.trim().replace(/\n/g,'<br>')}</p>`).join('');
            } else {
                highlightDiv.innerHTML = '<p>No highlights available.</p>';
            }
        } else {
            document.getElementById('clientName').textContent = '--';
            document.getElementById('clientAge').textContent = 'Age: --';
            document.getElementById('highlight').innerHTML = '<p>No highlights available.</p>';
        }
    }

    async function renderFuturePlanning() {
        const uryyToeSS4 = getQueryParam('uryyToeSS4');
        if (!uryyToeSS4) return;

        const plan = await getFuturePlanning(uryyToeSS4);
        document.getElementById('capacityDecision').textContent = plan?.col_first_box || 'No';
        document.getElementById('healthLPA').textContent = plan?.col_second_box || 'No';
        document.getElementById('propertyLPA').textContent = plan?.col_third_box || 'No';
        document.getElementById('dnacpr').textContent = plan?.col_fourt_box || 'No';
        document.getElementById('adrt').textContent = plan?.col_fift_box || 'No';
        document.getElementById('respect').textContent = plan?.col_sixth_box || 'No';
        document.getElementById('location').textContent = plan?.col_seventh_box || 'No';
    }

    async function renderAllData() {
        await renderClientProfile();
        await renderFuturePlanning();
    }

    renderAllData();