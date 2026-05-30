<script>
// At the top of dashboard.js (or any protected page)
requireSession("./index");

// Read the session anywhere
const specialId = sessionStorage.getItem("user_special_Id");
</script>