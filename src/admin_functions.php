<?php
session_start();

function checkAdmin() {
    if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
        header('Location: /admin/login');
        exit;
    }
}

function getRomFiles($basePath) {
    $roms = [];
    $platforms = array_filter(glob($basePath . '/*'), 'is_dir');
    
    foreach ($platforms as $platformPath) {
        $platform = basename($platformPath);
        $files = array_filter(glob($platformPath . '/*'), 'is_file');
        
        foreach ($files as $filePath) {
            $fileName = basename($filePath);
            if ($fileName === '.gitignore') continue;
            
            $roms[] = [
                'name' => pathinfo($fileName, PATHINFO_FILENAME),
                'platform' => $platform,
                'rom_url' => '/rom/' . $platform . '/' . $fileName
            ];
        }
    }
    return $roms;
}

function getGameByRomUrl($pdo, $romUrl) {
    $stmt = $pdo->prepare("SELECT * FROM games WHERE ROMURL = ?");
    $stmt->execute([$romUrl]);
    return $stmt->fetch();
}
