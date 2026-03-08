<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Connexion DB
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

$errorMessages = [];
$successMessage = '';

// Récupérer les infos de l'utilisateur
$stmt = $pdo->prepare("SELECT id, username, email, phone, age, birthdate, profile_picture, bio FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: login.php');
    exit;
}

// Fonction pour uploader et recadrer la photo de profil avec coordonnées de cadrage
function uploadAndCropProfilePic($tmp_file, $cropData = null) {
    $target_dir = 'assets/users_profile_pictures/';
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $random_hex = substr(bin2hex(random_bytes(4)), 0, 8);
    $timestamp = (int)(microtime(true) * 1000);
    $filename = $random_hex . '_' . $timestamp . '.jpg';
    $target_file = $target_dir . $filename;
    
    // Vérif taille/type
    if ($_FILES['profile_picture']['size'] > 5242880) { // 5Mo
        throw new Exception('Image trop grande (max 5Mo)');
    }
    
    $source = imagecreatefromstring(file_get_contents($tmp_file));
    if (!$source) throw new Exception('Image invalide');
    
    $size = getimagesize($tmp_file);
    $src_w = $size[0];
    $src_h = $size[1];
    $thumb_size = 400;
    
    // Si des données de cadrage sont fournies, les utiliser
    if ($cropData && isset($cropData['x'], $cropData['y'], $cropData['width'], $cropData['height'])) {
        $x = (int)$cropData['x'];
        $y = (int)$cropData['y'];
        $width = (int)$cropData['width'];
        $height = (int)$cropData['height'];
        
        // Calculer les ratios pour adapter aux dimensions réelles de l'image
        $scaleX = $src_w / $cropData['naturalWidth'];
        $scaleY = $src_h / $cropData['naturalHeight'];
        
        $src_x = $x * $scaleX;
        $src_y = $y * $scaleY;
        $src_w_crop = $width * $scaleX;
        $src_h_crop = $height * $scaleY;
    } else {
        // Cadrage centré par défaut (carré au centre)
        $src_w_crop = min($src_w, $src_h);
        $src_h_crop = $src_w_crop;
        $src_x = ($src_w > $src_w_crop) ? ($src_w - $src_w_crop) / 2 : 0;
        $src_y = ($src_h > $src_h_crop) ? ($src_h - $src_h_crop) / 2 : 0;
    }

    // PHP 8.2+ : s'assurer que toutes les coordonnées transmises à imagecopyresampled sont des entiers
    $src_x = (int) round($src_x);
    $src_y = (int) round($src_y);
    $src_w_crop = (int) round($src_w_crop);
    $src_h_crop = (int) round($src_h_crop);
    
    $thumb = imagecreatetruecolor($thumb_size, $thumb_size);
    imagecopyresampled($thumb, $source, 0, 0, $src_x, $src_y, $thumb_size, $thumb_size, $src_w_crop, $src_h_crop);
    imagejpeg($thumb, $target_file, 85);
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $target_file;
}

