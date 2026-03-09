<?php
session_start();

// Connexion DB
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=netchat;charset=utf8mb4", 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur DB: " . $e->getMessage());
}

// Fonction photo (copie EXACTE)
function uploadAndCropProfilePic($tmp_file) {
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
        throw new Exception('Trop grande');
    }
    
    $source = imagecreatefromstring(file_get_contents($tmp_file));
    if (!$source) throw new Exception('Image invalide');
    
    $size = getimagesize($tmp_file);
    $thumb_size = 400;
    $src_w = $size[0]; $src_h = $size[1];
    $dst_x = ($src_w > $thumb_size) ? ($src_w - $thumb_size)/2 : 0;
    $dst_y = ($src_h > $thumb_size) ? ($src_h - $thumb_size)/2 : 0;
    
    $thumb = imagecreatetruecolor($thumb_size, $thumb_size);
    imagecopyresampled($thumb, $source, 0, 0, $dst_x, $dst_y, $thumb_size, $thumb_size, $thumb_size, $thumb_size);
    imagejpeg($thumb, $target_file, 85);
    
    imagedestroy($source);
    imagedestroy($thumb);
    
    return $target_file;
}





$error = '';
$success = '';

if ($_POST && $_POST['action'] === 'register') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone'] ?? '');
    $birthdate = $_POST['birthdate'] ?? '';

    $errors = [];

    // Vérification mots de passe identiques
    if ($password !== $confirm_password) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }

    // Vérification pseudo
    if (strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $errors[] = "Pseudo invalide (3-20 caractères, lettres/chiffres/_)";
    }

    // Vérification email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email invalide";
    }

    // Sécurité mot de passe
    if (strlen($password) < 8) {
        $errors[] = "Mot de passe trop court (8 caractères minimum)";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une majuscule";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins une minuscule";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un chiffre";
    }
    if (!preg_match('/[\W_]/', $password)) {
        $errors[] = "Le mot de passe doit contenir au moins un caractère spécial";
    }

    // Empêcher pseudo dans le mot de passe
    if (stripos($password, $username) !== false) {
        $errors[] = "Le mot de passe ne doit pas contenir votre pseudo";
    }

    // Date obligatoire
    if (empty($birthdate)) {
        $errors[] = "La date de naissance est obligatoire";
    } else {
        if (strtotime($birthdate) === false) {
            $errors[] = "Format de date invalide";
        } elseif (strtotime($birthdate) > strtotime('-13 years')) {
            $errors[] = "Vous devez avoir au moins 13 ans";
        }

        // Empêcher date dans le mot de passe
        $birthParts = explode('-', $birthdate);
        $year = $birthParts[0];
        $month = $birthParts[1];
        $day = $birthParts[2];

        if (
            stripos($password, $year) !== false ||
            stripos($password, $month) !== false ||
            stripos($password, $day) !== false ||
            stripos($password, str_replace('-', '', $birthdate)) !== false
        ) {
            $errors[] = "Le mot de passe ne doit pas contenir votre date de naissance";
        }
    }

    // Vérification téléphone
    if (!empty($phone) && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
        $errors[] = "Numéro de téléphone invalide";
    }

    // Si erreurs → stop
    if (!empty($errors)) {
        $error = "<ul>";
        foreach ($errors as $e) {
            $error .= "<li>$e</li>";
        }
        $error .= "</ul>";
    } else {
        // Vérif doublon
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);

        if ($stmt->rowCount() == 0) {

            $hash = password_hash($password, PASSWORD_DEFAULT);
            $profilepic_filename = 'assets/user_icon.png';

            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                try {
                    $profilepic_filename = uploadAndCropProfilePic($_FILES['profile_picture']['tmp_name']);
                } catch (Exception $e) {
                    error_log("Photo fail: " . $e->getMessage());
                }
            }

            $age = floor((time() - strtotime($birthdate)) / (365.25 * 24 * 3600));

            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, phone, age, profile_picture) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $email, $hash, $phone, $age, $profilepic_filename])) {
                $_SESSION['userid'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                header('Location: public/#');
                exit;
            } else {
                $error = "Pseudo ou email déjà utilisé";
            }
        }
    }
}



