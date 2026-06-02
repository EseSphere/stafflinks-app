<!--index.php code-->
<?php require_once('header-log.php'); ?>

<div class="mt-5" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card text-center" id="pinCard">
        <h3 class="mb-3 fw-bold">Signin</h3>

        <div class="container">
            <input
                style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
                placeholder="••••" type="text" maxlength="4" id="pin" class="pin-input form-control-plaintext mb-4"
                readonly>

            <div class="keypad d-grid gap-1 mt-2" id="keypad">
                <!-- gap-2→gap-1, mt-3→mt-2 -->
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="1">1</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="2">2</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="3">3</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="4">4</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="5">5</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="6">6</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-light" data-num="7">7</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="8">8</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="9">9</button></div>
                </div>
                <div class="row g-0">
                    <div class="col-4"><button class="btn btn-clear" id="clearPin">C</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                    <div class="col-4"><button class="btn btn-login" id="loginBtn"><i
                                class="bi bi-box-arrow-in-right"></i></button></div>
                </div>
            </div>

            <div class="create-account-box">
                <span class="create-account-text">Don't have an account?</span>
                <a href="./signup" class="create-account-btn">
                    Create Account
                    <i class="bi bi-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="./js/login.js?v=<?php echo time(); ?>"></script>
<?php require_once('footer-log.php'); ?>