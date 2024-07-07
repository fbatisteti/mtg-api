<?php

class Set
{
    // attributes
    private $name;
    private $other_names;
    private $release_date;
    private $set_size;    
    
    // construct
    public function __construct($name, $other_names, $release_date, $set_size) {
        $this->name = $name;
        $this->other_names = $other_names;
        $this->release_date = $release_date;
        $this->set_size = $set_size;
    }

    // getters and setters
    public function GetName() { return $this->name; }
    public function GetOtherNames() { return $this->other_names; }
    public function GetReleaseDate() { return $this->release_date; }
    public function GetSetSize() { return $this->set_size; }

    public function SetName($name) { $this->name = $name; }
    public function SetOtherName($other_names) { $this->other_names = $other_names; }
    public function SetReleaseDate($release_date) { $this->release_date = $release_date; }
    public function SetSetSize($set_size) { $this->set_size = $set_size; }

    // methods
    public function SaveToDB($connection) {
        // confirm if not added already
        $query = "SELECT * FROM sets WHERE name = \"".$this->GetName()."\";";
        $ex = mysqli_query($connection, $query);

        if (mysqli_num_rows($ex) == 0) {
            $query = "INSERT INTO sets (name, release_date, size) VALUES (\"".$this->GetName()."\", \"".$this->GetReleaseDate()."\", \"".$this->GetSetSize()."\");";
            $ex = mysqli_query($connection, $query);
        }
    }
}

?>