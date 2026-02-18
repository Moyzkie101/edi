<?php
    //database connection 
    $host = "localhost";
    $username = "root";
    $password = "Password1";
    $database = ["edi", "masterdata"];

    // ini_set('display_errors',1);
    // error_reporting(E_ALL);
    // mysqli_report(MYSQLI_REPORT_ERROR | E_DEPRECATED | E_STRICT);
    // error_reporting(0);

    $connections = [];
    foreach ($database as $db) {
        $connections[] = mysqli_connect($host, $username, $password, $db);
    }

    // keep original variable names for compatibility
    list($conn, $conn1) = $connections;

    // check connections
    foreach ($connections as $i => $connection) {
        if (!$connection) {
            $failedDb = $database[$i];
            die("Connection to '{$failedDb}' failed: " . mysqli_connect_error());
        }
    }

    // Set the time zone to Philippines time.
    date_default_timezone_set('Asia/Manila');



    // Dynamic base path detection for URLs (for assets, links, etc.)
    function getBasePath() {
        // Get the protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        
        // Get the host
        $host = $_SERVER['HTTP_HOST'];
        
        // Get project folder name from PHP_SELF
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0]; // First directory is the project folder
        
        // Check if we're in a subfolder (like dashboard)
        $subFolder = '';
        if (count($pathParts) > 1 && $pathParts[1] === 'dashboard') {
            $subFolder = 'dashboard/';
        }
        
        // Return the complete base URL with subfolder if present
        return $protocol . $host . '/' . $projectFolder . '/' . $subFolder;
    }

    // Function for logout URL (without dashboard subfolder)
    function getAuthPath() {
        // Get the protocol (http or https)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        
        // Get the host
        $host = $_SERVER['HTTP_HOST'];
        
        // Get project folder name from PHP_SELF
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0]; // First directory is the project folder
        
        // Return base URL without any subfolder for authentication
        return $protocol . $host . '/' . $projectFolder . '/';
    }

    // Function for file system paths (for includes)
    function getFileSystemPath() {
        // Get the document root
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // Get project folder name from PHP_SELF
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0]; // First directory is the project folder
        
        // Build the file system path
        $basePath = $documentRoot . DIRECTORY_SEPARATOR . $projectFolder . DIRECTORY_SEPARATOR;
        
        // Normalize path separators for Windows
        $basePath = str_replace('/', DIRECTORY_SEPARATOR, $basePath);
        
        return $basePath;
    }

    // Function for relative paths (automatically calculates multiple ../)
    function getRelativePath() {
        // Get current file's directory
        $currentDir = dirname($_SERVER['SCRIPT_FILENAME']);
        
        // Get project root directory
        $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        $phpSelf = $_SERVER['PHP_SELF'];
        $pathParts = explode('/', trim($phpSelf, '/'));
        $projectFolder = $pathParts[0];
        
        $projectRoot = $documentRoot . DIRECTORY_SEPARATOR . $projectFolder;
        $projectRoot = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $projectRoot);
        
        // Normalize current directory
        $currentDirNormalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $currentDir);
        
        // Remove trailing separator for comparison
        $projectRoot = rtrim($projectRoot, DIRECTORY_SEPARATOR);
        $currentDirNormalized = rtrim($currentDirNormalized, DIRECTORY_SEPARATOR);
        
        // If we're already in the project root, return empty string
        if ($currentDirNormalized === $projectRoot) {
            return '';
        }
        
        // Calculate relative path by counting directory levels
        $relativePath = '';
        $tempPath = $currentDirNormalized;
        $levels = 0;
        
        // Keep going up until we reach the project root or hit safety limit
        while ($tempPath !== $projectRoot && $levels < 10) {
            $tempPath = dirname($tempPath);
            $tempPath = rtrim($tempPath, DIRECTORY_SEPARATOR);
            $levels++;
            $relativePath .= '..' . DIRECTORY_SEPARATOR;
        }
        
        return $relativePath;
    }

    // Function to get specific relative path (for custom levels)
    function getCustomRelativePath($levels = 1) {
        $relativePath = '';
        for ($i = 0; $i < $levels; $i++) {
            $relativePath .= '..' . DIRECTORY_SEPARATOR;
        }
        return $relativePath;
    }

    // Get dynamic paths
    $base_url = getBasePath();        // For URLs (assets, links)
    $auth_url = getAuthPath();        // For authentication URLs
    $base_path = getFileSystemPath(); // For file includes
    $relative_path = getRelativePath(); // For relative paths using multiple ../
?>