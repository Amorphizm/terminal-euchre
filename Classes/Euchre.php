<?php

class Euchre
{
    public array $players;
    public int $pointsToWin;
    public bool $stickTheDealer;

    // Default to 4. Maybe this can be expanded upon to make a euchre game that takes more than 4 players max?
    private int $numPlayers = 4;
    // The first partnership to score 5, 7 or 10 points, as agreed beforehand, wins the game.
    private array $pointsToWinChoices = ['5', '7', '10'];

    public function __construct()
    {
        $this->getPointsToWin();
        // get stick the dealer
        // create players
        
    }

    private function getPointsToWin(): void
    {
        $pointsToWinMsg = "Please enter the amount of points needed to win the game! Can be chosen between";
        foreach ($this->pointsToWinChoices as $key => $value) {
            if ($key === count($this->pointsToWinChoices) - 1) {
                $pointsToWinMsg .= " and $value.\n";
                break;
            }

            $pointsToWinMsg .= " $value,";
        }
        
        $validInput = false;
        while (!$validInput) {
            echo $pointsToWinMsg;

            $input = readLine("Enter winning amount: ");
            if (!is_numeric($input) || !in_array($input, $this->pointsToWinChoices)) {
                echo "Ooops! Looks like that input is not valid. Try again!\n";
            } else {
                $this->pointsToWin = intval($input);
                $validInput = true;
            }
        }
    }
}

?>
