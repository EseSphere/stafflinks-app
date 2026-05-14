<!--Care Plan-->
<?php include_once 'header.php'; ?>

<div class="main-wrapper container">
    <div class="row gutters-sm">
        <!-- Client Profile Horizontal Layout -->
        <?php require_once 'client-profile-extension.php'; ?>

        <!-- Client Info & Stats -->
        <div class="col-md-12">
            <div class="card p-3">
                <h5>Care Plan</h5>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Phone:</div>
                    <div class="col-sm-8" id="clientPhone">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Room / Key Safe:</div>
                    <div class="col-sm-8" id="clientKeySafe">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Address:</div>
                    <a href="#" target="_blank" id="clientAddress" class="text-decoration-none text-dark">
                        <div class="col-sm-8">Loading...</div>
                    </a>
                </div>
                <hr>
                <!-- Assigned Carers Panel -->
                <div class="col-md-12 mt-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h5 class="mb-0">Assigned Carers</h5>
                    </div>
                    <div class="row" id="carersContainer"></div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Email:</div>
                    <div class="col-sm-8" id="clientEmail">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">City:</div>
                    <div class="col-sm-8" id="clientCity">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Pronoun:</div>
                    <div class="col-sm-8" id="clientPronoun">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Dob:</div>
                    <div class="col-sm-8" id="dateofbirth">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Condition:</div>
                    <div class="col-sm-8" id="condition">Loading...</div>
                </div>
                <hr>
                <div class="row">
                    <div class="col-sm-4 fw-bold">Gender:</div>
                    <div class="col-sm-8" id="gender">Loading...</div>
                </div>
            </div>
        </div>

        <div class="quick-stats mt-3">
            <div class="stat alert alert-success">
                <h6>Total Carers</h6><span id="totalCarers">--</span>
            </div>
            <div class="stat alert alert-danger">
                <h6>Service Users</h6><span id="visitsToday">--</span>
            </div>
            <div class="stat alert alert-primary">
                <h6>Run Name</h6><span id="pendingTasks">--</span>
            </div>
        </div>

        <!-- Assessment Links as Separate Cards -->
        <div class="col-md-12 mt-3">
            <div class="card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h5 class="mb-0">Assessments</h5>
                    <button class="btn btn-danger btn-sm"><i class="bi bi-file-earmark-pdf"></i> Report</button>
                </div>
                <div id="assessmentCards"></div>
            </div>
        </div>
        <hr>
        <!--client highlights-->
        <?php require_once 'highlight-extention.php'; ?>
    </div>

    <!-- Start shift -->
    <div style="position: fixed; top:255px; right:20px;" class="ms-auto">
        <a href="#" id="startShiftBtn" class="btn btn-primary">
            <i class="bi bi-play-circle"></i> Start
        </a>
    </div>
</div>
<script src="./js/sync_care_plan.js?v=<?php echo time(); ?>"></script>
<script src="./js/care_plan.js?v=<?php echo time(); ?>"></script>

<?php include_once 'footer.php'; ?>