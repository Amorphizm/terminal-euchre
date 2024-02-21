<?php 

class Card 
{
    public int $level;
    public string $suit;
    public string $type;
    public string $name;

    public function __construct(string $suit, string $type, int $level)
    {
        $this->suit = $suit;
        $this->type = $type;
        $this->level = $level;
        $this->name = "$type of $suit" . 's';
    }
}

?>
