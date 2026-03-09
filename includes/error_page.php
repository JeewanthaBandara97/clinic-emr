<?php
/**
 * Friendly Error Page (Production)
 */
require_once __DIR__ . '/../config/config.php';
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Unexpected Error - <?php echo APP_NAME; ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8fafc; }
    .card { border-radius: 14px; border: 1px solid #e2e8f0; }
  </style>
</head>
<body>
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-7 col-md-9">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <div class="d-flex align-items-center mb-3">
              <div class="me-3" style="font-size:32px">😕</div>
              <div>
                <h4 class="mb-0">Something went wrong</h4>
                <small class="text-muted">Please try again or contact support</small>
              </div>
            </div>
            <hr />
            <div class="d-flex gap-2">
              <a href="<?php echo APP_URL; ?>/index.php" class="btn btn-primary">Back to Home</a>
              <a href="javascript:history.back()" class="btn btn-outline-secondary">Go Back</a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
