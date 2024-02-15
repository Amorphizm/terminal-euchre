<?php

class Player 
{
    public string $name;
    public array $hand = [];

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

?>
