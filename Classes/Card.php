<?php 

class Card 
{
    public int $level;
    public string $suit;
    public string $face;

    public function __construct(string $suit, string $face, int $level)
    {
        $this->suit = $suit;
        $this->face = $face;
        $this->level = $level;
    }
}

?>
