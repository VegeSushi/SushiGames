<?php
require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->safeLoad();

require_once __DIR__ . '/../src/admin_functions.php';
require_once __DIR__ . '/../src/game_functions.php';

$router = new \Bramus\Router\Router();

$router->get('/', function() {
    $pdo = require __DIR__ . '/../src/database.php';

    $selectedGenre = $_GET['genre'] ?? null;
    $selectedPlatform = $_GET['platform'] ?? null;

    $games = getGames($pdo, $selectedGenre, $selectedPlatform);
    $genres = getUniqueGenres($pdo);
    $platforms = getUniquePlatforms($pdo);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Sushi Games</title>
        <link rel="stylesheet" href="/css/style.css">
    </head>
    <body>
        <h1>Welcome to Sushi Games!</h1>

        <form method="GET" action="/" class="filter-form">
            <label for="genre">Filter by Genre:</label>
            <select name="genre" id="genre">
                <option value="">All Genres</option>
                <?php foreach ($genres as $genre): ?>
                    <option value="<?= htmlspecialchars($genre) ?>" <?= $selectedGenre === $genre ? 'selected' : '' ?>>
                        <?= htmlspecialchars($genre) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="platform">Filter by Platform:</label>
            <select name="platform" id="platform">
                <option value="">All Platforms</option>
                <?php foreach ($platforms as $platform): ?>
                    <option value="<?= htmlspecialchars($platform) ?>" <?= $selectedPlatform === $platform ? 'selected' : '' ?>>
                        <?= htmlspecialchars($platform) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Filter</button>
            <a href="/" class="clear-link">Clear</a>
        </form>

        <div class="games-list">
            <?php if (empty($games)): ?>
                <p>No games found matching your filters.</p>
            <?php else: ?>
                <?php foreach ($games as $game): ?>
                    <div class="game-card">
                        <?php if ($game['ImageURL']): ?>
                            <img src="<?= htmlspecialchars($game['ImageURL']) ?>" alt="<?= htmlspecialchars($game['Name']) ?>">
                        <?php else: ?>
                            <img src="/graphics/no-image.png" alt="No image available">
                        <?php endif; ?>
                        <h3><?= htmlspecialchars($game['Name']) ?></h3>
                        <p><strong>Platform:</strong> <?= htmlspecialchars($game['Platform']) ?></p>
                        <p><strong>Genre:</strong> <?= htmlspecialchars($game['Genre']) ?></p>
                        <p><strong>Developer:</strong> <?= htmlspecialchars($game['Developer']) ?></p>
                        <a href="/play?rom=<?= urlencode($game['ROMURL']) ?>" class="play-now-btn">Play Now</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <footer>
            <p>Powered by <a href="https://github.com/emulatorjs/EmulatorJS" target="_blank">EmulatorJS</a> and <a href="https://js-dos.com/" target="_blank">jsdos</a></p>
        </footer>
    </body>
    </html>
    <?php
});

$router->get('/play', function() {
    $pdo = require __DIR__ . '/../src/database.php';
    $romUrl = $_GET['rom'] ?? null;

    if (!$romUrl) {
        header('Location: /');
        exit;
    }

    $game = getGameByRomUrl($pdo, $romUrl);
    if (!$game) {
        echo "Game not found.";
        return;
    }

    if ($game['Platform'] === 'DOS') {
        header('Location: /play-dos?rom=' . urlencode($romUrl));
        exit;
    }

    $core = getCoreForPlatform($game['Platform']);
    // Ensure the ROM URL is absolute for EmulatorJS if needed, or relative to the domain
    // Since ROMURL starts with /rom/ in database, it's already root-relative.
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Playing <?= htmlspecialchars($game['Name']) ?></title>
        <style>
            body, html { margin: 0; padding: 0; width: 100%; height: 100%; overflow: hidden; background: black; color: white; }
            #game { width: 100vw; height: 100vh; }
            .back-btn { position: absolute; top: 10px; left: 10px; z-index: 1000; background: rgba(0,0,0,0.5); padding: 5px 10px; color: white; text-decoration: none; border-radius: 4px; }
        </style>
    </head>
    <body>
        <a href="/" class="back-btn">&larr; Back to Sushi Games</a>
        <div id="game"></div>
        <script>
            EJS_player = "#game";
            EJS_core = "<?= $core ?>";
            EJS_pathtodata = "https://cdn.emulatorjs.org/stable/data/";
            EJS_gameUrl = "<?= htmlspecialchars($game['ROMURL']) ?>";
        </script>
        <script src="https://cdn.emulatorjs.org/stable/data/loader.js"></script>
    </body>
    </html>
    <?php
});

