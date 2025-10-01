<?php
session_start();
include '../includes/config.php';

// Redirect if not logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit();
}

// Get statistics
$users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$properties_count = $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$bookings_count = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$revenue = $pdo->query("SELECT SUM(total_amount) FROM bookings WHERE status = 'confirmed'")->fetchColumn();

// Get recent activities
$recent_bookings = $pdo->query("
    SELECT b.*, u.first_name, u.last_name, p.title 
    FROM bookings b 
    JOIN users u ON b.user_id = u.user_id 
    JOIN properties p ON b.property_id = p.property_id 
    ORDER BY b.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get user growth data
$user_growth = $pdo->query("
    SELECT DATE(created_at) as date, COUNT(*) as count 
    FROM users 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) 
    GROUP BY DATE(created_at) 
    ORDER BY date
")->fetchAll();

// Get property types distribution
$property_types = $pdo->query("
    SELECT property_type, COUNT(*) as count 
    FROM properties 
    GROUP BY property_type
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .admin-container {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: 250px;
            background: #2c3e50;
            color: white;
            padding: 1rem 0;
        }
        
        .admin-sidebar-header {
            padding: 1rem;
            border-bottom: 1px solid #34495e;
            text-align: center;
        }
        
        .admin-sidebar-header h2 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .admin-nav {
            list-style: none;
            padding: 0;
            margin: 1rem 0;
        }
        
        .admin-nav-item {
            margin: 0;
        }
        
        .admin-nav-link {
            display: block;
            padding: 0.75rem 1rem;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .admin-nav-link:hover,
        .admin-nav-link.active {
            background: #34495e;
            color: white;
            border-left: 4px solid #3498db;
        }
        
        .admin-nav-link i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .admin-main {
            flex: 1;
            background: #ecf0f1;
            padding: 1rem;
        }
        
        .admin-header {
            background: white;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .admin-welcome h1 {
            margin: 0;
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        .admin-welcome p {
            margin: 0;
            color: #7f8c8d;
        }
        
        .admin-actions button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .stat-card {
            background: white;
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .stat-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #3498db;
        }
        
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
        }
        
        .stat-label {
            color: #7f8c8d;
            margin: 0;
        }
        
        .chart-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .chart-card {
            background: white;
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .chart-title {
            margin: 0 0 1rem 0;
            color: #2c3e50;
        }
        
        .recent-activities {
            background: white;
            padding: 1.5rem;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .activity-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table th,
        .activity-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .activity-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #ffeaa7;
            color: #d35400;
        }
        
        .status-confirmed {
            background: #d1f7c4;
            color: #27ae60;
        }
        
        .status-cancelled {
            background: #ffccc9;
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="admin-sidebar-header">
                <h2><i class="fas fa-cog"></i> Admin Panel</h2>
            </div>
            
            <ul class="admin-nav">
                <li class="admin-nav-item">
                    <a href="index.php" class="admin-nav-link active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="users.php" class="admin-nav-link">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="properties.php" class="admin-nav-link">
                        <i class="fas fa-home"></i> Properties
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="bookings.php" class="admin-nav-link">
                        <i class="fas fa-calendar-check"></i> Bookings
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="universities.php" class="admin-nav-link">
                        <i class="fas fa-university"></i> Universities
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="reports.php" class="admin-nav-link">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="settings.php" class="admin-nav-link">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                </li>
                <li class="admin-nav-item">
                    <a href="logout.php" class="admin-nav-link">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-header">
                <div class="admin-welcome">
                    <h1>Welcome, <?php echo $_SESSION['admin_name']; ?>!</h1>
                    <p>Here's what's happening with your platform today.</p>
                </div>
                <div class="admin-actions">
                    <button onclick="window.location.href='../index.php'">
                        <i class="fas fa-globe"></i> View Site
                    </button>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="stat-value"><?php echo number_format($users_count); ?></h3>
                    <p class="stat-label">Total Users</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <h3 class="stat-value"><?php echo number_format($properties_count); ?></h3>
                    <p class="stat-label">Total Properties</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <h3 class="stat-value"><?php echo number_format($bookings_count); ?></h3>
                    <p class="stat-label">Total Bookings</p>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <h3 class="stat-value">KES <?php echo number_format($revenue ?: 0); ?></h3>
                    <p class="stat-label">Total Revenue</p>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="chart-grid">
                <div class="chart-card">
                    <h3 class="chart-title">User Growth (Last 30 Days)</h3>
                    <canvas id="userGrowthChart"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3 class="chart-title">Property Types Distribution</h3>
                    <canvas id="propertyTypesChart"></canvas>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="recent-activities">
                <h3 class="chart-title">Recent Bookings</h3>
                <table class="activity-table">
                    <thead>
                        <tr>
                            <th>Property</th>
                            <th>User</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['title']; ?></td>
                                <td><?php echo $booking['first_name'] . ' ' . $booking['last_name']; ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?></td>
                                <td>KES <?php echo number_format($booking['total_amount']); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $booking['status']; ?>">
                                        <?php echo ucfirst($booking['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        // User Growth Chart
        const userGrowthCtx = document.getElementById('userGrowthChart').getContext('2d');
        const userGrowthChart = new Chart(userGrowthCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($user_growth, 'date')); ?>,
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_column($user_growth, 'count')); ?>,
                    borderColor: '#3498db',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        
        // Property Types Chart
        const propertyTypesCtx = document.getElementById('propertyTypesChart').getContext('2d');
        const propertyTypesChart = new Chart(propertyTypesCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($property_types, 'property_type')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($property_types, 'count')); ?>,
                    backgroundColor: [
                        '#3498db',
                        '#e74c3c',
                        '#2ecc71',
                        '#f39c12',
                        '#9b59b6',
                        '#1abc9c'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>