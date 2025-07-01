<?php
// summary_report.php - Generate HTML summary report of dashboard projects

// Start the session if it hasn't been started yet
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require 'config.php';
require_once 'url_helper.php';

// Redirect if user is not logged in
if (!isset($_SESSION['username'])) {
    http_response_code(403);
    echo "<!DOCTYPE html><html><head><title>Access Denied</title></head><body><h1>Access Denied</h1><p>Please <a href='login.php'>login</a> to access this report.</p></body></html>";
    exit;
}

// Get current date and time for the report
$reportDate = date('F j, Y');
$reportTime = date('h:i A');

// Handle search filter if provided
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

// Fetch all projects with user info (same query as index.php)
$sql = "SELECT p.*, u.firstname, u.lastname, mop.MoPDescription as mode_of_procurement
        FROM tblproject p
        LEFT JOIN tbluser u ON p.userID = u.userID
        LEFT JOIN mode_of_procurement mop ON p.MoPID = mop.MoPID";

if ($search !== "") {
    $sql .= " WHERE (p.prNumber LIKE ? OR p.projectDetails LIKE ?)";
}
$sql .= " ORDER BY COALESCE(p.editedAt, p.createdAt) DESC";

$stmt = $pdo->prepare($sql);
if ($search !== "") {
    $stmt->execute(["%$search%", "%$search%"]);
} else {
    $stmt->execute();
}
$projects = $stmt->fetchAll();

// Fetch stage order from reference table
$stmtStageRef = $pdo->query("SELECT stageName FROM stage_reference ORDER BY stageOrder ASC");
$stagesOrder = $stmtStageRef->fetchAll(PDO::FETCH_COLUMN);

