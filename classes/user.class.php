<?php

class User
{
    // attributes
    private $username;
    private $password;
    private $token;
    
    // construct
    public function __construct($username, $password) {
        $this->username = $username;
        $this->password = $password;

        $this->token = bin2hex(random_bytes(16)); // 32 characters
    }

    // getters and setters
    public function GetUsername() { return $this->username; }
    public function GetPassword() { return $this->password; }
    public function GetToken() { return $this->token; }

    public function SetPassword($password) { $this->password = $password; }
    public function SetToken($token) { $this->token = $token; }

    // methods

}

?>