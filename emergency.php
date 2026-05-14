<?php include_once 'header.php'; ?>

<div class="main-wrapper container">
    <div class="row gutters-sm">
        <!-- Client Profile Horizontal Layout -->
        <?php require_once 'client-profile-extension.php'; ?>

        <!-- Future Planning Info -->
        <div class="col-md-12">
            <div class="card p-3">
                <h5>Emergency</h5>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Does he/she have capacity to make decisions related to their health and wellbeing?</div>
                    <div class="col-sm-8" id="capacityDecision">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Health and Welfare LPA</div>
                    <div class="col-sm-8" id="healthLPA">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Property and Financial Affairs LPA</div>
                    <div class="col-sm-8" id="propertyLPA">Loading...</div>
                </div>
                <hr>
                <div class="row" style="color:red;">
                    <div class="col-sm-4 fw-bold">Do Not Attempt Cardiopulmonary Resuscitation (DNACPR)</div>
                    <div class="col-sm-8" id="dnacpr" style="color:red; font-weight:800;">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Advance Decision to Refuse Treatment (ADRT / Living Will)</div>
                    <div class="col-sm-8" id="adrt">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Recommended Summary Plan for Emergency Care and Treatment (ReSPECT)</div>
                    <div class="col-sm-8" id="respect">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Where is it kept?</div>
                    <div class="col-sm-8" id="location">Loading...</div>
                </div>
            </div>

            <hr>
            <?php require_once 'highlight-extention.php'; ?>
        </div>
    </div>
</div>

<script src="./js/sync_emergency.js?v=<?php echo time(); ?>"></script>
<script src="./js/emergency.js?v=<?php echo time(); ?>"></script>

<?php include_once 'footer.php'; ?>