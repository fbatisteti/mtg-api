<?php

class Card
{
    // attributes
    private $name;
    private $other_names;
    private $color;
    private $type;
    private $rarity;
    private $set_id;
    private $artist;
    private $image_url;
    private $description;
    private $price;
    private $stock;
    
    // construct
    public function __construct($name, $other_names, $color, $type, $rarity, $set_id, $artist, $image_url, $description = "", $price, $stock = "") {
        $this->name = $name;
        $this->other_names = $other_names;
        $this->color = $color;
        $this->type = $type;
        $this->rarity = $rarity;
        $this->set_id = $set_id;
        $this->artist = $artist;
        $this->image_url = $image_url;
        if ($description == "") {
            $this->description = $this->GetDescription();
        }
        else {
            $this->description = $description;
        }
        $this->price = $price;
        if ($stock = "") {
            $this->stock = $this->GetRandomStock();
        }
        else {
            $this->stock = $stock;
        }
    }

    // getters and setters
    public function GetName() { return $this->name; }
    public function GetOtherNames() { return $this->other_names; }
    public function GetColor() { return $this->color; }
    public function GetType() { return $this->type; }
    public function GetRarity() { return $this->rarity; }
    public function GetSetId() { return $this->set_id; }
    public function GetArtist() { return $this->artist; }
    public function GetImageUrl() { return $this->image_url; }
    public function GetDescriptionValue() { return $this->description; }
    public function GetPrice() { return $this->price; }
    public function GetStock() { return $this->stock; }

    public function SetName($name) { $this->name = $name; }
    public function SetOtherNames($other_names) { $this->other_names = $other_names; }
    public function SetColor($color) { $this->color = $color; }
    public function SetType($type) { $this->type = $type; }
    public function SetRarity($rarity) { $this->rarity = $rarity; }
    public function SetSetId($set_id) { $this->set_id = $set_id; }
    public function SetArtist($artist) { $this->artist = $artist; }
    public function SetImageUrl($image_url) { $this->image_url = $image_url; }
    public function SetDescription($description) { $this->description = $description; }
    public function SetPrice($price) { $this->price = $price; }
    public function SetStock($stock) { $this->stock = $stock; }

    // methods
    private function GetDescription() {
        $conditions = array(
            "Mint condition",
            "Encased in acrylic",
            "Normal use",
            "Bad condition"
        );
    
        $shippings = array(
            "Shipping paid by buyer",
            "Shipping paid by seller",
            "No shipping, delivery in hands",
            "Just showing off :)"
        );
        
        return $conditions[array_rand($conditions)] . " - " . $shippings[array_rand($shippings)];
    }

    private function GetRandomStock() {
            return rand(0, 100);
    }

    public function SaveToDB($connection) {
        // confirm if not added already
        $query = "SELECT * FROM cards WHERE name = \"".$this->GetName()."\";";
        $ex = mysqli_query($connection, $query);

        if (mysqli_num_rows($ex) == 0) {
            $fields = "name";
            $fields .= ", other_names";
            $fields .= ", color";
            $fields .= ", type";
            $fields .= ", rarity";
            $fields .= ", set_id";
            $fields .= ", artist";
            $fields .= ", image_url";
            $fields .= ", description";
            $fields .= ", price";
            $fields .= ", stock";

            $values = "\"".$this->GetName()."\"";
            $values .= ", \"".$this->GetOtherNames()."\"";
            $values .= ", \"".$this->GetColor()."\"";
            $values .= ", \"".$this->GetType()."\"";
            $values .= ", \"".$this->GetRarity()."\"";
            $values .= ", \"".$this->GetSetId()."\"";
            $values .= ", \"".$this->GetArtist()."\"";
            $values .= ", \"".$this->GetImageUrl()."\"";
            $values .= ", \"".$this->GetDescriptionValue()."\"";
            $values .= ", \"".$this->GetPrice()."\"";
            $values .= ", \"".$this->GetStock()."\"";

            $query = "INSERT INTO cards ($fields) VALUES ($values);";
            $ex = mysqli_query($connection, $query);
        }
    }
}

?>