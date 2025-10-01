<?php
include '../includes/config.php';
include '../includes/auth.php';

// Redirect if not admin
if (!isAdmin()) {
    redirect('../index.php');
}

// Handle user actions
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($user_id > 0) {
        switch ($action) {
            case 'delete':
                // Verify user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Don't allow self-deletion
                    if ($user_id == $_SESSION['user_id']) {
                        $_SESSION['error'] = "You cannot delete your own account";
                    } else {
                        // Delete user
                        $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
                        if ($stmt->execute([$user_id])) {
                            $_SESSION['success'] = "User deleted successfully";
                        } else {
                            $_SESSION['error'] = "Error deleting user";
                        }
                    }
                }
                break;
                
            case 'toggle_status':
                // Verify user exists
                $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Don't allow self-deactivation
                    if ($user_id == $_SESSION['user_id']) {
                        $_SESSION['error'] = "You cannot deactivate your own account";
                    } else {
                        // Toggle status (this would require adding an active field to users table)
                        $_SESSION['error'] = "User status toggle feature not implemented yet";
                    }
                }
                break;
        }
    }
    
    // Redirect to avoid form resubmission
    redirect('admin/users.php');
}

// Get all users
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();

// Get user counts by type
$userCounts = $pdo->query("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<?php include 'admin-header.php'; ?>

<div class="admin-content">
    <div class="content-header">
        <h2>User Management</h2>
        <div class="header-actions">
            <a href="user-add.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add New User
            </a>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success">
            <p><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-error">
            <p><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></p>
        </div>
    <?php endif; ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($userCounts['admin'] ?? 0); ?></h3>
                <p>Administrators</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-tie"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($userCounts['owner'] ?? 0); ?></h3>
                <p>Property Owners</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-graduation-cap"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format($userCounts['tenant'] ?? 0); ?></h3>
                <p>Students/Tenants</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-user-plus"></i>
            </div>
            <div class="stat-info">
                <h3><?php echo number_format(array_sum($userCounts)); ?></h3>
                <p>Total Users</p>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Type</th>
                    <th>Joined</th>
                    <th>Last Login</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo $user['user_id']; ?></td>
                        <td>
                            <div class="user-info">
                                <img src="<?php echo BASE_URL . $user['profile_image']; ?>" 
                                     alt="<?php echo $user['first_name']; ?>" 
                                     class="user-avatar-sm"
                                     onerror="this.src='<?php echo BASE_URL; ?>assets/images/default-avatar.jpg'">
                                <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                            </div>
                        </td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['phone']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $user['user_type']; ?>">
                                <?php echo ucfirst($user['user_type']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <?php if ($user['last_login']): ?>
                                <?php echo date('M d, Y H:i', strtotime($user['last_login'])); ?>
                            <?php else: ?>
                                Never
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <a href="user-view.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-outline" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="user-edit.php?id=<?php echo $user['user_id']; ?>" class="btn-sm btn-outline" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <a href="users.php?action=delete&id=<?php echo $user['user_id']; ?>" 
                                       class="btn-sm btn-danger" 
                                       title="Delete"
                                       onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Close admin-main and admin-container divs
echo '</main></div>'; 
include '../includes/footer.php'; 
?>