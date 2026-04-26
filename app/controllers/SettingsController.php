<?php
require_once __DIR__ . '/../../core/BaseController.php';
require_once __DIR__ . '/../models/User.php';

class SettingsController extends BaseController {
    private $userModel;
    
    public function __construct() {
        parent::__construct();
        $this->requireAuth();
        $this->userModel = new User($this->db);
    }
    
    public function index() {
        $user = $this->userModel->findById($_SESSION['user_id']);
        if (!$user) {
            $this->redirect('/login');
        }
        
        $errorMessages = [];
        $successMessage = '';
        
        // Traitement des formulaires
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'update_profile') {
                $result = $this->updateProfile($user);
                $errorMessages = $result['errors'] ?? [];
                $successMessage = $result['success'] ?? '';
                if (empty($errorMessages)) {
                    $user = $this->userModel->findById($_SESSION['user_id']); // Recharger
                }
            } elseif ($action === 'update_account') {
                $result = $this->updateAccount($user);
                $errorMessages = $result['errors'] ?? [];
                $successMessage = $result['success'] ?? '';
                if (empty($errorMessages)) {
                    $user = $this->userModel->findById($_SESSION['user_id']); // Recharger
                }
            }
        }
        
        $profile_picture = $user['profile_picture'] ?? 'assets/user_icon.png';
        $profile_picture_path = __DIR__ . '/../../public/' . $profile_picture;
        if (!file_exists($profile_picture_path)) {
            $profile_picture = 'assets/user_icon.png';
        }
        
        $this->view('settings/index', [
            'user' => $user,
            'profile_picture' => $profile_picture,
            'errorMessages' => $errorMessages,
            'successMessage' => $successMessage
        ]);
    }
    
    private function updateProfile($user) {
        $errors = [];
        $success = '';

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return ['errors' => ["Utilisateur introuvable"]];
        }

        $username = trim((string)($_POST['username'] ?? ''));
        $bio = (string)($_POST['bio'] ?? '');

        if (strlen($username) < 3 || strlen($username) > 20 || !preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Pseudo invalide (3-20 caractères, lettres/chiffres/_)";
        } elseif ($this->userModel->usernameExists($username, $userId)) {
            $errors[] = "Ce pseudo est déjà utilisé";
        }

        if (mb_strlen($bio) > 500) {
            $errors[] = "Bio trop longue (500 caractères max)";
        }

        $update = [];
        if (empty($errors)) {
            $update['username'] = $username;
            $update['bio'] = $bio;

            // Upload photo (optionnel)
            if (isset($_FILES['profile_picture']) && ($_FILES['profile_picture']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                try {
                    $update['profile_picture'] = $this->uploadAndCropProfilePic($_FILES['profile_picture']);
                } catch (Exception $e) {
                    $errors[] = "Photo de profil invalide";
                }
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        if (!empty($update)) {
            $ok = $this->userModel->update($userId, $update);
            if (!$ok) {
                return ['errors' => ["Erreur lors de la mise à jour du profil"]];
            }

            // refléter dans la session
            $_SESSION['username'] = $username;
            $success = "Profil mis à jour";
        }

        return ['errors' => [], 'success' => $success];
    }
    
    private function updateAccount($user) {
        $errors = [];
        $success = '';

        $userId = (int)($user['id'] ?? 0);
        if ($userId <= 0) {
            return ['errors' => ["Utilisateur introuvable"]];
        }

        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $birthdate = trim((string)($_POST['birthdate'] ?? ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email invalide";
        } elseif ($this->userModel->emailExists($email, $userId)) {
            $errors[] = "Cet email est déjà utilisé";
        }

        if ($phone !== '' && !preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $phone)) {
            $errors[] = "Numéro de téléphone invalide";
        }

        $birthdateValue = null;
        $ageValue = null;
        if ($birthdate !== '') {
            $ts = strtotime($birthdate);
            if ($ts === false) {
                $errors[] = "Date de naissance invalide";
            } elseif ($ts > strtotime('-13 years')) {
                $errors[] = "Vous devez avoir au moins 13 ans";
            } else {
                $birthdateValue = date('Y-m-d', $ts);
                $ageValue = (int)floor((time() - $ts) / (365.25 * 24 * 3600));
            }
        }

        if (!empty($errors)) {
            return ['errors' => $errors];
        }

        $update = [
            'email' => $email,
            'phone' => ($phone !== '' ? $phone : null),
            'birthdate' => $birthdateValue
        ];
        if ($ageValue !== null) {
            $update['age'] = $ageValue;
        }

        $ok = $this->userModel->update($userId, $update);
        if (!$ok) {
            return ['errors' => ["Erreur lors de la mise à jour du compte"]];
        }

        $success = "Compte mis à jour";
        return ['errors' => [], 'success' => $success];
    }

    private function uploadAndCropProfilePic(array $file): string {
        $tmp = $file['tmp_name'] ?? '';
        if (!$tmp || !is_uploaded_file($tmp)) {
            throw new Exception('Upload invalide');
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > 5242880) { // 5 Mo
            throw new Exception('Taille invalide');
        }

        $raw = file_get_contents($tmp);
        if ($raw === false) {
            throw new Exception('Lecture impossible');
        }

        $source = @imagecreatefromstring($raw);
        if (!$source) {
            throw new Exception('Image invalide');
        }

        $info = @getimagesize($tmp);
        if (!$info || empty($info[0]) || empty($info[1])) {
            imagedestroy($source);
            throw new Exception('Image invalide');
        }

        $srcW = (int)$info[0];
        $srcH = (int)$info[1];
        $side = min($srcW, $srcH);
        $srcX = (int)floor(($srcW - $side) / 2);
        $srcY = (int)floor(($srcH - $side) / 2);

        $thumbSize = 400;
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagecopyresampled($thumb, $source, 0, 0, $srcX, $srcY, $thumbSize, $thumbSize, $side, $side);

        $publicDir = realpath(__DIR__ . '/../../public');
        if (!$publicDir) {
            imagedestroy($source);
            imagedestroy($thumb);
            throw new Exception('Public introuvable');
        }

        $relDir = 'assets/users_profile_pictures';
        $targetDir = $publicDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relDir);
        if (!is_dir($targetDir)) {
            if (!mkdir($targetDir, 0777, true) && !is_dir($targetDir)) {
                imagedestroy($source);
                imagedestroy($thumb);
                throw new Exception('mkdir');
            }
        }

        $randomHex = substr(bin2hex(random_bytes(4)), 0, 8);
        $timestamp = (int)(microtime(true) * 1000);
        $filename = $randomHex . '_' . $timestamp . '.jpg';
        $targetAbs = $targetDir . DIRECTORY_SEPARATOR . $filename;

        $ok = imagejpeg($thumb, $targetAbs, 85);
        imagedestroy($source);
        imagedestroy($thumb);

        if (!$ok) {
            throw new Exception('save');
        }

        return $relDir . '/' . $filename;
    }
}
