<?php require_once('header-log.php'); ?>

<style>
    .btn-pop {
        transform: scale(1.2);
        transition: transform 0.15s ease;
    }
</style>

<div class="mt-4" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card p-4 text-center mt-3">
        <h3 class="mb-3 fw-bold">Create PIN</h3>
        <input
            style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
            placeholder="••••"
            type="text" maxlength="4" id="pin"
            class="pin-input form-control-plaintext mb-4" readonly>

        <div class="keypad d-grid gap-2 mt-3" id="keypad">
            <div class="row">
                <div class="col-4"><button class="btn btn-light" data-num="1">1</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="2">2</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="3">3</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button class="btn btn-light" data-num="4">4</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="5">5</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="6">6</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button class="btn btn-light" data-num="7">7</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="8">8</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="9">9</button></div>
            </div>
            <div class="row">
                <div class="col-4"><button class="btn btn-clear" id="clearPin">C</button></div>
                <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                <div class="col-4"><button class="btn btn-login" id="savePin"><i class="bi bi-box-arrow-in-right"></i></button></div>
            </div>
        </div>
    </div>
</div>

<script src="./js/create_pin.js?v=<?php echo time(); ?>"></script>

<?php require_once('footer-log.php'); ?>