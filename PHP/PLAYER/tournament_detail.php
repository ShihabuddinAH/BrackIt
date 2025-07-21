<?php
require_once 'config.php';

// Get tournament slug dari URL
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: index.php');
    exit;
}

// Fetch tournament data dari database
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE slug = ?");
    $stmt->execute([$slug]);
    $tournament = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$tournament) {
        header('Location: index.php');
        exit;
    }
    
    // Convert rules string to array
    $rules_array = !empty($tournament['rules']) ? explode('|', $tournament['rules']) : [];
    
} catch (Exception $e) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($tournament['name']); ?> - BrackIt</title>
    <link rel="stylesheet" href="CSS/navbar.css">
    <link rel="stylesheet" href="CSS/tournamentDetail.css">
</head>
<body>
    <header class="header">
        <div class="logo"></div>
        <nav class="nav">
            <ul class="nav-menu">
                <li><a href="index.php#tournament">Tournament</a></li>
                <li><a href="index.php#teams">Teams</a></li>
                <li><a href="index.php#klasemen">Klasemen</a></li>
                <li><a href="index.php#contactus">Contact Us</a></li>
            </ul>
        </nav>
        <div class="nav-right">
            <div class="user-section" id="userSection">
                <span class="login-text" id="loginText"><a href="login.html">Login</a></span>
                <div class="user-icon" id="userIcon" style="display: none"></div>
            </div>
            <div class="hamburger-menu" id="hamburgerMenu">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <main class="tournament-detail">
        <section class="tournament-hero <?php echo htmlspecialchars($tournament['hero_class']); ?>">
            <a href="index.php" class="back-button">← Kembali</a>
            <div class="tournament-content">
                <h1 class="tournament-title"><?php echo htmlspecialchars($tournament['name']); ?></h1>
                <p class="tournament-subtitle"><?php echo htmlspecialchars($tournament['subtitle']); ?></p>
                <div class="tournament-status">
                    <?php 
                    $status_text = [
                        'upcoming' => 'Segera Dimulai',
                        'registration_open' => 'Pendaftaran Dibuka',
                        'ongoing' => 'Sedang Berlangsung',
                        'completed' => 'Selesai'
                    ];
                    echo $status_text[$tournament['status']] ?? 'Pendaftaran Dibuka';
                    ?>
                </div>
                <?php if ($tournament['tournament_start']): ?>
                <div id="countdown" data-tournament-date="<?php echo date('c', strtotime($tournament['tournament_start'] . ' 10:00:00')); ?>" style="margin-top: 20px;"></div>
                <?php endif; ?>
            </div>
        </section>

        <div class="tournament-content">
            <div class="content-grid">
                <div class="info-card">
                    <h3>Informasi Tournament</h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Game:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['game']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Format:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['format']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Max Team:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['max_teams']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Pendaftaran:</span>
                            <span class="info-value">
                                <?php 
                                if ($tournament['registration_start'] && $tournament['registration_end']) {
                                    echo date('d', strtotime($tournament['registration_start'])) . ' - ' . 
                                         date('d F Y', strtotime($tournament['registration_end']));
                                } else {
                                    echo 'Segera Diumumkan';
                                }
                                ?>
                            </span>
                        </li>
                        <li>
                            <span class="info-label">Tournament:</span>
                            <span class="info-value">
                                <?php 
                                if ($tournament['tournament_start'] && $tournament['tournament_end']) {
                                    echo date('d', strtotime($tournament['tournament_start'])) . ' - ' . 
                                         date('d F Y', strtotime($tournament['tournament_end']));
                                } else {
                                    echo 'Segera Diumumkan';
                                }
                                ?>
                            </span>
                        </li>
                        <li>
                            <span class="info-label">Prize Pool:</span>
                            <span class="info-value prize-pool"><?php echo htmlspecialchars($tournament['prize_pool']); ?></span>
                        </li>
                    </ul>
                </div>

                <div class="info-card">
                    <h3>Persyaratan</h3>
                    <ul class="info-list">
                        <li>
                            <span class="info-label">Rank Minimum:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['min_rank']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Team Size:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['team_size']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Entry Fee:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['entry_fee']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Platform:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['platform']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Region:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['region']); ?></span>
                        </li>
                        <li>
                            <span class="info-label">Age Limit:</span>
                            <span class="info-value"><?php echo htmlspecialchars($tournament['age_limit']); ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="tournament-description">
                <h3>Tentang <?php echo htmlspecialchars($tournament['name']); ?></h3>
                <?php 
                $paragraphs = explode("\n\n", $tournament['description']);
                foreach ($paragraphs as $paragraph) {
                    if (trim($paragraph)) {
                        echo '<p>' . nl2br(htmlspecialchars(trim($paragraph))) . '</p>';
                    }
                }
                ?>
            </div>

            <?php if (!empty($rules_array)): ?>
            <div class="rules-section">
                <h3>Peraturan Tournament</h3>
                <ul class="rules-list">
                    <?php foreach ($rules_array as $rule): ?>
                        <?php if (trim($rule)): ?>
                            <li><?php echo htmlspecialchars(trim($rule)); ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="registration-section">
                <h3>
                    <?php 
                    $registration_titles = [
                        'unisi-cup' => 'Daftar Sekarang!',
                        'mobile-legends' => 'Bergabunglah dengan Legends!',
                        'pubg' => 'Raih Chicken Dinner!',
                        'free-fire' => 'Raih BOOYAH!'
                    ];
                    echo $registration_titles[$tournament['hero_class']] ?? 'Daftar Sekarang!';
                    ?>
                </h3>
                <p>
                    <?php 
                    $registration_descriptions = [
                        'unisi-cup' => 'Jangan lewatkan kesempatan untuk menjadi bagian dari tournament esports terbesar untuk mahasiswa!',
                        'mobile-legends' => 'Buktikan bahwa tim Anda adalah yang terbaik di Mobile Legends Championship!',
                        'pubg' => 'Tunjukkan skill survival terbaik Anda di PUBG Tournament!',
                        'free-fire' => 'Gabung sekarang dan tunjukkan bahwa squad Anda adalah yang terbaik!'
                    ];
                    echo $registration_descriptions[$tournament['hero_class']] ?? 'Bergabunglah dengan tournament ini dan tunjukkan kemampuan terbaik Anda!';
                    ?>
                </p>
                <button class="register-button" data-tournament-id="<?php echo htmlspecialchars($tournament['slug']); ?>"
                    <?php echo ($tournament['status'] !== 'registration_open') ? 'disabled' : ''; ?>>
                    <?php 
                    if ($tournament['status'] === 'registration_open') {
                        $button_texts = [
                            'unisi-cup' => 'Daftar Tim',
                            'mobile-legends' => 'Daftar Tim',
                            'pubg' => 'Daftar Squad',
                            'free-fire' => 'Daftar Squad'
                        ];
                        echo $button_texts[$tournament['hero_class']] ?? 'Daftar Tim';
                    } else {
                        echo 'Pendaftaran Ditutup';
                    }
                    ?>
                </button>
            </div>
        </div>
    </main>

    <script src="../../SCRIPT/PLAYER/navbar.js"></script>
    <script src="SCRIPT/tournamentDetail.js"></script>
</body>
</html>