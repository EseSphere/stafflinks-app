<?php require_once('header-log.php'); ?>

<div class="mt-3" data-aos="zoom-in" data-aos-duration="1000" style="z-index:1000;">
    <div style="border: none; z-index:1000;" class="p-4 text-center">
        <h3 class="mb-3 fw-bold">Account Setup</h3>
        <div class="alert alert-info fs-6" style="border-radius: 12px; border-left:10px solid #EB6F46; padding:8px; background-color:#E9E7E0; margin-bottom:40px; text-align:left;">
            Signing in on a new device? Please use the form below to set up your account.
        </div>
        <form id="emailForm" class="w-100 bg-light p-3 rounded">
            <h6 class="fs-5 mb-3 fw-semibold w-100 justify-start items-start text-start">Enter Email</h6>
            <input style="height: 60px;" type="email" id="email" class="form-control mb-2 fs-6" placeholder="Enter your email" required>
            <div class="form-group w-100 mt-2 justify-start items-start text-start">
                <button type="submit" id="submitBtn" style="height: 50px;" class="btn btn-primary">Continue</button>
            </div>
            <div id="loader" class="mt-2" style="display:none; color: #007bff;">Checking account...</div>
        </form>
    </div>
</div>

<script src="./js/signup.js?v=<?php echo time(); ?>"></script>

<?php require_once('footer-log.php'); ?>