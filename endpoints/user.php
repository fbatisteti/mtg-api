<?php

// from api.php comes:
//      $method / $specific_endpoint / $params / $return

require('classes\user.class.php');

$error = '';

$local_endpoint = '';
$url_id = '';

if ($specific_endpoint != '' && $specific_endpoint != '/') {
    $local_endpoint = '/'.explode('/', $specific_endpoint)[1];

    $failsafe = explode('/', $specific_endpoint);

    if (count($failsafe) > 3) {
        $error = "Invalid API call format";
    }
    else if (count($failsafe) > 2) {
        $url_id = explode('/', $specific_endpoint)[2];
    }
    else {
        $url_id = '';
    }
}

if ($error != '') {
    $return = $error;
}
else {
    switch ($local_endpoint) {
        case "/create":
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
    
                    // check if username already exists
                    $query = "SELECT * FROM user WHERE username = '$username';";
    
                    $ex = mysqli_query($conn, $query);
    
                    if (mysqli_num_rows($ex) == 0) {
                        // insert
                        $query = "INSERT INTO user (username, password, token, expires) VALUES ('$username', '$password', '$token', '$expiration');";
    
                        $ex = mysqli_query($conn, $query);
    
                        if (mysqli_affected_rows($conn) == 1) {
                            $return = "User was created. Your current API key is " . $token;
                        }
                        else {
                            $return = "Something went wrong when creating your user. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Username is already taken. Please, try again with another username";
                    }
                }
                else {
                    $return = "Please, correct the information sent. Required fields are: username; password";
                }
            }
            else {
                $return = "Invalid method for API call /user/create. Expecting POST, received " . $method;
            }

            break;

        case "/changePassword": // with ID
            if ($method == 'PUT') {
                // check for API key
                $header_apiKey = apache_request_headers();

                if (isset($header_apiKey['API_KEY'])) {
                    $apiKey = $header_apiKey['API_KEY'];

                    // get body
                    $body = file_get_contents('php://input');
                    $data = json_decode($body, true);

                    // check required fields
                    if (isset($data['old_password']) && isset($data['new_password'])) {
                        $old_pw = $data['old_password'];
                        $new_pw = $data['new_password'];

                        // get target user
                        $query = "SELECT * FROM user WHERE id = '$url_id';";

                        $ex = mysqli_query($conn, $query);

                        if (mysqli_num_rows($ex) == 1) {
                            $res = mysqli_fetch_assoc($ex);

                            // confirm if API key matches saved token
                            if ($apiKey == $res['token']) {
                                // confirm if old password matches saved one
                                if ($old_pw == $res['password']) {
                                    // update
                                    $query = "UPDATE user SET password = '$new_pw' WHERE id = '$url_id';";

                                    $ex = mysqli_query($conn, $query);

                                    if (mysqli_affected_rows($conn) == 1) {
                                        $return = "Password changed";
                                    }
                                    else {
                                        $return = "Something went wrong when changing your password. Please, check the information sent and try again";
                                    }
                                }
                                else {
                                    $return = "Provided current password is incorrect. Please, check the information sent and try again";
                                }
                            }
                            else {
                                $return = "Provided API key is incorrect. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "Provided user could not be found. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Please, correct the information sent. Required fields are: old_password; new_password";
                    }
                }
                else {
                    $return = "API key not found. Please, provide it as an API key authorization header named as API_KEY";
                }
            }
            else {
                $return = "Invalid method for API call /user/changePassword. Expecting PUT, received " . $method;
            }

            break;

        case "/resetPassword":
            if ($method == 'POST') {
                // get body
                $body = file_get_contents('php://input');
                $data = json_decode($body, true);

                // check required fields
                if (isset($data['username'])) {
                    $username = $data['username'];
                    $expiration = new DateTime();
                    $expiration->modify('-7 days');
                    $expiration = $expiration->format('Y-m-d');
    
                    // check if username exists
                    $query = "SELECT * FROM user WHERE username = '$username';";
    
                    $ex = mysqli_query($conn, $query);
    
                    if (mysqli_num_rows($ex) == 1) {
                        // invalidate token
                        $query = "UPDATE user SET expires = '$expiration' WHERE username = '$username';";
    
                        $ex = mysqli_query($conn, $query);
    
                        if (mysqli_affected_rows($conn) == 1) {
                            $return = "An email was sent with instruction on how to proceed. Please, verify your inbox";
                        }
                        else {
                            $return = "Something went wrong when resetting your password. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Provided username could not be found";
                    }
                }
                else {
                    $return = "Please, correct the information sent. Required fields are: username";
                }
            }
            else {
                $return = "Invalid method for API call /user/resetPassword. Expecting POST, received " . $method;
            }

            break;

        default:
            $array = array();
            $array['group'] = '/user';
            $array['endpoints'] = ['/create', '/changePassword/{id}', '/resetPassword'];
            $array['extra'] = "Since this is for study only, not much care was put in securing the queries against XSS injection attacks. Passwords are also not encrypted. Most of its implementation is supposed to be used on a website. In hindsight, this wasn't even needed for this study :)";

            $return = json_encode($array);
            break;
    }
}

?>