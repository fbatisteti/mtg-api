<?php

// from api.php comes:
//      $method / $specific_endpoint / $params / $return

require('classes\set.class.php');

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
        case "/list":
            if ($method == 'GET') {
                // setting the conditions
                $where = '1=1';

                foreach ($params as $key => $value) {
                    switch ($key) {
                        case "id":
                            $where .= " AND id = '$value'";
                            break;

                        case "name":
                            $where .= " AND (name LIKE '%".$value."%'";
                            $where .= " OR other_names LIKE '%".$value."%')";
                            break;

                        case "min_date":
                            $where .= " AND release_date >= '$value'";
                            break;

                        case "max_date":
                            $where .= " AND release_date <= '$value'";
                            break;

                        case "min_cards":
                            $where .= " AND size >= '$value'";
                            break;

                        case "max_cards":
                            $where .= " AND size <= '$value'";
                            break;

                        case "language":
                            $where .= " AND other_names LIKE '%\"".$value."\"%'";
                            break;

                        default:
                            break;
                    }
                }

                // search
                $query = "SELECT * FROM sets WHERE $where;";
    
                $ex = mysqli_query($conn, $query);

                if (mysqli_num_rows($ex) > 0) {
                    $set_list = array();

                    while ($res = mysqli_fetch_assoc($ex)) {
                        // $set = new Set($res['name'],
                        //                 $res['other_names'],
                        //                 $res['release_date'],
                        //                 $res['size']);

                        array_push($set_list, $res);
                    }
                                   
                    $return = json_encode($set_list);
                    CheckSetCache($set_list);
                }
                else {
                    $return = "No sets where found that match your critearia";
                }
            }
            else {
                $return = "Invalid method for API call /set/list. Expecting GET, received " . $method;
            }

            break;

        case "/create":
            if ($method == 'POST') {
                // check for API key
                $header_apiKey = apache_request_headers();

                if (isset($header_apiKey['API_KEY'])) {
                    $apiKey = $header_apiKey['API_KEY'];

                    // get body
                    $body = file_get_contents('php://input');
                    $data = json_decode($body, true);

                    // check required fields
                    if (isset($data['name'])) {
                        $insert_fields = '';
                        $insert_values = '';

                        $name = $data['name'];
                        $insert_fields .= 'name';
                        $insert_values .= "\"".$name."\"";

                        if (isset($data['release_date'])) {
                            $release_date = $data['release_date'];
                            $insert_fields .= ', release_date';
                            $insert_values .= ", '".$release_date."'";
                        }
        
                        if (isset($data['set_size'])) {
                            $set_size = $data['set_size'];
                            $insert_fields .= ', size';
                            $insert_values .= ", '".$set_size."'";
                        }
                        
                        if (isset($data['other_names'])) {
                            $other_names = $data['other_names'];
                            $insert_fields .= ', other_names';
                            $insert_values .= ", '".str_replace("'", "\'", json_encode($other_names))."'";
                        }
        
                        // check if set already exists
                        $query = "SELECT * FROM sets WHERE name = \"$name\";";
        
                        $ex = mysqli_query($conn, $query);

                        if (mysqli_num_rows($ex) == 0) {
                            // confirm if API key matches saved token
                            if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                                // insert
                                $query = "INSERT INTO sets ($insert_fields) VALUES ($insert_values);";

                                $ex = mysqli_query($conn, $query);
            
                                if (mysqli_affected_rows($conn) == 1) {
                                    $return = "Set was created";
                                    UpdateSetCache($conn);
                                }
                                else {
                                    $return = "Something went wrong when creating your set. Please, check the information sent and try again";
                                }
                            }
                            else {
                                $return = "Provided API key is incorrect. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "A set with provided name already exists. Please, try again with another name";
                        }
                    }
                    else {
                        $return = "Please, correct the information sent. Required fields are: name";
                    }
                }
                else {
                    $return = "API key not found. Please, provide it as an API key authorization header named as API_KEY";
                }
            }
            else {
                $return = "Invalid method for API call /set/create. Expecting POST, received " . $method;
            }

            break;

        case "/update":
            if ($method == 'PUT') {
                // check for API key
                $header_apiKey = apache_request_headers();

                if (isset($header_apiKey['API_KEY'])) {
                    $apiKey = $header_apiKey['API_KEY'];

                    // get body
                    $body = file_get_contents('php://input');
                    $data = json_decode($body, true);

                    // check required fields
                    if (true) {
                        $update_fields = '';

                        if (isset($data['name'])) {
                            $name = $data['name'];
                        }

                        if (isset($data['release_date'])) {
                            $release_date = $data['release_date'];
                        }
        
                        if (isset($data['set_size'])) {
                            $set_size = $data['set_size'];
                        }
                        
                        if (isset($data['other_names'])) {
                            $other_names = $data['other_names'];
                        }
                        
                        // get target set
                        $query = "SELECT * FROM sets WHERE id = '$url_id';";

                        $ex = mysqli_query($conn, $query);

                        if (mysqli_num_rows($ex) == 1) {
                            $res = mysqli_fetch_assoc($ex);

                            if (isset($name) && $res['name'] != $name) {
                                $update_fields .= ', name = "'.$name.'"';
                            }
                            
                            if (isset($release_date) && $res['release_date'] != $release_date) {
                                $update_fields .= ', release_date = "'.$release_date.'"';
                            } 

                            if (isset($set_size) && $res['size'] != $set_size) {
                                $update_fields .= ', size = "'.$set_size.'"';
                            }

                            if (isset($other_names) && $res['other_names'] != $other_names) {
                                $update_fields .= ', other_names = \''.str_replace("'", "\'", json_encode($other_names)).'\'';
                            }

                            // confirm if API key matches saved token
                            if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                                // update
                                $update_fields = substr($update_fields, 1);
                                $query = "UPDATE sets SET $update_fields WHERE id = '$url_id';";

                                $ex = mysqli_query($conn, $query);

                                if (mysqli_affected_rows($conn) == 1) {
                                    $return = "Set was updated";
                                    UpdateSetCache($conn);
                                }
                                else {
                                    $return = "Something went wrong when updating your set. Please, check the information sent and try again";
                                }
                            }
                            else {
                                $return = "Provided API key is incorrect. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "A set with provided ID could not be found. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "Please, correct the information sent. Required fields are: name";
                    }
                }
                else {
                    $return = "API key not found. Please, provide it as an API key authorization header named as API_KEY";
                }
            }
            else {
                $return = "Invalid method for API call /set/update. Expecting PUT, received " . $method;
            }

            break;

        case "/delete";
            if ($method == 'DELETE') {
                // check for API key
                $header_apiKey = apache_request_headers();

                if (isset($header_apiKey['API_KEY'])) {
                    $apiKey = $header_apiKey['API_KEY'];
                    
                    // get target set
                    $query = "SELECT * FROM sets WHERE id = '$url_id';";

                    $ex = mysqli_query($conn, $query);

                    if (mysqli_num_rows($ex) == 1) {
                        // confirm if API key matches saved token
                        if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                            // delete
                            $query = "DELETE FROM sets WHERE id = '$url_id';";

                            $ex = mysqli_query($conn, $query);

                            if (mysqli_affected_rows($conn) == 1) {
                                $return = "Set was removed";
                                UpdateSetCache($conn);
                            }
                            else {
                                $return = "Something went wrong when deleting your set. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "Provided API key is incorrect. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "A set with provided ID could not be found. Please, check the information sent and try again";
                    }
                }
                else {
                    $return = "API key not found. Please, provide it as an API key authorization header named as API_KEY";
                }
            }
            else {
                $return = "Invalid method for API call /set/update. Expecting DELETE, received " . $method;
            }
            break;

        default:
            $array = array();
            $array['group'] = '/sets';
            $array['endpoints'] = ['/list', '/create', '/update/{id}', '/delete/{id}'];
            $array['extra'] = "One of the two main parts, basic CRUD for set/collection management";

            $return = json_encode($array);
            break;
    }
}

function CheckSetCache($incoming) {
    $path = 'cache\cached_sets.json';

    if (!file_exists($path)) {
        file_put_contents($path, '');
    }

    if (file_exists($path)) {
        $cached = json_decode(file_get_contents($path), true);
        if ($cached === $incoming) {
            return $cached;
        }
    }

    $cached = json_encode($incoming);
    file_put_contents($path, $cached);
}

function UpdateSetCache($connection) {
    $path = 'cache\cached_sets.json';

    $query = "SELECT * FROM sets";

    $ex = mysqli_query($connection, $query);

    if (mysqli_num_rows($ex) > 0) {
        $set_list = array();

        while ($res = mysqli_fetch_assoc($ex)) {
            array_push($set_list, $res);
        }
                        
        $cache = json_encode($set_list);
        file_put_contents($path, $cache);
    }
}

?>