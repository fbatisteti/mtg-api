<?php

require('db.php');

// generic variables
$method = $_SERVER['REQUEST_METHOD'];
$base_url = $_SERVER['SCRIPT_NAME'];
$endpoint = $_SERVER['REQUEST_URI'];
$params = $_SERVER['QUERY_STRING'];

// parameters
$endpoint = str_replace('?'.$params, '', $endpoint);
$params = explode('&', $params);

$params2 = array();
foreach ($params as $p) {
    $i = strpos($p, '=');
    $key = substr($p, 0, $i);
    $value = substr($p, $i+1);

    $params2[$key] = $value;
}
$params = $params2;

// endpoints
$endpoint = str_replace($base_url, '', $endpoint);
$base_endpoint = '';
$specific_endpoint = '';

if ($endpoint != "" && $endpoint != '/') {
    try {
        $base_endpoint = '/'.explode('/', $endpoint)[1];
        $specific_endpoint = str_replace($base_endpoint, '', $endpoint);
    }
    catch (\Throwable $th) {
        $endpoint = "/";
        $base_endpoint = $specific_endpoint = '';
    }    
}
else if ($endpoint == '' || $endpoint == '/') {
    $base_endpoint = '/';
}

// routing
$return = '';
switch ($base_endpoint) {
    case '/':
        $array = array();
        $array['group'] = 'MTG-API';
        $array['swagger'] = '';
        $array['github'] = '';
        $array['about'] = 'This was just a random study, though I had lots of fun making it. It is NOT complete, as some things are clearly missing, like encrypting sensitive information, validating inserted data and so on... but, again, this is but a study :)';
        $array['extra'] = "You can also run the /test endpoint to fill the database with some cards (top 1000 cards from Scryfall API from 2024-07-07 search, and all sets so far) :)";

        $return = json_encode($array);
        break;

    case "/user":
        require_once("endpoints/user.php");
        break;

    case "/auth":
        require_once("endpoints/auth.php");
        break;

    case "/sets":
        require_once("endpoints/sets.php");
        break;

    case "/cards":
        require_once("endpoints/cards.php");
        break;

    case "/test":
        Test($conn);
        break;

    default:
        $return = 'Invalid endpoint';

        break;
}

echo ($return);
exit;

function Test($connection) {
    // sets
    require('classes\set.class.php');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.scryfall.com/sets');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $setsArray = json_decode($response, true);
    $setsArray = $setsArray['data'];

    foreach ($setsArray as $set) {
        $name = $set['name'] . ' - ' . $set['code'];
        $other_names = '';
        $release_date = $set['released_at'];
        $set_size = $set['card_count'];

        $new_set = new Set($name, $other_names, $release_date, $set_size);
        $new_set->SaveToDB($connection);
    }

    // cards
    require('classes\card.class.php');

    $file = 'scryfall_2024-07-07_top1000.json';

    $jsonData = file_get_contents($file);
    $data = json_decode($jsonData, true);

    foreach ($data as $card) {
        $name = $card['name'];

        if (isset($card['image_uris']['png'])) {
            $image_url = $card['image_uris']['png'];
        }
        else {
            $image_url = "";
        }
        
        $type = $card['type_line'];

        $cardColor = '';
        if (isset($card['colors'])) {
            $colors = $card['colors'];

            for ($i=0; $i < count($colors); $i++) {
                $cardColor .= $colors[$i];
            }
            if ($cardColor == "") { $cardColor = "C"; } // colorless
        }

        $set_name = $card['set_name'] . ' - ' . $card['set'];
        
        $rarity = $card['rarity'];
        switch ($rarity) {
            case "common":
                $rarity = "C";
                break;

            case "uncommon":
                $rarity = "U";
                break;

            case "rare":
                $rarity = "R";
                break;

            case "mythic":
                $rarity = "M";
                break;

            default:
                $rarity = "";
                break;
        }
        
        $artist = $card['artist'];
        $price = $card['prices']['usd'];

        $query = "SELECT id FROM sets WHERE name LIKE \"%".$card['set_name']."%\"";
        $ex = mysqli_query($connection, $query);

        if (mysqli_num_rows($ex) == 0) {
            // pass, set is not there
        }
        else {
            $res = mysqli_fetch_assoc($ex);

            $new_card = new Card($name, '', $cardColor, $type, $rarity, $res['id'], $artist, $image_url, "", $price, "");
            $new_card->SaveToDB($connection);
        }
    }
}

?>