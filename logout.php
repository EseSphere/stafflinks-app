 <script>
(function() {
    // ── 1. Clear all StaffLinks session keys from sessionStorage ──────────
    sessionStorage.removeItem('loggedInUser');
    sessionStorage.removeItem('loggedInUserId');

    // ── 2. Full wipe — catches any other keys written in the future ───────
    //    sessionStorage.clear() removes EVERYTHING stored for this origin,
    //    ensuring no stale data from the previous user lingers.
    sessionStorage.clear();

    // ── 3. Redirect to the login page ─────────────────────────────────────
    //    replace() is used instead of href so the logout page itself is
    //    removed from browser history — pressing Back won't return here.
    window.location.replace('./');
})();
 </script>