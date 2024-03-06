<?php 

class Card 
{
    public int $level;
    public string $suit;
    public string $type;
    public string $name;
    public int $trumpLevel;
    public string $leftBower;

    public function __construct(array $suitWithLeft, string $type, int $level, int $trumpLevel)
    {
        $this->suit = $suitWithLeft[0];
        $this->type = $type;
        $this->level = $level;
        $this->trumpLevel = $trumpLevel;
        $this->leftBower = $type == 'Jack' ? $suitWithLeft[1] : '';
        $this->name = "$type of $this->suit" . 's';
    }
    
    public function getValue(string $playedSuit, string $trump): int
    {
        // Left bower check.
        if ($this->type == 'Jack' && $this->leftBower == $trump) return 12;

        // Trump check.
        if ($this->suit == $trump) return $this->trumpLevel;

        // Played suit check.
        if ($this->suit == $playedSuit) return $this->level;

        return 0; // No suit, no trump, no points :(
    }
}

?>
