<?php
// Get page info for head section
$page = isset($_GET['page']) && isset($pageController) ? $_GET['page'] : 'calendar';
$config = isset($pageController) ? $pageController->getPageConfig($page) : ['title' => 'Calendar'];
$dependencies = isset($pageController) ? $pageController->getDependencies() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($currentUser['name']); ?>'s <?php echo $config['title']; ?></title>
    
    <!-- Google Fonts - Noto Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- jQuery (base requirement) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <?php 
    // Load external dependencies CSS
    if (isset($config['requires'])) {
        foreach ($config['requires'] as $dep) {
            if (isset($dependencies[$dep]['css'])) {
                foreach ($dependencies[$dep]['css'] as $css) {
                    echo "<link rel=\"stylesheet\" href=\"{$css}\">\n    ";
                }
            }
        }
    }
    ?>
    
    <!-- Application Styles -->
    <link rel="stylesheet" href="frontend/assets/css/style.css">
    <?php 
    // Load page-specific styles
    if (isset($config['styles'])) {
        foreach ($config['styles'] as $style) {
            echo "<link rel=\"stylesheet\" href=\"frontend/assets/css/{$style}\">\n    ";
        }
    }
    ?>
</head>