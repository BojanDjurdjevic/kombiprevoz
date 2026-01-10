<?php
declare(strict_types=1);

namespace Controllers;

use Helpers\Logger;
use Models\User;
use PDO;
use Rules\Validator;

if (!defined('APP_ACCESS')) {
    http_response_code(403);
    die('Direct access forbidden');
}

/**
 * UserController
 * Svaka metoda = jedna akcija
 */
class UserController {
    private PDO $db;
    private object $data;
    private User $user;

    public function __construct(PDO $db, object $data)
    {
        $this->db = $db;
        $this->data = $data;
        $this->user = new User($this->db);
    }

    /**
     * Pomoćna metoda za dodelu podataka iz $data u $user objekat
     */
    private function assignUserData(): void
    {
        if (isset($this->data->users->id)) {
            $this->user->id = filter_var($this->data->users->id, FILTER_VALIDATE_INT) ?: null;
        }
        if (isset($this->data->users->name)) {
            $this->user->name = trim((string) $this->data->users->name);
        }
        if (isset($this->data->users->email)) {
            $this->user->email = trim((string) $this->data->users->email);
        }
        if (isset($this->data->users->pass)) {
            $this->user->pass = (string) $this->data->users->pass;
        }
        if (isset($this->data->users->remember)) {
            $this->user->remember = (bool) $this->data->users->remember;
        }
        if (isset($this->data->new_pass->password)) {
            $this->user->new_pass = (string) $this->data->new_pass->password;
        }
        if (isset($this->data->new_pass->confirmation_pass)) {
            $this->user->new_pass_confirm = (string) $this->data->new_pass->confirmation_pass;
        }
        if (isset($this->data->users->status)) {
            $this->user->status = (string) $this->data->users->status;
        }
        if (isset($this->data->users->city)) {
            $this->user->city = trim((string) $this->data->users->city);
        }
        if (isset($this->data->users->address)) {
            $this->user->address = trim((string) $this->data->users->address);
        }
        if (isset($this->data->users->phone)) {
            $this->user->phone = trim((string) $this->data->users->phone);
        }
        if (isset($this->data->token)) {
            $this->user->token = (string) $this->data->token;
        }
    }

    // ======================== GET METHODS ========================

    /**
     * GET all users - admin only
     * Akcija: { "user": "...", "all": true }
     */
    public function getAllUsers(): void
    {
        $this->assignUserData();
        $this->user->getAll();
    }

    /**
     * GET user by ID - admin only
     * Akcija: { "user": "...", "byID": true }
     */
    public function getUserById(): void
    {
        $this->assignUserData();
        $this->user->getByID();
    }

    /**
     * GET users by name - admin only
     * Akcija: { "user": "...", "byName": true }
     */
    public function getUsersByName(): void
    {
        $this->assignUserData();
        $this->user->getByName();
    }

    /**
     * GET users by city - admin only
     * Akcija: { "user": "...", "byCity": true }
     */
    public function getUsersByCity(): void
    {
        $this->assignUserData();
        $this->user->getByCity();
    }