// Traitement des formulaires
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Mise à jour du profil (username, bio, photo)
        $new_username = trim($_POST['username'] ?? '');
        $new_bio = trim($_POST['bio'] ?? '');
        
        $errors = [];
        
        // Validation username
        if (empty($new_username)) {
            $errors[] = "Le nom d'utilisateur est obligatoire";
        } elseif (strlen($new_username) < 3 || strlen($new_username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $new_username)) {
            $errors[] = "Pseudo invalide (3-20 caractères, lettres/chiffres/_)";
        } else {
            // Vérifier si le pseudo est déjà pris par un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$new_username, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Ce pseudo est déjà utilisé";
            }
        }
        
        // Validation bio (max 500 caractères)
        if (strlen($new_bio) > 500) {
            $errors[] = "La bio ne peut pas dépasser 500 caractères";
        }
        
        if (empty($errors)) {
            $profilepic_filename = $user['profile_picture'];
            
            // Gestion de l'upload de photo
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                try {
                    // Recadrage automatique au centre (pas de données de crop)
                    $profilepic_filename = uploadAndCropProfilePic($_FILES['profile_picture']['tmp_name'], null);
                } catch (Exception $e) {
                    $errors[] = "Erreur lors de l'upload de la photo : " . $e->getMessage();
                }
            }
            
            if (empty($errors)) {
                // Mise à jour en BDD
                $stmt = $pdo->prepare("UPDATE users SET username = ?, bio = ?, profile_picture = ? WHERE id = ?");
                if ($stmt->execute([$new_username, $new_bio, $profilepic_filename, $_SESSION['user_id']])) {
                    $_SESSION['username'] = $new_username;
                    $user['username'] = $new_username;
                    $user['bio'] = $new_bio;
                    $user['profile_picture'] = $profilepic_filename;
                    $successMessage = "Profil mis à jour avec succès !";
                } else {
                    $errorMessages[] = "Erreur lors de la mise à jour";
                }
            } else {
                $errorMessages = $errors;
            }
        } else {
            $errorMessages = $errors;
        }
    }
    
    if ($action === 'update_account') {
        // Mise à jour des infos du compte (email, téléphone, date de naissance)
        $new_email = trim($_POST['email'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');
        $birthdate = trim($_POST['birthdate'] ?? '');
        $new_age = null;

        $errors = [];
        
        // Validation email
        if (empty($new_email)) {
            $errors[] = "L'email est obligatoire";
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        } else {
            // Vérifier si l'email est déjà pris par un autre utilisateur
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$new_email, $_SESSION['user_id']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Cet email est déjà utilisé";
            }
        }
        
        // Validation téléphone
        if (!empty($new_phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $new_phone)) {
            $errors[] = "Numéro de téléphone invalide";
        }
        
        // Validation date de naissance + calcul de l'âge
        if (empty($birthdate)) {
            $errors[] = "La date de naissance est obligatoire";
        } else {
            $timestamp = strtotime($birthdate);
            if ($timestamp === false) {
                $errors[] = "Format de date de naissance invalide";
            } else {
                $new_age = (int) floor((time() - $timestamp) / (365.25 * 24 * 3600));
                if ($new_age < 13 || $new_age > 120) {
                    $errors[] = "Âge invalide (13-120 ans)";
                }
            }
        }
        
        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET email = ?, phone = ?, age = ?, birthdate = ? WHERE id = ?");
            if ($stmt->execute([$new_email, $new_phone ?: null, $new_age, $birthdate, $_SESSION['user_id']])) {
                $user['email'] = $new_email;
                $user['phone'] = $new_phone;
                $user['age'] = $new_age;
                $user['birthdate'] = $birthdate;
                $successMessage = "Informations du compte mises à jour avec succès !";
            } else {
                $errorMessages[] = "Erreur lors de la mise à jour";
            }
        } else {
            $errorMessages = $errors;
        }
    }
}

$profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
if (!file_exists($profile_picture)) {
    $profile_picture = 'assets/user_icon.png';
}
$user_profile_picture = $profile_picture;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paramètres - NetChat</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icon.png">
    <link rel="apple-touch-icon" href="assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
    <link href="dashboard.css" rel="stylesheet">
    <link href="settingsprofile.css" rel="stylesheet">
