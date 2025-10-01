<?php
include '../includes/config.php';

// Get all universities
$universities = $pdo->query("SELECT * FROM universities ORDER BY 
    CASE WHEN name LIKE '%Taita Taveta%' THEN 0 ELSE 1 END, 
    name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Universities - Kenya Coastal Student Housing</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Universities</h1>
                <p class="hero-subtitle">Find housing near these coastal universities</p>
            </div>
        </div>
    </section>
    
    <section class="universities">
        <div class="container">
            <div class="universities-grid">
                <?php foreach ($universities as $university): ?>
                    <div class="university-card">
                        <div class="university-logo">
                            <?php if (!empty($university['logo'])): ?>
                                <img src="../assets/images/universities/<?php echo $university['logo']; ?>" 
                                     alt="<?php echo $university['name']; ?>"
                                     onerror="this.src='../assets/images/default-university.png'">
                            <?php else: ?>
                                <i class="fas fa-university fa-3x"></i>
                            <?php endif; ?>
                        </div>
                        
                        <h3><?php echo $university['name']; ?></h3>
                        <p><?php echo $university['location']; ?>, <?php echo $university['county']; ?></p>
                        <p><?php echo $university['description']; ?></p>
                        
                        <a href="properties.php?university=<?php echo $university['university_id']; ?>" class="btn-outline">
                            Find Housing
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    
    <?php include '../includes/footer.php'; ?>
    <script src="../assets/js/script.js"></script>
</body>
</html>