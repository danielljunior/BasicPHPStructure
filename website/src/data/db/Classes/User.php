<?php
namespace database;

class User {
	private int $id;
	private string $uuid;
	private string $username;
	private string $email;
	private string $password;
	private ?string $token;
	private UserRole $role;
	private UserStatus $status;
	private string $date_of_creation;

	function __construct(
		int $id,
		string $uuid,
		string $username,
		string $email,
		string $password,
		?string $token,
		UserRole $role,
		UserStatus $status,
		string $date_of_creation
	){
		$this->id = $id;
		$this->uuid = $uuid;
		$this->username = $username;
		$this->email = $email;
		$this->password = $password;
		$this->token = $token;
		$this->role = $role;
		$this->status = $status;
		$this->date_of_creation = $date_of_creation;
	}

	function getId() : int {
		return $this->id;
	}

	function getUUID() : string {
		return $this->uuid;
	}

	function getUsername() : string {
		return $this->username;
	}

	function getEmail() : string {
		return $this->email;
	}

	function getPassword() : string {
		return $this->password;
	}

	function getToken() : ?string {
		return $this->token;
	}

	function getRole() : UserRole {
		return $this->role;
	}

	function getStatus() : UserStatus {
		return $this->status;
	}

	function getDateOfCreation() : string {
		return $this->date_of_creation;
	}
}