    /**
     * GET user by email with logs - admin only
     * Akcija: { "users": { "byEmail": "email@example.com" } }
     */
    public function getUserByEmail(): void
    {
        $this->assignUserData();
        $result = $this->user->getByEmail();

        if ($result['success']) {
            http_response_code(200);
            echo json_encode([
                'success' => true,
                'user' => $result['user'],
                'logs' => $result['logs']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            if ($result['error'] === 'invalid_email') {
                http_response_code(400);
            } elseif ($result['error'] === 'not_found') {
                http_response_code(404);
            } else {
                http_response_code(500);
            }

            echo json_encode([
                'error' => $result['message']
            ], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * GET user logs - admin only
     * Akcija: { "users": { "getLogs": true } }
     */
    public function getUserLogs(): void
    {
        $this->assignUserData();
        $logs = $this->user->getLogs();
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'logs' => $logs
        ], JSON_UNESCAPED_UNICODE);
    }

    /**
     * CHECK reset token validity
     * Akcija: { "user": "...", "token": "..." }
     */
    public function checkResetToken(): void
    {
        $this->assignUserData();
        if (empty($this->user->token)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Token je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->checkToken($this->user->token);
    }

    // ======================== POST METHODS ========================

    /**
     * REGISTER new user (self-registration)
     * Akcija: { "users": { "signin": true, ... } }
     */
    public function registerUser(): void
    {
        $this->assignUserData();
        // Validacija obaveznih polja
        if (empty($this->user->name) || empty($this->user->email) 
            || empty($this->user->pass) || empty($this->user->address) 
            || empty($this->user->city) || empty($this->user->phone)) {
            
            http_response_code(400);
            echo json_encode([
                'error' => 'Sva polja su obavezna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija email formata
        if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->create();
    }

    /**
     * CREATE user by admin - admin only
     * Akcija: { "users": { "byAdmin": true, ... } }
     */
    public function createUserByAdmin(): void
    {
        $this->assignUserData();
        // Validacija obaveznih polja
        if (empty($this->user->name) || empty($this->user->email) 
            || empty($this->user->address) || empty($this->user->city) 
            || empty($this->user->phone) || empty($this->user->status)) {
            
            http_response_code(400);
            echo json_encode([
                'error' => 'Sva polja su obavezna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija email formata
        if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija statusa
        $validStatuses = ['Superadmin', 'Admin', 'User', 'Driver'];
        if (!in_array($this->user->status, $validStatuses, true)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan status korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->createByAdmin();
    }

    /**
     * LOGIN user
     * Akcija: { "users": { "login": true, "email": "...", "pass": "..." } }
     */
    public function loginUser(): void
    {
        $this->assignUserData();
        if (empty($this->user->email) || empty($this->user->pass)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Email i lozinka su obavezni'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija email formata
        if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->login();
    }

    /**
     * LOGOUT user
     * Akcija: { "logout": true }
     */
    public function logoutUser(): void
    {
        $this->user->logout();
    }

    // ======================== PUT METHODS ========================

    /**
     * UPDATE user profile
     * Akcija: { "updateProfile": true, "users": { ... } }
     */
    public function updateProfile(): void
    {
        $this->assignUserData();
        if (!($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da vršite izmene'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija email formata ako se menja
        if (!empty($this->user->email) && !filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->update();
    }

    /**
     * UPDATE user password
     * Akcija: { "updatePass": true, "new_pass": { "password": "...", "confirmation_pass": "..." } }
     */
    public function updatePassword(): void
    {
        $this->assignUserData();
        if (!$this->user->isOwner()) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da menjate lozinku'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($this->user->new_pass) || empty($this->user->new_pass_confirm)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nova lozinka i potvrda su obavezne'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($this->user->new_pass !== $this->user->new_pass_confirm) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Lozinke se ne poklapaju'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->updatePassword();
    }

    /**
     * REQUEST password reset (send email with token)
     * Akcija: { "resetPass": true, "users": { "email": "..." } }
     */
    public function requestPasswordReset(): void
    {
        $this->assignUserData();
        if (empty($this->user->email)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Email adresa je obavezna'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija email formata
        if (!filter_var($this->user->email, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Neispravan format email adrese'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->resetPassword();
    }

    /**
     * PROCESS password reset (with token)
     * Akcija: { "token": "...", "new_pass": { ... } }
     */
    public function processPasswordReset(): void
    {
        $this->assignUserData();
        if (empty($this->user->token)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Token je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (empty($this->user->new_pass) || empty($this->user->new_pass_confirm)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Nova lozinka i potvrda su obavezne'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if ($this->user->new_pass !== $this->user->new_pass_confirm) {
            http_response_code(400);
            echo json_encode([
                'error' => 'Lozinke se ne poklapaju'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->processResetPassword();
    }

    /**
     * UPDATE user by admin (change status, etc.) - admin only
     * Akcija: { "updateByAdmin": true, "users": { ... } }
     */
    public function updateUserByAdmin(): void
    {
        $this->assignUserData();
        // Validacija ID-a
        if (empty($this->user->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID korisnika je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        // Validacija statusa ako se menja
        if (!empty($this->user->status)) {
            $validStatuses = ['Superadmin', 'Admin', 'User', 'Driver'];
            if (!in_array($this->user->status, $validStatuses, true)) {
                http_response_code(400);
                echo json_encode([
                    'error' => 'Neispravan status korisnika'
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
        }

        $this->user->userUpdateByAdmin();
    }

    // ======================== DELETE METHODS ========================

    /**
     * DELETE user (soft delete)
     * Akcija: { "delete": true, "users": { "id": N } }
     */
    public function deleteUser(): void
    {
        $this->assignUserData();
        if (empty($this->user->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID korisnika je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!($this->user->isOwner() || Validator::isAdmin() || Validator::isSuper())) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Niste autorizovani da obrišete ovog korisnika'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->delete();
    }

    /**
     * RESTORE user - admin only
     * Akcija: { "restore": true, "users": { "id": N } }
     */
    public function restoreUser(): void
    {
        $this->assignUserData();
        if (empty($this->user->id)) {
            http_response_code(400);
            echo json_encode([
                'error' => 'ID korisnika je obavezan'
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        $this->user->restore();
    }
}

?>