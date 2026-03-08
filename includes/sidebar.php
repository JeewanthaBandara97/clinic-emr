<?php
/**
 * Sidebar Navigation - UPDATED
 */

$isDoctor = User::isDoctor();
$isAssistant = User::isAssistant();
$basePath = $isDoctor ? '/doctor' : '/assistant';
?>
<nav id="sidebar" class="sidebar">
    <div class="sidebar-header">
        <a href="<?php echo APP_URL . $basePath; ?>" class="sidebar-brand">
            <i class="bi bi-hospital"></i><span>Clinic EMR</span>
        </a>
    </div>
    <div class="sidebar-user">
        <div class="user-avatar"><i class="bi bi-person-circle"></i></div>
        <div class="user-info">
            <div class="user-name"><?php echo htmlspecialchars(User::getFullName() ?? 'User'); ?></div>
            <div class="user-role"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Role'); ?></div>
        </div>
    </div>
    <ul class="sidebar-nav">
        <?php if ($isAssistant): ?>
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/index.php" 
                   class="nav-link <?php echo $currentPage === 'index' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i><span>Dashboard</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="<?php echo APP_URL; ?>/assistant/sessions.php" 
                   class="nav-link <?php echo in_array($currentPage, ['sessions', 'session-queue']) ? 'active' : ''; ?>">
                    <i class="bi bi-calendar-week"></i><span>Manage Sessions</span>
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
        <?php endif; ?>
        
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
        <button id="sidebarToggle" class="btn btn-link"><i class="bi bi-list fs-4"></i></button>
        <div class="topbar-right">
            <span class="text-muted me-3">
                <i class="bi bi-calendar3 me-1"></i><?php echo date('l, F j, Y'); ?>
            </span>
            <span class="badge bg-primary">
                <i class="bi bi-clock me-1"></i>
                <span id="currentTime"><?php echo date('h:i A'); ?></span>
            </span>
        </div>
    </nav>
    <div class="content-wrapper">