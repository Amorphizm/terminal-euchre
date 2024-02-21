<?php

require_once(__DIR__ . '/Player.php');

class Human extends Player
{
    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        $input = null;
        $validInput = false;
        $validInputs = ['y', 'yes', 'n', 'no'];

        while (!$validInput) {
            echo "$this->name - Would you like " . ($this->isDealer ? '' : $dealerName . " ") . "to pick up the $card->type of $card->suit's?\n";
            $input = readLine("yes or no: ");

            $validInput = in_array(strtolower($input), $validInputs);
            if (!$validInput) echo "Whoops! You entered something incorrect. Please try again!\n";
        }

        return in_array(strtolower($input), ['y', 'yes']);
    }
}

?>
