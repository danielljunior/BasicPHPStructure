<?php

namespace database;

use database\Database;

class UserEntity
{
  private function __construct() {}
  private static ?UserEntity $_instance = null;

  public static function instance(): UserEntity
  {
    if (self::$_instance === null) {
      self::$_instance = new UserEntity();
    }
    return self::$_instance;
  }

  /**
   * @param array<string, mixed> $data
   */
  function validateFieldsForObject(array $data): bool
  {
    $requiredKeys = ['id', 'uuid', 'username', 'email', 'password', 'token', 'role', 'status', 'date_of_creation'];
    foreach ($requiredKeys as $key) {
      if (!array_key_exists($key, $data)) {
        error_log("Missing field: " . $key);
        return false;
      }
    }
    return true;
  }

  /**
   * @param array<string, mixed> $data
   */
  function createObject(array $data): ?User
  {
    if (!$this->validateFieldsForObject($data)) {
      return null;
    }

    return new User(
      id: $data['id'],
      uuid: $data['uuid'],
      username: $data['username'],
      email: $data['email'],
      password: $data['password'],
      token: $data['token'],
      role: UserRole::from($data['role']),
      status: UserStatus::from($data['status']),
      date_of_creation: $data['date_of_creation']
    );
  }

  function getByUsername(string $username): ?User
  {
    $db = Database::instance();
    $result = $db->query(
      "SELECT * FROM user WHERE username = ?",
      "s",
      [$username]
    );
    return !empty($result) ? $this->createObject($result[0]) : null;
  }



  function getByUUID(string $uuid): ?User
  {
    $db = Database::instance();
    $result = $db->query(
      "SELECT * FROM user WHERE uuid = ?",
      "s",
      [$uuid]
    );
    return !empty($result) ? $this->createObject($result[0]) : null;
  }




  function getByEmail(string $email): ?User
  {
    $db = Database::instance();
    $result = $db->query(
      "SELECT * FROM user WHERE email = ?",
      "s",
      [$email]
    );
    return !empty($result) ? $this->createObject($result[0]) : null;
  }



  function getUserByToken(string $token): ?User
  {
    $hashedToken = $this->hashToken($token);
    $db = Database::instance();
    $result = $db->query(
      "SELECT * FROM user WHERE token = ?",
      "s",
      [$hashedToken]
    );
    return !empty($result) ? $this->createObject($result[0]) : null;
  }

  function hashToken(string $token): string
  {
    $shuffleKey = \utils\getShuffleKey();
    if (!empty($shuffleKey)) {
      return hash_hmac('sha256', $token, $shuffleKey);
    }
    return hash('sha256', $token);
  }

  function hashPassword(string $input): string
  {
    // Hash the input securely using Argon2id
    // ( memory-hard, GPU-resistant )
    $hashedInput = password_hash($input, PASSWORD_ARGON2ID);
    $shuffleKey = \utils\getShuffleKey();

    if (!empty($shuffleKey)) {
      $shuffleIterations = random_int(0, 15);
      $hashedInput = \utils\controlledStringShuffle(
        $hashedInput,
        $shuffleKey,
        iterations: $shuffleIterations + 3
      );
      $hexIteration = dechex($shuffleIterations);
      $hashedInput = $hexIteration . $hashedInput;
    }

    return $hashedInput;
  }

  function unshuffleHash(string $hash): string
  {
    $shuffleKey = \utils\getShuffleKey();

    if (!empty($shuffleKey)) {
      $shuffleIterations = hexdec($hash[0]);
      $hash = substr($hash, 1);

      $hash = \utils\controlledStringShuffle(
        $hash,
        $shuffleKey,
        reverse: true,
        iterations: $shuffleIterations + 3
      );
    }

    return $hash;
  }

  function create(
    string $username,
    string $email,
    string $password,
    UserRole $role = UserRole::EDITOR,
    UserStatus $status = UserStatus::UNCONFIRMED,
    ?string $token = null,
    ?string $uuid = null
  ): ?User {
    // Generate UUIDv4 if not provided
    if ($uuid === null) {
      $uuid = \utils\generateUUIDv4();
    }

    $hashedPassword = $this->hashPassword($password);
    if ($token !== null) {
      $token = $this->hashToken($token);
    }

    $db = Database::instance();
    $success = $db->query(
      "INSERT INTO user (uuid, username, email, password, token, role, status) VALUES (?, ?, ?, ?, ?, ?, ?)",
      "sssssss",
      [
        $uuid,
        $username,
        $email,
        $hashedPassword,
        $token,
        $role->value,
        $status->value
      ]
    );

    $obj = null;

    if ($success) {
      $obj = $this->getByUUID($uuid);
    }

    return $success ? $obj : null;
  }

  function update(
    string $selectorField,
    mixed $selectorValue,
    string $targetColumn,
    mixed $value
  ): bool {
    $allowedColumns = ['id', 'uuid', 'username', 'email', 'password', 'token', 'role', 'status', 'date_of_creation'];
    if (!in_array($selectorField, $allowedColumns, true) || !in_array($targetColumn, $allowedColumns, true)) {
      error_log("Invalid column name for update: {$selectorField} or {$targetColumn}");
      return false;
    }

    if ($selectorValue instanceof \BackedEnum) {
      $selectorValue = $selectorValue->value;
    }

    if ($value instanceof \BackedEnum) {
      $value = $value->value;
    }

    if ($targetColumn === 'password' && $value !== null) {
      $value = $this->hashPassword($value);
    } elseif ($targetColumn === 'token' && $value !== null) {
      $value = $this->hashToken($value);
    }

    $valType = is_int($value) ? "i" : (is_float($value) ? "d" : "s");
    $selType = is_int($selectorValue) ? "i" : (is_float($selectorValue) ? "d" : "s");

    $types = $valType . $selType;

    $db = Database::instance();
    return (bool) $db->query(
      "UPDATE user SET {$targetColumn} = ? WHERE {$selectorField} = ?",
      $types,
      [$value, $selectorValue]
    );
  }


  function authenticate(
    string $usernameOrEmail,
    string $password
  ): ?User {
    if (filter_var($usernameOrEmail, FILTER_VALIDATE_EMAIL)) {
      $user = $this->getByEmail($usernameOrEmail);
    } else {
      $user = $this->getByUsername($usernameOrEmail);
    }
    if (!$user) {
      return null;
    }

    $dbPassword = $user->getPassword();
    $dbPassword = $this->unshuffleHash($dbPassword);

    if (password_verify($password, $dbPassword)) {
      return $user;
    }
    return null;
  }
}
