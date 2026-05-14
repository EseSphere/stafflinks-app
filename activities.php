<?php include_once 'header.php'; ?>
<link rel="stylesheet" href="css/style3.css">

<style>
    .care-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 15px;
        margin-bottom: 10px;
        border-radius: 12px;
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }

    .care-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .care-icon {
        font-size: 1.5rem;
        margin-right: 10px;
    }

    .status-not-updated {
        font-weight: 500;
        font-size: 0.9rem;
        padding: 3px 8px;
        border-radius: 12px;
        background-color: #7f8c8d;
    }

    #prnModal .modal-body ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    #prnModal .modal-body li {
        padding: 8px 12px;
        margin-bottom: 6px;
        border-radius: 8px;
        background-color: #f8f9fa;
        font-weight: 500;
        display: flex;
        align-items: center;
    }

    #clientInitials {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }
</style>

<div class="main-wrapper container">
    <?php require_once 'client-profile-extension.php'; ?>

    <div class="card p-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h4 class="fs-4">Activities</h4>
            <button class="btn btn-warning prn-btn text-end" data-bs-toggle="modal" data-bs-target="#prnModal" style="display:inline-block; width:120px;">
                <i class="bi bi-bandaid"></i> PRN
                <span class="prn-badge" id="prnCount" style="display:none;">0</span>
            </button>
        </div>
        <hr>
        <div id="careActivitiesContainer"></div>
    </div>

    <div class="col-md-12 mt-3 mb-5">
        <a href="" id="continueBtn" class="btn btn-primary btn-lg">Continue</a>
    </div>

    <?php require_once 'highlight-extention.php'; ?>
</div>

<div class="modal fade" id="prnModal" tabindex="-1" aria-labelledby="prnModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">PRN Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">Log PRN medication or task here.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-primary" id="logPRNBtn">Log PRN</a>
            </div>
        </div>
    </div>
</div>

<script src="./js/activities.js?v=<?php echo time(); ?>"></script>

<?php include_once 'footer.php'; ?>