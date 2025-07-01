<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Project - DepEd BAC Tracking System</title>
    <link rel="stylesheet" href="assets/css/edit_project.css">
    <link rel="stylesheet" href="assets/css/background.css">
</head>
<body class="<?php echo $isAdmin ? 'admin-view' : 'user-view'; ?>">
    <?php include 'header.php'; ?>

    <script>
        // Pass PHP variables to JavaScript
        window.firstUnsubmittedStageName = <?php echo json_encode($firstUnsubmittedStageName); ?>;
        window.showSuccessToast = <?php echo isset($_SESSION['stageSuccess']) ? 'true' : 'false'; ?>;
    </script>

    <div class="dashboard-container">
        <a href="<?php echo url('index.php'); ?>" class="back-btn">&larr; Back to Dashboard</a>

        <h1>Edit Project</h1>

        <?php
            if (isset($errorHeader)) { echo "<p class='project-error-message'>$errorHeader</p>"; }
            if (isset($successHeader)) { echo "<p class='project-success-message'>$successHeader</p>"; }
            if (isset($stageError)) { echo "<p class='project-error-message'>$stageError</p>"; }
        ?>

        <div class="project-info-card">
            <h3>Project Information</h3>
            <?php if ($isAdmin): ?>
                <form action="edit_project.php?projectID=<?php echo $projectID; ?>" method="post" class="project-form">
            <?php endif; ?>
            
            <div class="project-info-grid">
                <div class="project-info-item">
                    <label for="prNumber">PR Number</label>
                    <?php if ($isAdmin): ?>
                        <input type="text" name="prNumber" id="prNumber" value="<?php echo htmlspecialchars($project['prNumber']); ?>" required>
                    <?php else: ?>
                        <div class="value"><?php echo htmlspecialchars($project['prNumber']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="project-info-item">
                    <label>Mode of Procurement</label>
                    <div class="value"><?php echo htmlspecialchars($project['MoPDescription'] ?? 'N/A'); ?></div>
                </div>

                <div class="project-info-item project-details-full">
                    <label for="projectDetails">Project Details</label>
                    <?php if ($isAdmin): ?>
                        <textarea name="projectDetails" id="projectDetails" rows="3" required><?php echo htmlspecialchars($project['projectDetails']); ?></textarea>
                    <?php else: ?>
                        <div class="value"><?php echo htmlspecialchars($project['projectDetails']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="project-info-item">
                    <label>Total ABC</label>
                    <?php if ($isAdmin): ?>
                        <input type="number" name="totalABC" id="totalABC"
                            value="<?php echo htmlspecialchars($project['totalABC']); ?>"
                            required min="0" step="1">
                    <?php else: ?>
                        <div class="value">
                            <?php echo isset($project['totalABC']) ? '₱' . number_format($project['totalABC']) : 'N/A'; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="project-info-item">
                    <label>Project Status</label>
                    <div class="value">
                        <span class="status-badge <?php echo $noticeToProceedSubmitted ? 'finished' : 'in-progress'; ?>">
                            <?php echo $noticeToProceedSubmitted ? 'Finished' : 'In Progress'; ?>
                        </span>
                    </div>
                </div>

                <div class="project-info-item">
                    <label>Created By</label>
                    <div class="value"><?php echo htmlspecialchars($project['creator_firstname'] . " " . $project['creator_lastname']); ?><br>
                    <small>Office: <?php echo htmlspecialchars($project['officename'] ?? 'N/A'); ?></small></div>
                </div>

                <div class="project-info-item">
                    <label>Last Accessed By</label>
                    <div class="value">
                        <?php
                        if (!empty($project['lastAccessedBy']) && !empty($project['lastAccessedAt']) && isset($lastAccessedByName) && $lastAccessedByName !== "N/A") {
                            // Get office info for the last accessed user
                            $stmtAccessUserOffice = $pdo->prepare("SELECT o.officename FROM tbluser u LEFT JOIN officeid o ON u.officeID = o.officeID WHERE u.userID = ?");
                            $stmtAccessUserOffice->execute([$project['lastAccessedBy']]);
                            $accessUserOffice = $stmtAccessUserOffice->fetch();
                            $accessOfficeInfo = $accessUserOffice ? htmlspecialchars($accessUserOffice['officename'] ?? 'N/A') : 'N/A';
                            
                            echo $lastAccessedByName . "<br><small>Office: " . $accessOfficeInfo . "</small><br><small>" . date("M d, Y h:i A", strtotime($project['lastAccessedAt'])) . "</small>";
                        } else {
                            echo "Not Available";
                        }
                        ?>
                    </div>
                </div>

                <div class="project-info-item">
                    <label>Date Created</label>
                    <div class="value"><?php echo date("M d, Y h:i A", strtotime($project['createdAt'])); ?></div>
                </div>

                <div class="project-info-item">
                    <label>Last Updated</label>
                    <div class="value">
                        <?php
                        $lastUpdatedInfo = "Not Available";
                        if (!empty($project['editedBy']) && !empty($project['editedAt'])) {
                            $stmtEditUser = $pdo->prepare("SELECT u.firstname, u.lastname, o.officename FROM tbluser u LEFT JOIN officeid o ON u.officeID = o.officeID WHERE u.userID = ?");
                            $stmtEditUser->execute([$project['editedBy']]);
                            $editUser = $stmtEditUser->fetch();
                            if ($editUser) {
                                $editUserFullName = htmlspecialchars($editUser['firstname'] . " " . $editUser['lastname']);
                                $editUserOffice = htmlspecialchars($editUser['officename'] ?? 'N/A');
                                echo $editUserFullName . "<br><small>Office: " . $editUserOffice . "</small><br><small>" . date("M d, Y h:i A", strtotime($project['editedAt'])) . "</small>";
                            } else {
                                echo $lastUpdatedInfo;
                            }
                        } else {
                            echo $lastUpdatedInfo;
                        }
                        ?>
                    </div>
                </div>

                <div class="project-info-item">
                    <label>Program Owner</label>
                    <div class="value">
                        <?php echo htmlspecialchars($project['programOwner'] ?? 'N/A'); ?>
                        <?php if (!empty($project['programOffice'])): ?>
                            <br><small>Office: <?php echo htmlspecialchars($project['programOffice']); ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <?php if ($isAdmin): ?>
                <button type="submit" name="update_project_header" class="update-project-btn">
                    Update Project Information
                </button>
                </form>
            <?php endif; ?>
        </div>

        <div class="stage-management-card">
            <h3>Project Stages Management</h3>
            
            <!-- Stage Selection Dropdown -->
            <div class="stage-dropdown-container" id="stageDropdownSection">
                <form method="post" id="stageDropdownForm">
                    <label for="stageDropdown">Create New Stage</label>
                    <select id="stageDropdown" name="stageName" required>
                        <option value="">-- Select a Stage to Create --</option>
                        <?php foreach ($stagesOrder as $stage): ?>
                            <?php if ($stage !== 'Mode Of Procurement' && !isset($stagesMap[$stage])): ?>
                                <option value="<?php echo htmlspecialchars($stage); ?>"><?php echo htmlspecialchars($stage); ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_stage" onclick="return confirm('Are you sure you want to create this stage?')">Create Stage</button>
                </form>
            </div>

            <!-- Stages Table -->
            <div class="stages-table-container">
                <table id="stagesTable">
                    <thead>
                        <tr>
                        <th class="col-stage">Stage Name</th>
                        <th class="col-created">Date Created</th>
                        <th class="col-approved">Date Approved</th>
                        <th class="col-office">Office</th>
                        <th class="col-remark">Remark</th>
                        <th class="col-status">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Display only Mode Of Procurement and created stages
                    foreach ($stagesOrder as $index => $stage):
                        // Always show Mode Of Procurement
                        if ($stage === 'Mode Of Procurement'):
                    ?>
                        <tr data-stage="<?php echo htmlspecialchars($stage); ?>">
                            <td><?php echo htmlspecialchars($stage); ?></td>
                            <td colspan="4">
                                <div class="readonly-field">
                                    <?php echo htmlspecialchars($project['MoPDescription'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="submit-stage-btn autofilled" disabled>Autofilled</button>
                            </td>
                        </tr>
                    <?php
                            continue;
                        endif;

                        // Only show stages that exist in $stagesMap (i.e., created)
                        if (!isset($stagesMap[$stage])) continue;

                        $safeStage = str_replace(' ', '_', $stage);
                        $currentStageData = $stagesMap[$stage] ?? null;
                        $currentSubmitted = ($currentStageData && $currentStageData['isSubmitted'] == 1);

                        $value_created = ($currentStageData && !empty($currentStageData['createdAt']))
                            ? date("Y-m-d\TH:i", strtotime($currentStageData['createdAt'])) : "";
                        $value_approved = ($currentStageData && !empty($currentStageData['approvedAt']))
                            ? date("Y-m-d\TH:i", strtotime($currentStageData['approvedAt'])) : "";
                        $value_remark = ($currentStageData && !empty($currentStageData['remarks']))
                            ? htmlspecialchars($currentStageData['remarks']) : "";

                        $displayOfficeName = "Not set";
                        if (isset($currentStageData['officeID']) && isset($officeList[$currentStageData['officeID']])) {
                            $displayOfficeName = htmlspecialchars($currentStageData['officeID'] . ' - ' . $officeList[$currentStageData['officeID']]);
                        }
                    ?>
                    <tr data-stage="<?php echo htmlspecialchars($stage); ?>">
                        <td><?php echo htmlspecialchars($stage); ?></td>
                        <td>
                            <input type="datetime-local" value="<?php echo $value_created; ?>" disabled>
                        </td>
                        <form method="post" class="stage-form-inline">
                            <input type="hidden" name="stageName" value="<?php echo htmlspecialchars($stage); ?>">
                            <td>
                                <?php if ($currentSubmitted): ?>
                                    <input type="datetime-local" value="<?php echo $value_approved; ?>" disabled>
                                <?php else: ?>
                                    <input type="datetime-local" name="approvedAt" value="<?php echo $value_approved; ?>" required>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="readonly-office-field"><?php echo $displayOfficeName; ?></div>
                            </td>
                            <td>
                                <?php if ($currentSubmitted): ?>
                                    <input type="text" value="<?php echo $value_remark; ?>" disabled>
                                <?php else: ?>
                                    <input type="text" name="remark" value="<?php echo $value_remark; ?>" placeholder="Remarks (optional)">
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($currentSubmitted): ?>
                                    <button type="button" class="submit-stage-btn completed" disabled>Submitted</button>
                                <?php else: ?>
                                    <button type="submit" name="submit_stage" class="submit-stage-btn available" onclick="return confirm('Are you sure you want to submit this stage?')">Submit</button>
                                <?php endif; ?>
                            </td>
                        </form>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </div>

        <div class="card-view">
            <?php foreach ($stagesOrder as $index => $stage):
                if ($stage === 'Mode Of Procurement') continue;
                
                $safeStage = str_replace(' ', '_', $stage);
                $currentStageData = $stagesMap[$stage] ?? null;
                $currentSubmitted = ($currentStageData && $currentStageData['isSubmitted'] == 1);

                $value_created = ($currentStageData && !empty($currentStageData['createdAt'])) ? date("Y-m-d\TH:i", strtotime($currentStageData['createdAt'])) : "";
                $value_approved = ($currentStageData && !empty($currentStageData['approvedAt'])) ? date("Y-m-d\TH:i", strtotime($currentStageData['approvedAt'])) : "";
                $value_remark = ($currentStageData && !empty($currentStageData['remarks'])) ? htmlspecialchars($currentStageData['remarks']) : "";

                // Only show office information for submitted stages
                $displayOfficeName = "Not set";
                if ($currentSubmitted && isset($currentStageData['officeID']) && isset($officeList[$currentStageData['officeID']])) {
                    $displayOfficeName = htmlspecialchars($currentStageData['officeID'] . ' - ' . $officeList[$currentStageData['officeID']]);
                }
            ?>
            <div class="stage-card">
                <h4><?php echo htmlspecialchars($stage); ?></h4>

                <label>Created At:</label>
                <input type="datetime-local" value="<?php echo $value_created; ?>" disabled>

                <label>Approved At:</label>
                <input type="datetime-local" value="<?php echo $value_approved; ?>" disabled>

                <label>Office:</label>
                <?php if ($currentSubmitted): ?>
                    <div class="readonly-office-field">
                        <?php echo $displayOfficeName; ?>
                    </div>
                <?php else: ?>
                    <span class="stage-not-set">Not set</span>
                <?php endif; ?>

                <label>Remark:</label>
                <input type="text" value="<?php echo $value_remark; ?>" disabled>

                <div class="stage-actions">
                    <?php
                    if ($currentSubmitted) {
                        echo '<button type="button" class="submit-stage-btn completed" disabled>Submitted</button>';
                    } else {
                        echo '<button type="button" class="submit-stage-btn available">Available</button>';
                    }
                    ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Toast Success Notification -->
        <?php if (isset($_SESSION['stageSuccess'])): ?>
        <div id="toast-success" class="toast-success">
            <?php echo htmlspecialchars($_SESSION['stageSuccess']); ?>
        </div>
        <?php unset($_SESSION['stageSuccess']); endif; ?>
    </div>

    <script src="assets/js/edit_project.js"></script>
</body>
</html>