</head>
<body>
    <div class="nc-layout">
        <!-- Sidebar gauche type X -->
        <aside class="nc-sidebar d-none d-lg-block">
            <div class="nc-sidebar-inner">
                <div>
                    <a href="dashboard.php" class="nc-sidebar-logo">
                        <img src="assets/logo.png" alt="NetChat">
                        <span class="netchat-title fs-3 fw-bold">NetChat</span>
                    </a>
                    
                    <nav class="nc-sidebar-nav nav flex-column">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-home"></i><span>Accueil</span>
                        </a>
                        <a href="#" class="nav-link">
                            <i class="fas fa-bell"></i><span>Notifications</span>
                        </a>
                        <a href="#" class="nav-link">
                            <i class="fas fa-comments"></i><span>Chat</span>
                        </a>
                        <a href="profil.php?id=<?= (int)$_SESSION['user_id'] ?>" class="nav-link">
                            <i class="fas fa-user"></i><span>Profil</span>
                        </a>
                        <a href="settingsprofile.php" class="nav-link active">
                            <i class="fas fa-cog"></i><span>Paramètres</span>
                        </a>
                    </nav>
                </div>
                
                <div>
                    <a href="profil.php?id=<?= (int)$_SESSION['user_id'] ?>" class="nc-sidebar-profile">
                        <img src="<?= htmlspecialchars($user_profile_picture) ?>" alt="Profil">
                        <div>
                            <div class="fw-semibold"><?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                            <div class="text-muted small">@<?= htmlspecialchars($user['username'] ?? $_SESSION['username']) ?></div>
                        </div>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Colonne centrale : paramètres -->
        <main class="nc-main-column">
            <div class="nc-main-header">
                <h5>Paramètres</h5>
            </div>
            
            <div class="px-4 py-3">
                <div class="row">
                    <div class="col-lg-3 mb-4">
                        <div class="netchat-card p-3">
                            <h5 class="mb-4 fw-bold">Paramètres</h5>
                            <nav class="nav flex-column settings-nav">
                                <a class="nav-link active" data-section="profile" href="#profile">
                                    <i class="fas fa-user me-2"></i>Profil
                                </a>
                                <a class="nav-link" data-section="account" href="#account">
                                    <i class="fas fa-cog me-2"></i>Compte
                                </a>
                            </nav>
                        </div>
                    </div>
                    
                    <div class="col-lg-9">
                <div class="netchat-card p-4">
                    <!-- Messages d'erreur/succès -->
                    <?php if (!empty($errorMessages)): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <ul class="mb-0 text-start">
                                <?php foreach ($errorMessages as $msg): ?>
                                    <li><?= htmlspecialchars($msg) ?></li>
                                <?php endforeach; ?>
                            </ul>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($successMessage): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($successMessage) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Section Profil -->
                    <div id="profile-section" class="settings-content">
                        <h4 class="mb-4">
                            <i class="fas fa-user me-2"></i>Profil
                        </h4>
                        
                        <form method="POST" action="settingsprofile.php" enctype="multipart/form-data" class="needs-validation" novalidate id="profileForm">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <!-- Photo de profil -->
                            <div class="text-center mb-4">
                                <img src="<?= htmlspecialchars($profile_picture) ?>" 
                                     alt="Photo de profil" 
                                     class="rounded-circle mb-3" 
                                     width="120" 
                                     height="120" 
                                     style="object-fit: cover; border: 4px solid var(--primary-blue); box-shadow: 0 8px 24px rgba(0, 212, 255, 0.3);"
                                     id="profile-preview">
                                <div>
                                    <label for="profile_picture" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-camera me-1"></i>Changer la photo
                                    </label>
                                    <input type="file" 
                                           name="profile_picture" 
                                           id="profile_picture" 
                                           accept="image/*" 
                                           class="d-none">
                                </div>
                            </div>
                            
                            <!-- Nom d'utilisateur -->
                            <div class="input-group-wrapper mb-4">
                                <div class="floating-icon"><i class="fas fa-user"></i></div>
                                <input type="text" 
                                       name="username" 
                                       id="username" 
                                       class="form-control form-control-lg py-3" 
                                       placeholder="Nom d'utilisateur" 
                                       required 
                                       maxlength="20" 
                                       minlength="3"
                                       value="<?= htmlspecialchars($user['username']) ?>">
                                <div class="invalid-feedback">3-20 caractères (lettres/chiffres/_)</div>
                            </div>
                            
                            <!-- Bio -->
                            <div class="mb-4">
                                <label for="bio" class="form-label fw-semibold mb-2">
                                    <i class="fas fa-pencil-alt me-1"></i>Bio
                                </label>
                                <textarea name="bio" 
                                          id="bio" 
                                          class="form-control" 
                                          rows="4" 
                                          maxlength="500"
                                          placeholder="Parlez-nous de vous..."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                                <small class="text-muted">
                                    <span id="bio-count">0</span>/500 caractères
                                </small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </form>
                    </div>
                    
                    <!-- Section Compte -->
                    <div id="account-section" class="settings-content" style="display: none;">
                        <h4 class="mb-4">
                            <i class="fas fa-cog me-2"></i>Compte
                        </h4>
                        
                        <form method="POST" action="settingsprofile.php" class="needs-validation" novalidate>
                            <input type="hidden" name="action" value="update_account">
                            
                            <!-- Email -->
                            <div class="input-group-wrapper mb-4">
                                <div class="floating-icon"><i class="fas fa-envelope"></i></div>
                                <input type="email" 
                                       name="email" 
                                       id="email" 
                                       class="form-control form-control-lg py-3" 
                                       placeholder="Email" 
                                       required
                                       value="<?= htmlspecialchars($user['email']) ?>">
                                <div class="invalid-feedback">Email valide requis</div>
                            </div>

                            <!-- Changer le mot de passe (style cohérent avec la DA) -->
                            <div class="input-group-wrapper mb-4">
                                <div class="floating-icon"><i class="fas fa-key"></i></div>
                                <button type="button"
                                        class="form-control form-control-lg py-3 btn btn-outline-primary d-flex align-items-center justify-content-between"
                                        style="border-radius: 16px;"
                                        onclick="window.location.href='editmdp.php'">
                                    <span class="fw-semibold">Changer le mot de passe</span>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            </div>
                            
                            <!-- Téléphone -->
                            <div class="input-group-wrapper mb-4">
                                <div class="floating-icon"><i class="fas fa-phone"></i></div>
                                <input type="tel" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-control form-control-lg py-3" 
                                       placeholder="Téléphone (optionnel)"
                                       value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                <div class="invalid-feedback">Format invalide</div>
                            </div>
                            
                            <!-- Date de naissance -->
                            <div class="input-group-wrapper mb-4">
                                <div class="floating-icon"><i class="fas fa-calendar"></i></div>
                                <input type="date" 
                                       name="birthdate" 
                                       id="birthdate" 
                                       class="form-control form-control-lg py-3" 
                                       max="<?= date('Y-m-d', strtotime('-13 years')) ?>"
                                       value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>">
                                <div class="invalid-feedback">Minimum 13 ans</div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-save me-2"></i>Enregistrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            </div>
        </main>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navigation entre les sections
        document.querySelectorAll('.settings-nav .nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const section = this.getAttribute('data-section');
                
                // Mettre à jour les liens actifs
                document.querySelectorAll('.settings-nav .nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
                
                // Afficher/masquer les sections
                document.getElementById('profile-section').style.display = section === 'profile' ? 'block' : 'none';
                document.getElementById('account-section').style.display = section === 'account' ? 'block' : 'none';
            });
        });
        
        // Prévisualisation de l'image sélectionnée
        document.addEventListener('DOMContentLoaded', function() {
            const profilePictureInput = document.getElementById('profile_picture');
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(event) {
                            // Mettre à jour la prévisualisation directement
                            document.getElementById('profile-preview').src = event.target.result;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
        
        // Compteur de caractères pour la bio
        const bioTextarea = document.getElementById('bio');
        const bioCount = document.getElementById('bio-count');
        if (bioTextarea && bioCount) {
            // Initialiser avec la valeur actuelle
            bioCount.textContent = bioTextarea.value.length;
            bioTextarea.addEventListener('input', function() {
                bioCount.textContent = this.value.length;
            });
        }
        
        // Validation Bootstrap
        (function() {
            'use strict';
            window.addEventListener('load', function() {
                var forms = document.getElementsByClassName('needs-validation');
                Array.prototype.filter.call(forms, function(form) {
                    form.addEventListener('submit', function(event) {
                        if (form.checkValidity() === false) {
                            event.preventDefault();
                            event.stopPropagation();
                        }
                        form.classList.add('was-validated');
                    }, false);
                });
            }, false);
        })();
    </script>
</body>
</html>
