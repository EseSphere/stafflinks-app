<?php require_once('header-log.php'); ?>

<div class="mt-5" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card text-center" id="otpCard">
        <h3 class="mb-3 fw-bold">Verify OTP</h3>

        <div class="container">
            <div class="alert alert-info fs-6 g-1 d-flex align-items-center justify-content-center" role="alert"
                style="border-radius: 12px; border-left:10px solid #EB6F46; padding:8px; background-color:#E9E7E0; margin-bottom:20px; text-align:left;">
                Enter the 6-digit verification code sent to your email address.
            </div>

            <input
                style="background-color:inherit !important; color:#000 !important; font-size:3rem; letter-spacing:0.5rem; text-align:center; font-weight:bold;"
                placeholder="••••••" type="text" maxlength="6" id="otp" class="pin-input form-control-plaintext mb-4"
                readonly>

            <div id="messageBox" class="mb-2 fs-6 fw-semibold"></div>

            <div class="keypad d-grid gap-1 mt-2" id="keypad">
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
                    <div class="col-4"><button class="btn btn-clear" id="clearOtp">C</button></div>
                    <div class="col-4"><button class="btn btn-light" data-num="0">0</button></div>
                    <div class="col-4"><button class="btn btn-login" id="verifyOtp">
                            <i class="bi bi-box-arrow-in-right"></i>
                        </button></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="./js/verify-otp.js?v=<?php echo time(); ?>"></script>
<?php require_once('footer-log.php'); ?>