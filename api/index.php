<?php
// Get the requested path
$request = $_SERVER['REQUEST_URI'];
$path = parse_url($request, PHP_URL_PATH);

// Remove leading slash and get the page name
$page = trim($path, '/');

// Default to index if no page specified
if (empty($page)) {
    $page = 'index';
}

// Clean the page name (remove .php if present)
$page = str_replace('.php', '', $page);

// Path to the actual PHP file
$file_path = __DIR__ . '/../pages/' . $page . '.php';

// Check if the file exists
if (file_exists($file_path)) {
    // Include the file
    include $file_path;
} else {
    // 404 error
    http_response_code(404);
    echo "<h1>404 - Page Not Found</h1>";
    echo "<p>The page '{$page}.php' was not found.</p>";
}
?>