<?php

// from api.php comes:
//      $method / $specific_endpoint / $params / $return

$error = '';

$local_endpoint = '';

if ($specific_endpoint != '' && $specific_endpoint != '/') {
    $local_endpoint = '/'.explode('/', $specific_endpoint)[1];

    $failsafe = explode('/', $specific_endpoint);

    if (count($failsafe) != 2) {
        $error = "Invalid API call format";
    }
}

if ($error != '') {
    $return = $error;
}
else {
    switch ($local_endpoint) {
        case "/login":
            if ($method == 'POST') {
                // get body
                $body = file_get_contents('php://input');
                $data = json_decode($body, true);

                // check required fields
                if (isset($data['username']) && isset($data['password'])) {
                    $username = $data['username'];
                    $password = $data['password'];
                    $token = bin2hex(random_bytes(16));
    
                    $expiration = new DateTime();
                    $expiration->modify('+3 days');
                    $expiration = $expiration->format('Y-m-d');
    
                    // check if user exists
                    $query = "SELECT * FROM user WHERE username = '$username' AND password = '$password';";
    
                    $ex = mysqli_query($conn, $query);
    
                    if (mysqli_num_rows($ex) == 1) {
                        // update
                        $query = "UPDATE user SET token = '$token', expires = '$expiration' WHERE username = '$username' AND password = '$password';";
                        
                        $ex = mysqli_query($conn, $query);
    
                        if (mysqli_affected_rows($conn) == 1) {
                            $return = "Your token was refreshed. Your current API key is " . $token;
                        }
                        else {
                            $return = "Something went wrong when refreshing your token. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Username/password combination is incorrect. Please, check the information sent and try again";
                    }
                }
                else {
                    $return = "Please, correct the information sent. Required fields are: username; password";
                }
            }
            else {
                $return = "Invalid method for API call /auth/login. Expecting POST, received " . $method;
            }
            
            break;

        case "/logout":
            if ($method == 'POST') {
                // get body
                $body = file_get_contents('php://input');
                $data = json_decode($body, true);

                // check required fields
                if (isset($data['token'])) {
                    $token = $data['token'];

                    $expiration = new DateTime();
                    $expiration->modify('-7 days');
                    $expiration = $expiration->format('Y-m-d');
    
                    // check if token exists
                    $query = "SELECT * FROM user WHERE token = '$token';";
    
                    $ex = mysqli_query($conn, $query);
    
                    if (mysqli_num_rows($ex) == 1) {
                        // update
                        $query = "UPDATE user SET expires = '$expiration' WHERE token = '$token';";
                        
                        $ex = mysqli_query($conn, $query);
    
                        if (mysqli_affected_rows($conn) == 1) {
                            $return = "Your token was revoked";
                        }
                        else {
                            $return = "Something went wrong when revoking your token. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Provided token could not be found. Please, check the information sent and try again";
                    }
                }
                else {
                    $return = "Please, correct the information sent. Required fields are: token";
                }
            }
            else {
                $return = "Invalid method for API call /auth/logout. Expecting POST, received " . $method;
            }

            break;

        default:
            $array = array();
            $array['group'] = '/auth';
            $array['endpoints'] = ['/login', '/logout'];
            $array['extra'] = "Simple endpoint that creates and revokes tokens (the API_KEY that must be sent as authorization)";

            $return = json_encode($array);
            break;        
    }
}

?>