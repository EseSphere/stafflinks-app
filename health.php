<?php include_once 'header.php'; ?>

<div class="main-wrapper container">
    <div class="row gutters-sm">
        <!-- Client Profile Horizontal Layout -->
        <?php require_once 'client-profile-extension.php'; ?>

        <!-- Client Info & Stats -->
        <div class="col-md-12">
            <div class="card p-3">
                <h5>Health</h5>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">NHS Number</div>
                    <div style="color: red; font-weight:800;" class="col-sm-8" id="nhsNumber">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Allergies</div>
                    <div class="col-sm-8" id="allergies">Yes</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">GP Phone</div>
                    <div class="col-sm-8" id="gpPhone">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">GP Address</div>
                    <div class="col-sm-8" id="gpAddress">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Pharmancy Phone</div>
                    <div class="col-sm-8" id="locationSupport">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Medicine support</div>
                    <div class="col-sm-8" id="medicine">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">GP Name</div>
                    <div class="col-sm-8" id="gpName">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">GP Email</div>
                    <div class="col-sm-8" id="gpEmail">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Pharmancy</div>
                    <div class="col-sm-8" id="pharmancy">No</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Pharmancy Address</div>
                    <div class="col-sm-8" id="pharmancyAdd">No</div>
                </div>
            </div>

            <hr>
            <?php require_once 'highlight-extention.php'; ?>
        </div>
    </div>
</div>
<script src="./js/sync_health.js?v=<?php echo time(); ?>"></script>
<script src="./js/health.js?v=<?php echo time(); ?>"></script>
<?php include_once 'footer.php'; ?>