?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetChat - Inscription</title>
    
    <link rel="icon" type="image/png" sizes="32x32" href="assets/icon.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/icon.png">
    <link rel="apple-touch-icon" href="assets/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8 col-sm-10">
                <div class="netchat-card p-5">
                    <div class="text-center mb-5">
                        <div class="d-flex align-items-center justify-content-center mb-3">
                            <img src="assets/logo.png" alt="NetChat" width="60" height="60" class="me-3" style="border-radius: 16px;">
                            <h1 class="netchat-title display-4 fw-bold mb-0">NetChat</h1>
                        </div>
                        <p class="text-muted lead">Crée ton compte et rejoins la conversation</p>
                    </div>

                    <!-- Messages d'erreur/succès -->
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                        <?php if (!$success): ?>
    <form method="POST" action="register.php" class="needs-validation" novalidate>
        <input type="hidden" name="action" value="register">
        
        <!-- Pseudo -->
        <div class="input-group-wrapper mb-4">
            <div class="floating-icon"><i class="fas fa-user"></i></div>
            <input type="text" name="username" id="pseudo" class="form-control form-control-lg py-3" 
                   placeholder="Pseudo unique" required maxlength="20" minlength="3"
                   value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
            <div class="invalid-feedback">3-20 caractères (lettres/chiffres/_)</div>
        </div>

        <!-- Email -->
        <div class="input-group-wrapper mb-4">
            <div class="floating-icon"><i class="fas fa-envelope"></i></div>
            <input type="email" name="email" id="email" class="form-control form-control-lg py-3" 
                   placeholder="Email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <div class="invalid-feedback">Email valide requis</div>
        </div>

        <!-- Téléphone (optionnel) -->
        <div class="input-group-wrapper mb-4">
            <div class="floating-icon"><i class="fas fa-phone"></i></div>
            <input type="tel" name="phone" id="phone" class="form-control form-control-lg py-3" 
                   placeholder="06 12 34 56 78 (optionnel)">
            <div class="invalid-feedback">Format invalide</div>
        </div>

        <!-- Date naissance (optionnel) -->
        <div class="input-group-wrapper mb-4">
            <div class="floating-icon"><i class="fas fa-calendar"></i></div>
            <input type="date" name="birthdate" id="birthdate" class="form-control form-control-lg py-3" 
                   max="<?= date('Y-m-d', strtotime('-13 years')) ?>">
            <div class="invalid-feedback">Minimum 13 ans</div>
        </div>

        <!-- Mots de passe -->
        <div class="input-group-wrapper mb-4">
            <div class="floating-icon"><i class="fas fa-lock"></i></div>
            <input type="password" name="password" id="password" class="form-control form-control-lg py-3" 
                   placeholder="Mot de passe" required minlength="6">
            <div class="invalid-feedback">6 caractères minimum</div>
        </div>

        <div class="input-group-wrapper mb-5">
            <div class="floating-icon"><i class="fas fa-lock-open"></i></div>
            <input type="password" name="confirm_password" id="confirmPassword" class="form-control form-control-lg py-3" 
                   placeholder="Confirmer" required>
            <div class="invalid-feedback">Ne correspondent pas</div>
        </div>

        <div class="input-group-wrapper mb-4">
    <div class="floating-icon"><i class="fas fa-camera"></i></div>
    <input type="file" name="profile_picture" id="profile_picture" class="form-control form-control-lg py-3" accept="image/*" data-cropper>
    <div class="invalid-feedback">Image JPG/PNG (max 5Mo)</div>
    <div class="crop-preview mt-2" style="display:none;">
        <img id="crop-preview" class="rounded-circle mx-auto d-block" width="100" height="100" style="object-fit:cover;">
    </div>
</div>

        <button type="submit" class="btn btn-primary btn-lg w-100 mb-4">
            <i class="fas fa-rocket me-2"></i>S'inscrire
        </button>

                        <!-- Séparateur -->
                        <div class="text-center my-4">
                            <span class="text-muted small px-3">ou</span>
                            <hr class="my-2">
                        </div>

                        <!-- Boutons OAuth -->
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn" disabled>
                                    <i class="fab fa-google me-2"></i>Google
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-outline-primary w-100 py-2 oauth-btn" disabled>
                                    <i class="fab fa-github me-2"></i>GitHub
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="text-center mt-4">
                        <p class="text-muted mb-0">
                            Déjà un compte ? <a href="login.php" class="link-netchat fw-semibold">Se connecter</a>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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

        // Confirmation mot de passe en temps réel
        document.getElementById('confirmPassword').addEventListener('input', function() {
            const pass1 = document.getElementById('password').value;
            const pass2 = this.value;
            const feedback = this.parentElement.querySelector('.invalid-feedback');
            
            if (pass2 && pass1 !== pass2) {
                this.setCustomValidity('Les mots de passe ne correspondent pas');
                feedback.textContent = 'Les mots de passe ne correspondent pas';
            } else {
                this.setCustomValidity('');
            }
        });

        <link rel="stylesheet" href="https://unpkg.com/cropperjs@1.6.1/dist/cropper.css">
<script src="https://unpkg.com/cropperjs@1.6.1/dist/cropper.min.js"></script>
<script>
let cropper;
document.getElementById('profile_picture').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const img = document.getElementById('crop-preview');
            img.src = ev.target.result;
            img.style.display = 'block';
            img.parentElement.style.display = 'block';
            
            // Init Cropper (carré 1:1)
            const cropContainer = document.createElement('div');
            cropContainer.innerHTML = `<img id="crop-image" src="${ev.target.result}">`;
            img.parentElement.appendChild(cropContainer);
            
            cropper = new Cropper(document.getElementById('crop-image'), {
                aspectRatio: 1,
                viewMode: 1,
                autoCropArea: 0.8,
                preview: '#crop-preview'
            });
        };
        reader.readAsDataURL(file);
    }
});
</script>

    </script>
</body>
</html>
