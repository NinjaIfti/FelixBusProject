<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the requested path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Debug info
echo "<!-- Debug Info:\n";
echo "REQUEST_URI: " . $request . "\n";
echo "Parsed Path: " . $path . "\n";

// Remove leading slash and get the page name
$page = trim($path, '/');

// Default to index if no page specified
if (empty($page)) {
    $page = 'index';
}

// Clean the page name (remove .php if present)
$page = str_replace('.php', '', $page);
echo "Page name: " . $page . "\n";

// Path to the actual PHP file
$file_path = __DIR__ . '/../pages/' . $page . '.php';
echo "Looking for file: " . $file_path . "\n";
echo "File exists: " . (file_exists($file_path) ? 'YES' : 'NO') . "\n";

// Check what's in the pages directory
$pages_dir = __DIR__ . '/../pages/';
if (is_dir($pages_dir)) {
    echo "Pages directory contents:\n";
    $files = scandir($pages_dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            echo "  - " . $file . "\n";
        }
    }
} else {
    echo "Pages directory not found!\n";
}
echo "-->\n";

// Check if the file exists
if (file_exists($file_path)) {
    // Include the file
    include $file_path;
} else {
    // 404 error with debug info
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page '{$page}.php' was not found.</p>";
    echo "<p>Looking for: {$file_path}</p>";
    
    // Show available pages
    if (is_dir($pages_dir)) {
        echo "<h3>Available pages:</h3><ul>";
        $files = scandir($pages_dir);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && pathinfo($file, PATHINFO_EXTENSION) == 'php') {
                $page_name = pathinfo($file, PATHINFO_FILENAME);
                echo "<li><a href='/{$page_name}'>{$page_name}</a></li>";
            }
        }
        echo "</ul>";
    }
}
?>
