<?php
// project_tracker.php

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'config.php'; // Ensure your PDO connection is set up correctly
require_once 'url_helper.php';

// Redirect if user is not logged in.
if (!isset($_SESSION['username'])) {
    redirect('login.php');
}

// Get filter status from URL
$filterStatus = $_GET['status'] ?? ''; // Get the 'status' parameter from the URL

// Fetch stage order from reference table
$stmtStageRef = $pdo->query("SELECT stageName FROM stage_reference ORDER BY stageOrder ASC");
$stagesOrder = $stmtStageRef->fetchAll(PDO::FETCH_COLUMN);

// Build the SQL query based on filter status
$sql = "SELECT p.projectID, p.prNumber, p.projectDetails, p.remarks, p.createdAt,
        mop.MoPDescription as mode_of_procurement,
        (SELECT COUNT(*) FROM tblproject_stages ps WHERE ps.projectID = p.projectID AND ps.stageName = 'Notice to Proceed' AND ps.isSubmitted = 1) as is_finished
        FROM tblproject p
        LEFT JOIN mode_of_procurement mop ON p.MoPID = mop.MoPID";

$conditions = [];
$params = [];

// Add conditions based on the filter status
if ($filterStatus === 'done') {
    $sql .= " WHERE (SELECT COUNT(*) FROM tblproject_stages ps WHERE ps.projectID = p.projectID AND ps.stageName = 'Notice to Proceed' AND ps.isSubmitted = 1) > 0";
} elseif ($filterStatus === 'ongoing') {
    $sql .= " WHERE (SELECT COUNT(*) FROM tblproject_stages ps WHERE ps.projectID = p.projectID AND ps.stageName = 'Notice to Proceed' AND ps.isSubmitted = 1) = 0";
}

$sql .= " ORDER BY p.createdAt DESC";

// Prepare and execute the SQL query
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// For each project, determine the current stage using the same logic as index.php
foreach ($projects as &$project) {
    $currentStage = null;
    
    // Fetch all stages for this project
    $stmtStages = $pdo->prepare("SELECT * FROM tblproject_stages WHERE projectID = ? ORDER BY stageID ASC");
    $stmtStages->execute([$project['projectID']]);
    $stages = $stmtStages->fetchAll(PDO::FETCH_ASSOC);

    // Map stages by stageName for easy access
    $stagesMap = [];
    foreach ($stages as $stage) {
        $stagesMap[$stage['stageName']] = $stage;
    }
    
    // Check if finished
    $noticeToProceedSubmitted = false;
    if (isset($stagesMap['Notice to Proceed']) && $stagesMap['Notice to Proceed']['isSubmitted'] == 1) {
        $noticeToProceedSubmitted = true;
    }
    
    // Determine current stage - this should be the stage with the highest stageID that is submitted
    if (!$noticeToProceedSubmitted) {
        // Find the submitted stage with the highest stageID (most recent submission)
        $highestSubmittedStageID = 0;
        $highestSubmittedStageName = null;
        
        foreach ($stages as $stage) {
            if ($stage['isSubmitted'] == 1) {
                // Get the stageID from stage_reference table
                $stmtStageOrder = $pdo->prepare("SELECT stageOrder FROM stage_reference WHERE stageName = ?");
                $stmtStageOrder->execute([$stage['stageName']]);
                $stageOrderResult = $stmtStageOrder->fetch();
                
                if ($stageOrderResult && $stageOrderResult['stageOrder'] > $highestSubmittedStageID) {
                    $highestSubmittedStageID = $stageOrderResult['stageOrder'];
                    $highestSubmittedStageName = $stage['stageName'];
                }
            }
        }
        
        $currentStage = $highestSubmittedStageName;
    }
    
    $project['current_stage'] = $currentStage;
}
unset($project); // break reference

// Get admin status from session
$isAdmin = isset($_SESSION['admin']) && $_SESSION['admin'] == 1;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Tracker</title>
    <link rel="stylesheet" href="assets/css/project_tracker.css">
</head>
<body>
    <?php
    include 'header.php'
    ?>

    <?php if ($filterStatus === 'ongoing'): ?>
        <p class="filter-info">Ongoing Projects</p>
    <?php elseif ($filterStatus === 'done'): ?>
        <p class="filter-info">Finished Projects</p>
    <?php endif; ?>

    <div class="back-button-container">
        <!-- Changed link to index.php and added show_stats parameter -->
        <a href="<?php echo url('index.php', ['show_stats' => 'true']); ?>" class="back-button">&larr; Back to Dashboard</a>
    </div>
    <div class="project-list">
        <?php if (!empty($projects)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Mode of Procurement</th>
                        <th>PR Number</th>
                        <th>Project Details</th>
                        <th>Current Stage</th>
                        <th>Status</th>
                        <th>Actions</th> <!-- New Actions column header -->
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($projects as $project): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($project['mode_of_procurement'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($project['prNumber'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($project['projectDetails'] ?? 'N/A'); ?></td>
                            <td>
                                <?php
                                if (($project['is_finished'] ?? 0) > 0) {
                                    echo "<span class='status-done'>Finished</span>";
                                } else {
                                    echo htmlspecialchars($project['current_stage'] ?? 'No Stage Defined');
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                if (($project['is_finished'] ?? 0) > 0) {
                                    echo "<span class='status-done'>Done</span>";
                                } else {
                                    echo "<span class='status-ongoing'>Ongoing</span>";
                                }
                                ?>
                            </td>
                            <td class="action-icons">
                                <!-- Edit Icon -->
                                <a href="<?php echo url('edit_project.php', ['projectID' => $project['projectID']]); ?>" class="edit-project-btn action-btn-spacing" title="Edit Project">
                                    <img src="assets/images/Edit_icon.png" alt="Edit Project" class="icon-24">
                                </a>
                                <!-- Delete Icon - Only show if user is an admin -->
                                <?php if ($isAdmin): ?>
                                <a href="<?php echo url('index.php', ['deleteProject' => $project['projectID']]); ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this project and all its stages?');" title="Delete Project">
                                    <img src="assets/images/delete.png" alt="Delete Project" class="icon-24">
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: /* Corrected syntax: added colon */ ?>
            <p class="no-projects">No projects found.</p>
        <?php endif; ?>
    </div>
</body>
</html>