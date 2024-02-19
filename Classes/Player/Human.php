<?php

require_once(__DIR__ . '/Player.php');

class Human extends Player
{
    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    /**
     * See if this player wants to order up the card to the dealer to declare trump.
     * @param Card $card
     * @param string $dealerName
     * @param bool $isDealer
     * 
     * @return bool
     */
    public function orderUpCardCheck(Card $card, string $dealerName, bool $isDealer): bool
    {
        $input = null;
        $validInput = false;
        $validInputs = ['y', 'yes', 'n', 'no'];

        while (!$validInput) {
            echo "$this->name - Would you like " . ($isDealer ? '' : $dealerName . " ") . "to pick up the $card->type of $card->suit's?\n";
            $input = readLine("yes or no: ");

            $validInput = in_array(strtolower($input), $validInputs);
            if (!$validInput) echo "Whoops! You entered something incorrect. Please try again!\n";
        }

        return in_array(strtolower($input), ['y', 'yes']);
    }
}

?>