$router->get('/play-dos', function() {
    $pdo = require __DIR__ . '/../src/database.php';
    $romUrl = $_GET['rom'] ?? null;

    if (!$romUrl) {
        header('Location: /');
        exit;
    }

    $game = getGameByRomUrl($pdo, $romUrl);
    if (!$game) {
        echo "Game not found.";
        return;
    }
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <title>Playing <?= htmlspecialchars($game['Name']) ?></title>
        <script src="https://js-dos.com/6.22/current/js-dos.js"></script>
        <style>
            html, body, canvas, .dosbox-container {
                width: 100%;
                height: 100%;
                margin: 0;
                padding: 0;
                overflow: hidden;
                background: black;
            }
            .back-btn { 
                position: absolute; 
                top: 10px; 
                left: 10px; 
                z-index: 1000; 
                background: rgba(0,0,0,0.5); 
                padding: 5px 10px; 
                color: white; 
                text-decoration: none; 
                border-radius: 4px;
                font-family: sans-serif;
            }
        </style>
    </head>
    <body>
        <a href="/" class="back-btn">&larr; Back to Sushi Games</a>
        <canvas id="jsdos"></canvas>
        <script>
            Dos(document.getElementById("jsdos"), {
                wdosboxUrl: "https://js-dos.com/6.22/current/wdosbox.js",
                cycles: 1000,
                autolock: false,
            }).ready(function (fs, main) {
                fs.extract("<?= htmlspecialchars($game['ROMURL']) ?>").then(function () {
                    main(["-c", "AUTOEXEC.BAT"]).then(function (ci) {
                        window.ci = ci;
                    });
                });
            });
        </script>
    </body>
    </html>
    <?php
});

$router->get('/db-test', function() {
    require_once __DIR__ . '/../src/database.php';
    echo 'Database connection successful!';
});

$router->mount('/admin', function() use ($router) {
    $router->get('/login', function() {
        if (isset($_SESSION['admin']) && $_SESSION['admin'] === true) {
            header('Location: /admin');
            exit;
        }
        ?>
        <form method="POST" action="/admin/login">
            <input type="password" name="password" placeholder="Admin Password" required>
            <button type="submit">Login</button>
        </form>
        <?php
    });

    $router->post('/login', function() {
        $password = $_POST['password'] ?? '';
        if ($password === ($_ENV['ADMIN_PASSWORD'] ?? getenv('ADMIN_PASSWORD'))) {
            $_SESSION['admin'] = true;
            header('Location: /admin');
        } else {
            echo "Invalid password. <a href='/admin/login'>Try again</a>";
        }
    });

    $router->get('/logout', function() {
        session_destroy();
        header('Location: /admin/login');
    });

    $router->get('/', function() {
        checkAdmin();
        global $pdo;
        require_once __DIR__ . '/../src/database.php';
        $roms = getRomFiles(__DIR__ . '/rom');
        ?>
        <h1>Admin Dashboard</h1>
        <a href="/admin/logout">Logout</a>
        <table border="1">
            <tr>
                <th>Name</th>
                <th>Platform</th>
                <th>ROM URL</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php foreach ($roms as $rom): 
                $gameData = getGameByRomUrl($pdo, $rom['rom_url']);
                $indexed = (bool)$gameData;
                ?>
                <tr>
                    <td><?= htmlspecialchars($indexed ? $gameData['Name'] : $rom['name']) ?></td>
                    <td><?= htmlspecialchars($rom['platform']) ?></td>
                    <td><?= htmlspecialchars($rom['rom_url']) ?></td>
                    <td><?= $indexed ? 'Indexed' : 'Not Indexed' ?></td>
                    <td>
                        <?php if (!$indexed): ?>
                            <form method="POST" action="/admin/add-game">
                                Name: <input type="text" name="name" value="<?= htmlspecialchars($rom['name']) ?>"><br>
                                <input type="hidden" name="platform" value="<?= htmlspecialchars($rom['platform']) ?>">
                                <input type="hidden" name="rom_url" value="<?= htmlspecialchars($rom['rom_url']) ?>">
                                Genre: <input type="text" name="genre" required><br>
                                ImageURL: <input type="text" name="image_url"><br>
                                Developer: <input type="text" name="developer"><br>
                                <button type="submit">Add Game</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="/admin/rename-game">
                                <input type="hidden" name="rom_url" value="<?= htmlspecialchars($rom['rom_url']) ?>">
                                New Name: <input type="text" name="new_name" value="<?= htmlspecialchars($gameData['Name']) ?>">
                                <button type="submit">Rename</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    });

    $router->post('/add-game', function() {
        checkAdmin();
        global $pdo;
        require_once __DIR__ . '/../src/database.php';
        
        $name = $_POST['name'] ?? '';
        $platform = $_POST['platform'] ?? '';
        $genre = $_POST['genre'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $rom_url = $_POST['rom_url'] ?? '';
        $developer = $_POST['developer'] ?? '';

        $stmt = $pdo->prepare("INSERT INTO games (Name, Platform, Genre, ImageURL, ROMURL, Developer) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $platform, $genre, $image_url, $rom_url, $developer]);

        header('Location: /admin');
    });

    $router->post('/rename-game', function() {
        checkAdmin();
        global $pdo;
        require_once __DIR__ . '/../src/database.php';
        
        $rom_url = $_POST['rom_url'] ?? '';
        $new_name = $_POST['new_name'] ?? '';

        if ($rom_url && $new_name) {
            $stmt = $pdo->prepare("UPDATE games SET Name = ? WHERE ROMURL = ?");
            $stmt->execute([$new_name, $rom_url]);
        }

        header('Location: /admin');
    });
});

$router->run();
