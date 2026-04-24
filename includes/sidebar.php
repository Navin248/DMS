<?php
$current_user = isset($_SESSION['role']) ? $_SESSION['role'] : 'user';
?>
<style>
    /* Inline style added to bypass browser caching */
    nav.bg-dark ul li a {
        font-size: 1.25rem !important;
        padding: 0.75rem 1rem !important;
    }
    nav.bg-dark ul li a i {
        font-size: 1.25rem !important;
    }
</style>
<nav class="col-md-3 bg-dark text-white p-4" style="min-height: 100vh;">
    <h5 class="mb-4"><i class="fas fa-bars"></i> Menu</h5>
    <ul class="list-unstyled">
        <li class="mb-3">
            <?php if ($current_user === 'admin'): ?>
                <a href="/DMS/admin/dashboard.php" class="text-white text-decoration-none">
                    <i class="fas fa-chart-line"></i> Admin Dashboard
                </a>
            <?php else: ?>
                <a href="/DMS/worker/dashboard.php" class="text-white text-decoration-none">
                    <i class="fas fa-home"></i> My Dashboard
                </a>
            <?php endif; ?>
        </li>
        
        <?php if ($current_user === 'admin'): ?>
            <li class="mb-3">
                <a href="/DMS/requests/view_requests.php" class="text-white text-decoration-none">
                    <i class="fas fa-file-alt"></i> All Requests
                </a>
            </li>
            <li class="mb-3">
                <a href="/DMS/allocations/view_allocations.php" class="text-white text-decoration-none">
                    <i class="fas fa-truck"></i> Allocations
                </a>
            </li>
            <li class="mb-3">
                <a href="/DMS/disasters/view_disasters.php" class="text-white text-decoration-none">
                    <i class="fas fa-map-marker-alt"></i> Disasters
                </a>
            </li>
            <li class="mb-3">
                <a href="/DMS/resources/view_resources.php" class="text-white text-decoration-none">
                    <i class="fas fa-box"></i> Resources
                </a>
            </li>
            <li class="mb-3">
                <a href="/DMS/admin/manage_users.php" class="text-white text-decoration-none">
                    <i class="fas fa-users-cog"></i> Manage Users
                </a>
            </li>
        <?php else: ?>
            <!-- Coordinator Menu -->
            <li class="mb-3">
                <a href="/DMS/worker/my_requests.php" class="text-white text-decoration-none">
                    <i class="fas fa-file-alt"></i> My Requests
                </a>
            </li>
            <li class="mb-3">
                <a href="/DMS/requests/create_request.php" class="text-white text-decoration-none">
                    <i class="fas fa-plus-circle"></i> New Request
                </a>
            </li>
        <?php endif; ?>
        
        <hr>
        <li class="mb-3">
            <a href="/DMS/profile.php" class="text-white text-decoration-none">
                <i class="fas fa-user-circle"></i> My Profile
            </a>
        </li>
        <li>
            <a href="/DMS/logout.php" class="text-danger text-decoration-none">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </li>
    </ul>
</nav>
