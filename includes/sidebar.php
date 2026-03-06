<?php
/**
 * Sidebar Navigation
 */

$isDoctor = User::isDoctor();
$isAssistant = User::isAssistant();
$isAdmin = User::isAdmin();

// Set base path based on role
if ($isAdmin) {
    $basePath = '/admin';
} elseif ($isDoctor) {
    $basePath = '/doctor';
} else {
    $basePath = '/assistant';
}

// Get current page name for active state
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL . $basePath; ?>" class="sidebar-brand">
            <i class="bi bi-hospital"></i>
            <span>Clinic EMR</span>
        </a>
    </div>
    
    <div class="sidebar-user">
        <div class="user-avatar">
            <i class="bi bi-person-circle"></i>
        </div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars(User::getFullName() ?? 'User'); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></div>
        </div>
    </div>
    
    <ul class="sidebar-nav">

        <?php if ($isAssistant): ?>
            <!-- ==================== ASSISTANT MENU ==================== -->
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/index.php" 
                   class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/create-session.php" 
                   class="nav-link <?php echo $currentPage === 'create-session' ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-plus"></i><span>Create Session</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/register-patient.php" 
                   class="nav-link <?php echo $currentPage === 'register-patient' ? 'active' : ''; ?>">
                    <i class="bi bi-person-plus"></i><span>Register Patient</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/add-to-queue.php" 
                   class="nav-link <?php echo $currentPage === 'add-to-queue' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i><span>Add to Queue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/todays-patients.php" 
                   class="nav-link <?php echo $currentPage === 'todays-patients' ? 'active' : ''; ?>">
                    <i class="bi bi-list-check"></i><span>Today's Patients</span>
                </a>
            </li>

        <?php elseif ($isDoctor): ?>
            <!-- ==================== DOCTOR MENU ==================== -->
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/doctor/index.php" 
                   class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/doctor/patient-queue.php" 
                   class="nav-link <?php echo $currentPage === 'patient-queue' ? 'active' : ''; ?>">
                    <i class="bi bi-people-fill"></i><span>Patient Queue</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/doctor/search-patient.php" 
                   class="nav-link <?php echo $currentPage === 'search-patient' ? 'active' : ''; ?>">
                    <i class="bi bi-search"></i><span>Search Patient</span>
                </a>
            </li>

        <?php elseif ($isAdmin): ?>
            <!-- ==================== ADMIN MENU ==================== -->
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/admin/index.php" 
                   class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/admin/doctors.php" 
                   class="nav-link <?php echo $currentPage === 'doctors' ? 'active' : ''; ?>">
                    <i class="bi bi-heart-pulse"></i><span>Manage Doctors</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/admin/assistants.php" 
                   class="nav-link <?php echo $currentPage === 'assistants' ? 'active' : ''; ?>">
                    <i class="bi bi-person-badge"></i><span>Manage Assistants</span>
                </a>
            </li>

        <?php endif; ?>

        <!-- ==================== COMMON: LOGOUT ==================== -->
        <li class="nav-divider"></li>
        
        <li class="nav-item">
            <a href="<?php echo APP_URL; ?>/auth/logout.php" class="nav-link text-danger">
                <i class="bi bi-box-arrow-right"></i><span>Logout</span>
            </a>
        </li>
    </ul>
</nav>

<div class="main-content">
    <nav class="topbar">
        <button id="sidebarToggle" class="btn btn-link">
            <i class="bi bi-list fs-4"></i>
        </button>
        <div class="topbar-right">
            <span class="text-muted me-3">
                <i class="bi bi-calendar3 me-1"></i><?php echo date('l, F j, Y'); ?>
            </span>
        </div>
    </nav>
    
    <div class="content-wrapper">