// For each project, fetch its stages and determine status (same logic as index.php)
foreach ($projects as &$project) {
    // Fetch all stages for this project
    $stmtStages = $pdo->prepare("SELECT * FROM tblproject_stages WHERE projectID = ? ORDER BY stageID ASC");
    $stmtStages->execute([$project['projectID']]);
    $stages = $stmtStages->fetchAll(PDO::FETCH_ASSOC);

    // Map stages by stageName for easy access
    $stagesMap = [];
    foreach ($stages as $stage) {
        $stagesMap[$stage['stageName']] = $stage;
    }
    
    // Check if Notice to Proceed is submitted (project finished)
    $noticeToProceedSubmitted = false;
    if (isset($stagesMap['Notice to Proceed']) && $stagesMap['Notice to Proceed']['isSubmitted'] == 1) {
        $noticeToProceedSubmitted = true;
    }
    
    $project['notice_to_proceed_submitted'] = $noticeToProceedSubmitted ? 1 : 0;
    
    // Determine current stage - highest submitted stageID
    $currentStage = null;
    if (!$noticeToProceedSubmitted) {
        $highestSubmittedStageID = 0;
        $highestSubmittedStageName = null;
        
        foreach ($stages as $stage) {
            if ($stage['isSubmitted'] == 1) {
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

// Calculate statistics
$totalProjects = count($projects);
$finishedProjects = 0;
foreach ($projects as $project) {
    if ($project['notice_to_proceed_submitted'] == 1) {
        $finishedProjects++;
    }
}
$ongoingProjects = $totalProjects - $finishedProjects;

// Output the HTML directly - no PDF conversion needed
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DepEd BAC Summary Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; color: #333; line-height: 1.4; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #c62828; padding-bottom: 20px; }
        .header h1 { color: #c62828; margin: 0; font-size: 24px; }
        .header h2 { color: #666; margin: 5px 0; font-size: 18px; }
        .report-info { text-align: right; margin-bottom: 20px; font-size: 12px; color: #666; }
        .stats-summary { margin-bottom: 30px; background-color: #f8f9fa; padding: 15px; border-radius: 8px; }
        .stats-summary h3 { margin-top: 0; color: #c62828; }
        .stats-grid { display: flex; justify-content: space-around; text-align: center; }
        .stat-item { flex: 1; }
        .stat-value { font-size: 24px; font-weight: bold; display: block; margin: 5px 0; }
        .stat-value.total { color: #6c757d; }
        .stat-value.done { color: #28a745; }
        .stat-value.ongoing { color: #007bff; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #c62828; color: white; font-weight: bold; }
        tr:nth-child(even) { background-color: #f2f2f2; }
        .status-done { color: #28a745; font-weight: bold; }
        .status-ongoing { color: #007bff; font-weight: bold; }
        .no-projects { text-align: center; padding: 20px; color: #666; }
        .footer { margin-top: 30px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 10px; }
        
        /* Print controls */
        .print-controls { text-align: center; margin: 20px 0; background: #f8f9fa; padding: 15px; border-radius: 8px; }
        .print-btn { 
            background-color: #007bff; 
            color: white; 
            border: none; 
            padding: 12px 20px; 
            border-radius: 5px; 
            cursor: pointer; 
            font-size: 16px;
            margin: 0 10px;
            transition: background-color 0.3s;
        }
        .print-btn:hover { background-color: #0056b3; }
        .close-btn { background-color: #6c757d; }
        .close-btn:hover { background-color: #5a6268; }
        
        /* Hide print controls when printing */
        @media print {
            .print-controls { display: none !important; }
            body { margin: 0; font-size: 11px; }
            .header { page-break-after: avoid; }
            table { page-break-inside: avoid; font-size: 10px; }
            th, td { padding: 6px; }
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            body { margin: 10px; }
            table { font-size: 10px; }
            th, td { padding: 4px; }
            .stats-grid { flex-direction: column; }
            .stat-item { margin-bottom: 10px; }
        }
    </style>
</head>
<body>
    <div class='header'>
        <h1>SCHOOLS DIVISION OF LAOAG CITY</h1>
        <h2>DEPARTMENT OF EDUCATION</h2>
        <h2>BAC TRACKING SYSTEM - SUMMARY REPORT</h2>
    </div>
    
    <div class='report-info'>
        <strong>Report Generated:</strong> <?php echo $reportDate; ?> at <?php echo $reportTime; ?><br>
        <strong>Generated by:</strong> <?php echo htmlspecialchars($_SESSION['username']); ?><?php echo $search ? " (Filtered by: '" . htmlspecialchars($search) . "')" : ""; ?>
    </div>
    
    <div class='print-controls'>
        <button class='print-btn' onclick='window.print()'>🖨️ Print / Save as PDF</button>
        <button class='print-btn close-btn' onclick='window.close()'>❌ Close</button>
    </div>
    
    <div class='stats-summary'>
        <h3>Project Statistics Overview</h3>
        <div class='stats-grid'>
            <div class='stat-item'>
                <span class='stat-value total'><?php echo $totalProjects; ?></span>
                <div>Total Projects</div>
            </div>
            <div class='stat-item'>
                <span class='stat-value done'><?php echo $finishedProjects; ?></span>
                <div>Completed Projects</div>
            </div>
            <div class='stat-item'>
                <span class='stat-value ongoing'><?php echo $ongoingProjects; ?></span>
                <div>Ongoing Projects</div>
            </div>
        </div>
    </div>

    <?php if (!empty($projects)): ?>
        <h3>Project Details</h3>
        <table>
            <thead>
                <tr>
                    <th>Mode of Procurement</th>
                    <th>PR Number</th>
                    <th>Project Details</th>
                    <th>Project Owner</th>
                    <th>Created By</th>
                    <th>Date Created</th>
                    <th>Date Edited</th>
                    <th>Current Stage</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <?php
                    $currentStageDisplay = 'N/A';
                    $statusClass = 'status-ongoing';
                    $statusText = 'Ongoing';
                    
                    if ($project['notice_to_proceed_submitted'] == 1) {
                        $currentStageDisplay = 'Finished';
                        $statusClass = 'status-done';
                        $statusText = 'Done';
                    } elseif ($project['current_stage']) {
                        $currentStageDisplay = htmlspecialchars($project['current_stage']);
                    }
                    
                    $dateCreated = $project['createdAt'] ? date("m-d-Y h:i A", strtotime($project['createdAt'])) : 'N/A';
                    $dateEdited = $project['editedAt'] ? date("m-d-Y h:i A", strtotime($project['editedAt'])) : 'N/A';
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($project['mode_of_procurement'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($project['prNumber'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($project['projectDetails'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($project['programOwner'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars(($project['firstname'] ?? '') . ' ' . ($project['lastname'] ?? '')); ?></td>
                        <td><?php echo $dateCreated; ?></td>
                        <td><?php echo $dateEdited; ?></td>
                        <td><?php echo $currentStageDisplay; ?></td>
                        <td class='<?php echo $statusClass; ?>'><?php echo $statusText; ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class='no-projects'>No projects found matching the criteria.</div>
    <?php endif; ?>
    
    <div class='footer'>
        This report was generated automatically by the DepEd BAC Tracking System.<br>
        For questions or concerns, please contact the system administrator.
    </div>
    
    <script>
        // Auto-focus for better user experience
        window.onload = function() {
            console.log('Summary report loaded successfully');
        };
        
        // Handle print button
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>
