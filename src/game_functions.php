<?php

function getGames($pdo, $genre = null, $platform = null) {
    $sql = "SELECT * FROM games WHERE 1=1";
    $params = [];

    if ($genre) {
        $sql .= " AND Genre = ?";
        $params[] = $genre;
    }

    if ($platform) {
        $sql .= " AND Platform = ?";
        $params[] = $platform;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getUniqueGenres($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT Genre FROM games ORDER BY Genre ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getUniquePlatforms($pdo) {
    $stmt = $pdo->query("SELECT DISTINCT Platform FROM games ORDER BY Platform ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function getCoreForPlatform($platform) {
    $mapping = [
        'ATARI2600' => 'stella2014',
        'COMMODORE64' => 'vice_x64sc',
        'GAMEBOY' => 'gambatte',
        'GBC' => 'gambatte',
        'GBA' => 'mgba',
        'NINTENDO64' => 'mupen64plus_next',
        'NES' => 'fceumm',
        'SNES' => 'snes9x',
        'MASTER_SYSTEM' => 'genesis_plus_gx',
        'VIRTUALBOY' => 'beetle_vb',
    ];
    if (!isset($mapping[$platform])) {
        throw new Exception("Platform not supported.");
    }
    return $mapping[$platform];
}

