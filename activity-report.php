<?php include_once 'header.php'; ?>

<div class="main-wrapper container">

    <?php require_once 'client-profile-extension.php'; ?>

    <div class="card p-3 mb-3">
        <h4>Submit Report</h4>
        <hr>
        <form style="font-size: 18px;" method="post" action="./activities" id="activityReportForm">
            <div class="mb-3">
                <label class="form-label fs-5">Task / Medication</label>
                <p class="fw-bold fs-4" id="selectedActivity">Loading...</p>
                <p style="margin-top: -15px;" class="fw-semibold fs-5" id="activityDescription">Loading...</p>
            </div>

            <div class="mb-3">
                <label class="form-label fs-5">Status</label>
                <div class="d-flex flex-wrap gap-2" id="statusContainer"></div>
            </div>

            <div class="mb-3">
                <label for="reportText" class="form-label fs-5">Report / Notes</label>
                <textarea class="form-control fs-5" id="reportText" rows="4" placeholder="Enter details here"></textarea>
            </div>

            <a style="width: 100px; border-radius:3px;" href="./activities" id="continueBtn" class="btn btn-info text-decoration-none">Copy</a>
            <button style="width: 100px; border-radius:3px;" type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>

    <div id="previousReportsContainer" class="mb-3"></div>

    <?php require_once 'highlight-extention.php'; ?>
</div>

<script src="./js/activity_report.js?v=<?php echo time(); ?>"></script>

<?php include_once 'footer.php'; ?>