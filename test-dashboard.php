<?php

// Simple test script untuk dashboard
require_once 'vendor/autoload.php';

use App\Http\Controllers\GJM\DashboardController;
use Illuminate\Http\Request;

try {
    echo "Testing Dashboard Controller...\n";
    
    // Test individual methods
    $controller = new DashboardController();
    
    // Test safe count method (using reflection to access private method)
    $reflection = new ReflectionClass($controller);
    $safeCountMethod = $reflection->getMethod('safeCount');
    $safeCountMethod->setAccessible(true);
    
    echo "Testing safeCount method...\n";
    $result = $safeCountMethod->invoke($controller, 'App\Models\Prodi');
    echo "Prodi count: " . $result . "\n";
    
    echo "Dashboard controller test completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}