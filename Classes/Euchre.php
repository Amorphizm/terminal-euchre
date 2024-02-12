<?php

require_once("Player.php");

class Euchre
{
    public int $pointsToWin;
    public array $teams = [];
    public bool $stickTheDealer;
    // The first partnership to score 5, 7 or 10 points, as agreed beforehand, wins the game.
    private array $pointsToWinChoices = ['5', '7', '10'];

    public function __construct()
    {
        // Game setup
        $this->getPointsToWin();
        $this->getStickTheDealerValue();
        $this->createTeams();

        // Start game
        
    }

    #region game logic
    
    #engregion

    #region setup logic
    /**
     * Lets the user choose how many points they would like required to win the game.
     * 
     * @return void
     */
    private function getPointsToWin(): void
    {
        $pointsToWinMsg = "Please enter the amount of points needed to win the game! Can choose between";
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

    /**
     * Lets the user choose if they would like to stick the dealer or not.
     * 
     * @return void
     */
    private function getStickTheDealerValue(): void
    {
        $validInput = false;
        $validInputs = ['y', 'Y', 'n', 'N'];

        while (!$validInput) {
            echo "Stick the dealer, or nah?\n";

            $input = readLine("Enter y or n: ");
            if (!in_array($input, $validInputs)) {
                echo "Ooops! Looks like that input is not valid. Try again!\n";
            } else {
                $this->stickTheDealer = strtolower($input) === 'y'; 
                $validInput = true;
            }
        }
    }

    /**
     * Create the players.
     * 
     * @return void
     */
    private function createTeams(): void
    {
        $maxCharsForName = 15;
        while (($teamNum = count($this->teams) + 1) <= 2) {
            $team = [ // Should this be its own class?
                'points' => 0,
                'players' => [],
            ];
            echo "Setup for team $teamNum.\n";

            // Two players per team.
            for ($i = 0; $i < 2; $i++) {
                $validInput = false;
                $firstOrSecond = $i == 0 ? 'first' : 'second';

                while (!$validInput) {
                    $input = readLine("Enter a username for team $teamNum's $firstOrSecond player: ");
                    if (strlen($input) > $maxCharsForName) {
                        echo "Ooops! Looks like that username it too long (15 chars or less please). Try again!\n";
                    } else {
                        array_push($team['players'], new Player($input));
                        $validInput = true;
                    }
                }
            }

            array_push($this->teams, $team);
        }
    }
    #endregion
}

?>
