<?php

require_once(__DIR__ . '/Player.php');

class Human extends Player
{
    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    public function processOrderUp(Card $card): void
    {
        $this->displayHand();
    }

    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        $input = null;
        $validInput = false;
        $validInputs = ['y', 'yes', 'n', 'no'];

        while (!$validInput) {
            echo "$this->name - Would you like " . ($this->isDealer ? '' : $dealerName . " ") . "to pick up the $card->name?\n";
            $input = readLine("yes or no: ");

            $validInput = in_array(strtolower($input), $validInputs);
            if (!$validInput) echo "Whoops! You entered something incorrect. Please try again!\n";
        }

        return in_array(strtolower($input), ['y', 'yes']);
    }

    /**
     * Display the player's hand to them.
     * 
     * @return void
     */
    private function displayHand(): void
    {
        $cards = '';
        foreach ($this->hand as $key => $card) {
            $divider = $key == 4 ? '' : ' | ';
            $cards .= ($key + 1) . '. ' . $card->name . $divider;
        }

        echo "Your hand - \n$cards\n";
    }
}

?>
