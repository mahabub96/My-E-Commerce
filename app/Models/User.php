<?php

namespace App\Models;

use App\Core\Model;

/**
 * User model
 * Handles basic user lookups and authentication
 */
class User extends Model
{
    protected string $table = 'users';

    /**
     * Find a user by email
     * @param string $email
     * @return array|null
     */
    public function findByEmail(string $email): ?array
    {
        $email = trim(strtolower($email));
        // Use 'status' column from database schema (not 'is_active')
        $sql = "SELECT `id`, `email`, `name`, `role`, `status`, `created_at` FROM `{$this->table}` WHERE `email` = :email LIMIT 1";
        $stmt = $this->query($sql, ['email' => $email]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Authenticate user
     * @param string $email
     * @param string $password
     * @return array|null Returns user row on success, null on failure
     */
    public function authenticate(string $email, string $password): ?array
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return null;
        }

        // Password field in DB is assumed to be 'password'
        // fetch password separately for verification
        $stmt = $this->query("SELECT `password` FROM `{$this->table}` WHERE `id` = :id LIMIT 1", ['id' => $user['id']]);
        $pwRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$pwRow || !isset($pwRow['password'])) {
            return null;
        }

        if (!password_verify($password, $pwRow['password'])) {
            return null;
        }

        // Check active status (status = 'active')
        if (isset($user['status']) && $user['status'] !== 'active') {
            return null;
        }

        // Return safe public user data (no password)
        return [
            'id' => (int)$user['id'],
            'email' => $user['email'],
            'name' => $user['name'] ?? null,
            'role' => $user['role'] ?? null,
            'status' => $user['status'] ?? null,
            'created_at' => $user['created_at'] ?? null,
        ];
    }

    /**
     * Hash a plain password
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Create a user with automatic password hashing when provided
     * @param array $data
     * @return int Inserted user id
     */
    public function createUser(array $data): int
    {
        if (isset($data['email'])) {
            $data['email'] = trim(strtolower($data['email']));
        }

        if (isset($data['password'])) {
            $data['password'] = $this->hashPassword($data['password']);
        }

        return $this->create($data);
    }
}
