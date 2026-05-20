<?php require_once('header-log.php'); ?>
<style>
    .btn-pop {
        transform: scale(1.2);
        transition: transform 0.15s ease;
    }

    @keyframes shake {
        0% {
            transform: translateX(0);
        }

        20% {
            transform: translateX(-10px);
        }

        40% {
            transform: translateX(10px);
        }

        60% {
            transform: translateX(-10px);
        }

        80% {
            transform: translateX(10px);
        }

        100% {
            transform: translateX(0);
        }
    }

    .shake {
        animation: shake 0.4s;
    }

    .create-account-box {
        margin-top: 2rem;
        padding: 1.25rem;
        border-radius: 18px;
        background: rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.08);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .create-account-text {
        color: #6c757d;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .create-account-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.65rem 1.2rem;
        border-radius: 999px;
        background: #111;
        color: #fff;
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 700;
        transition: all 0.2s ease;
        box-shadow: 0 8px 18px rgba(0, 0, 0, 0.15);
    }

    .create-account-btn:hover {
        color: #fff;
        transform: translateY(-2px);
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2);
    }

    @media (max-width: 576px) {
        .create-account-box {
            padding: 1rem;
        }

        .create-account-btn {
            width: 100%;
        }
    }
</style>

<div class="mt-5" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1;">
    <div style="border: none; height:100vh;" class="card text-center" id="pinCard">
        <h3 class="mb-3 fw-bold">Signin</h3>

        <div class="container">
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
                    <div class="col-4"><button class="btn btn-login" id="loginBtn"><i class="bi bi-box-arrow-in-right"></i></button></div>
                </div>
            </div>

            <div class="create-account-box">
                <span class="create-account-text">Don’t have an account?</span>
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