<?php

// from api.php comes:
//      $method / $specific_endpoint / $params / $return

require('classes\card.class.php');

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

                        case "color":
                            $colors = str_split($value);
                            foreach ($colors as $key => $color) {
                                $where .= " AND color LIKE '%".$color."%'";
                            }
                            break;

                        case "type":
                            $types = explode(" ", $value);
                            foreach ($types as $key => $type) {
                                $where .= " AND type LIKE '%".$type."%'";
                            }
                            break;
                        
                        case "rarity":
                            $where .= " AND rarity = '$rarity'";
                            break;

                        case "set":
                            $where .= " AND set_id = (SELECT id FROM sets WHERE name LIKE '%$value%' OR other_names LIKE '%$value%')";
                            break;

                        case "artist":
                            $where .= " AND artist LIKE '%".$value."%'";
                            break;

                        case "description":
                            $where .= " AND description LIKE '%".$value."%'";
                            break;

                        case "min_price":
                            $where .= " AND price >= $value";
                            break;

                        case "max_price":
                            $where .= " AND price <= $value";
                            break;

                        case "min_stock":
                            $where .= " AND stock >= $value";
                            break;

                        case "max_stock":
                            $where .= " AND stock <= $value";
                            break;

                        default:
                            break;
                    }
                }

                // search
                $query = "SELECT * FROM cards WHERE $where;";
    
                $ex = mysqli_query($conn, $query);

                if (mysqli_num_rows($ex) > 0) {
                    $card_list = array();

                    while ($res = mysqli_fetch_assoc($ex)) {
                        // $card = new Card($res['name'],
                        //                 $res['other_names'],
                        //                 $res['color'],
                        //                 $res['type'],
                        //                 $res['rarity'],
                        //                 $res['set_id'],
                        //                 $res['artist'],
                        //                 $res['image_url'],
                        //                 $res['description'],
                        //                 $res['price'],
                        //                 $res['stock']);

                        array_push($card_list, $res);
                    }
                                   
                    $return = json_encode($card_list);
                    CheckCardCache($card_list);
                }
                else {
                    $return = "No cards where found that match your critearia";
                }
            }
            else {
                $return = "Invalid method for API call /card/list. Expecting POST, received " . $method;
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
                    if (isset($data['name']) && isset($data['set_id'])) {
                        $insert_fields = '';
                        $insert_values = '';

                        $name = $data['name'];
                        $insert_fields .= 'name';
                        $insert_values .= "\"".$name."\"";

                        $set_id = $data['set_id'];
                        $insert_fields .= ', set_id';
                        $insert_values .= ", $set_id";

                        if (isset($data['color'])) {
                            $color = $data['color'];
                            $insert_fields .= ', color';
                            $insert_values .= ", \"$color\"";
                        }

                        if (isset($data['type'])) {
                            $type = $data['type'];
                            $insert_fields .= ', type';
                            $insert_values .= ", \"$type\"";
                        }

                        if (isset($data['rarity'])) {
                            $rarity = $data['rarity'];
                            $insert_fields .= ', rarity';
                            $insert_values .= ", \"$rarity\"";
                        }

                        if (isset($data['artist'])) {
                            $artist = $data['artist'];
                            $insert_fields .= ', artist';
                            $insert_values .= ", \"$artist\"";
                        }

                        if (isset($data['image_url'])) {
                            $image_url = $data['image_url'];
                            $insert_fields .= ', image_url';
                            $insert_values .= ", \"$image_url\"";
                        }

                        if (isset($data['description'])) {
                            $description = $data['description'];
                            $insert_fields .= ', description';
                            $insert_values .= ", \"$description\"";
                        }

                        if (isset($data['price'])) {
                            $price = $data['price'];
                            $insert_fields .= ', price';
                            $insert_values .= ", $price";
                        }

                        if (isset($data['stock'])) {
                            $stock = $data['stock'];
                            $insert_fields .= ', stock';
                            $insert_values .= ", $stock";
                        }
                        
                        if (isset($data['other_names'])) {
                            $other_names = $data['other_names'];
                            $insert_fields .= ', other_names';
                            $insert_values .= ", '".str_replace("'", "\'", json_encode($other_names))."'";
                        }
        
                        // check if card already exists (ignoring lands)
                        $query = "SELECT * FROM cards WHERE name = \"$name\" AND type != \"land\";";
        
                        $ex = mysqli_query($conn, $query);

                        if (mysqli_num_rows($ex) == 0) {
                            // confirm if API key matches saved token
                            if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                                // insert
                                $query = "INSERT INTO cards ($insert_fields) VALUES ($insert_values);";

                                $ex = mysqli_query($conn, $query);
            
                                if (mysqli_affected_rows($conn) == 1) {
                                    $return = "Card was created";
                                    UpdateCardCache($conn);
                                }
                                else {
                                    $return = "Something went wrong when creating your card. Please, check the information sent and try again";
                                }
                            }
                            else {
                                $return = "Provided API key is incorrect. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "A card with provided name already exists. Please, try again with another name";
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

                        if (isset($data['set_id'])) {
                            $set_id = $data['set_id'];
                        }

                        if (isset($data['color'])) {
                            $color = $data['color'];
                        }

                        if (isset($data['type'])) {
                            $type = $data['type'];
                        }

                        if (isset($data['rarity'])) {
                            $rarity = $data['rarity'];
                        }

                        if (isset($data['artist'])) {
                            $artist = $data['artist'];
                        }

                        if (isset($data['image_url'])) {
                            $image_url = $data['image_url'];
                        }

                        if (isset($data['description'])) {
                            $description = $data['description'];
                        }

                        if (isset($data['price'])) {
                            $price = $data['price'];
                        }

                        if (isset($data['stock'])) {
                            $stock = $data['stock'];
                        }

                        if (isset($data['other_names'])) {
                            $other_names = $data['other_names'];
                        }
                        
                        // get target card
                        $query = "SELECT * FROM cards WHERE id = '$url_id';";

                        $ex = mysqli_query($conn, $query);

                        if (mysqli_num_rows($ex) == 1) {
                            $res = mysqli_fetch_assoc($ex);

                            if (isset($name) && $res['name'] != $name) {
                                $update_fields .= ', name = "'.$name.'"';
                            }

                            if (isset($set_id) && $res['set_id'] != $set_id) {
                                $update_fields .= ', set_id = '.$set_id;
                            }

                            if (isset($color) && $res['color'] != $color) {
                                $update_fields .= ', color = "'.$color.'"';
                            }

                            if (isset($type) && $res['type'] != $type) {
                                $update_fields .= ', type = "'.$type.'"';
                            }

                            if (isset($rarity) && $res['rarity'] != $rarity) {
                                $update_fields .= ', rarity = "'.$rarity.'"';
                            }

                            if (isset($artist) && $res['artist'] != $artist) {
                                $update_fields .= ', artist = "'.$artist.'"';
                            }

                            if (isset($image_url) && $res['image_url'] != $image_url) {
                                $update_fields .= ', image_url = "'.$image_url.'"';
                            }

                            if (isset($description) && $res['description'] != $description) {
                                $update_fields .= ', description = "'.$description.'"';
                            }

                            if (isset($price) && $res['price'] != $price) {
                                $update_fields .= ', price = '.$price;
                            }

                            if (isset($stock) && $res['stock'] != $stock) {
                                $update_fields .= ', stock = '.$stock;
                            }
                            
                            if (isset($other_names) && $res['other_names'] != $other_names) {
                                $update_fields .= ', other_names = \''.str_replace("'", "\'", json_encode($other_names)).'\'';
                            }

                            // confirm if API key matches saved token
                            if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                                // update
                                $update_fields = substr($update_fields, 1);
                                $query = "UPDATE cards SET $update_fields WHERE id = '$url_id';";

                                $ex = mysqli_query($conn, $query);

                                if (mysqli_affected_rows($conn) == 1) {
                                    $return = "Card was updated";
                                    UpdateCardCache($conn);
                                }
                                else {
                                    $return = "Something went wrong when updating your card. Please, check the information sent and try again";
                                }
                            }
                            else {
                                $return = "Provided API key is incorrect. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "A card with provided ID could not be found. Please, check the information sent and try again";
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
                    $query = "SELECT * FROM cards WHERE id = '$url_id';";

                    $ex = mysqli_query($conn, $query);

                    if (mysqli_num_rows($ex) == 1) {
                        // confirm if API key matches saved token
                        if ($apiKey == 'REa2xLY_C0mP^7E+_57r1n6') { // needs a better validation, but this is for the sake of exemplification
                            // delete
                            $query = "DELETE FROM cards WHERE id = '$url_id';";

                            $ex = mysqli_query($conn, $query);

                            if (mysqli_affected_rows($conn) == 1) {
                                $return = "Card was removed";
                                UpdateCardCache($conn);
                            }
                            else {
                                $return = "Something went wrong when deleting your card. Please, check the information sent and try again";
                            }
                        }
                        else {
                            $return = "Provided API key is incorrect. Please, check the information sent and try again";
                        }
                    }
                    else {
                        $return = "A card with provided ID could not be found. Please, check the information sent and try again";
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

        default:
            $array = array();
            $array['group'] = '/cards';
            $array['endpoints'] = ['/list', '/create', '/update/{id}', '/delete/{id}'];
            $array['extra'] = "One of the two main parts, basic CRUD for card management. Please note that the create part requests a set ID, and this is as planned, thinking this implementation would be used in a website or by someone who already have said ID to begin with";

            $return = json_encode($array);
            break;
    }
}

function CheckCardCache($incoming) {
    $path = 'cache\cached_card.json';

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

function UpdateCardCache($connection) {
    $path = 'cache\cached_cards.json';

    $query = "SELECT * FROM cards";

    $ex = mysqli_query($connection, $query);

    if (mysqli_num_rows($ex) > 0) {
        $card_list = array();

        while ($res = mysqli_fetch_assoc($ex)) {
            array_push($card_list, $res);
        }
                        
        $cache = json_encode($card_list);
        file_put_contents($path, $cache);
    }
}

?>