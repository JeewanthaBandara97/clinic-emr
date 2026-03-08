<?php
/**
 * View Application Logs
 * Visit: http://localhost/clinic_emr/logs-viewer.php
 */

require_once __DIR__ . '/includes/auth.php';
requireDoctor();

$logsDir = __DIR__ . '/logs';
$logFiles = [];

if (is_dir($logsDir)) {
    $files = scandir($logsDir, SCANDIR_SORT_DESCENDING);
    foreach ($files as $file) {
        if (strpos($file, '.log') !== false) {
            $logFiles[] = $file;
        }
    }
}

$viewFile = null;
$logContent = '';

// If specific file requested
if (!empty($_GET['file'])) {
    $file = basename($_GET['file']); // Prevent directory traversal
    $filePath = $logsDir . '/' . $file;
    
    if (file_exists($filePath) && in_array($file, $logFiles)) {
        $viewFile = $file;
        $logContent = file_get_contents($filePath);
    }
}

// If no file specified, show latest
if (!$viewFile && !empty($logFiles)) {
    $viewFile = $logFiles[0];
    $filePath = $logsDir . '/' . $viewFile;
    $logContent = file_get_contents($filePath);
}

$pageTitle = 'App Logs';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';
?>

<div class="page-header">
    <h1><i class="bi bi-file-text me-2"></i>Application Logs</h1>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Log Files</h6>
            </div>
            <div class="list-group list-group-flush">
                <?php if (empty($logFiles)): ?>
                    <div class="list-group-item text-muted text-center py-3">
                        No logs yet
                    </div>
                <?php else: ?>
                    <?php foreach ($logFiles as $file): ?>
                        <a href="?file=<?php echo urlencode($file); ?>" 
                           class="list-group-item list-group-item-action <?php echo $file === $viewFile ? 'active' : ''; ?>">
                            <small><?php echo htmlspecialchars($file); ?></small>
                            <br>
                            <tiny class="text-muted"><?php echo filesize($logsDir . '/' . $file); ?> bytes</tiny>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-9">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">
                    <?php if ($viewFile): ?>
                        📄 <?php echo htmlspecialchars($viewFile); ?>
                    <?php else: ?>
                        No log file selected
                    <?php endif; ?>
                </h6>
                <?php if ($viewFile): ?>
                    <div>
                        <a href="?file=<?php echo urlencode($viewFile); ?>&download=1" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-download"></i> Download
                        </a>
                        <button onclick="location.reload()" class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise"></i> Refresh
                        </button>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card-body">
                <?php if (empty($logContent)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="bi bi-inbox display-4"></i>
                        <p class="mt-2">No logs to display</p>
                    </div>
                <?php else: ?>
                    <pre style="max-height: 600px; overflow-y: auto; background: #f8f9fa; padding: 15px; border-radius: 5px; font-size: 12px;"><?php echo htmlspecialchars($logContent); ?></pre>
                    
                    <div class="text-muted text-end mt-2">
                        <small>Total lines: <?php echo substr_count($logContent, "\n"); ?></small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
