<style>
    #loadingOverlay {
        position: fixed;
        z-index: 9999;
        inset: 0;
        background: rgba(255, 255, 255, 0.8);
        backdrop-filter: blur(4px);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 15px;
    }

    #loadingOverlay img {
        width: 350px;
        height: 350px;
    }

    #loadingOverlay h4 {
        margin: 0;
        font-weight: bold;
        color: #333;
    }
</style>

<div id="loadingOverlay">
    <img src="./images/logo-gif.gif" alt="Loading...">
    <h1 class="fw-bold">Starting Shift...</h1>
</div>

<script src="./js/checkin_geolocation.js?v=<?php echo time(); ?>"></script>