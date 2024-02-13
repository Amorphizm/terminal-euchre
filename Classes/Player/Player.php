<?php

class Player 
{
    public array $hand;
    public string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }
}

?>
