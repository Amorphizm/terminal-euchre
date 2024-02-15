<?php

require_once(__DIR__ . '/Deck.php');
require_once(__DIR__ . '/Player/Human.php');

class Euchre
{
    public Deck $deck;
    public int $pointsToWin;
    public array $teams = [];
    public bool $stickTheDealer;
    public bool $gameOver = false;
    public array $dealerPosition = []; // index 0 is the team, index 1 is the player, used to traverse 2d teams array.
    // The first partnership to score 5, 7 or 10 points, as agreed beforehand, wins the game.
    private array $pointsToWinChoices = ['5', '7', '10'];

    public function __construct()
    {
        // Game setup
        $this->getPointsToWin();
        $this->getStickTheDealerValue();
        $this->createTeams();

        // Start game
        // while (!$this->gameOver) {
            // Init a new shuffled deck.
            $this->deck = new Deck();
            // deal cards
            $this->dealCards();
            // go around and see who wants to call it
                // stick the dealer or no? Handle it.
            // trick begin, loop over players for turns.
            // trick over, apply points to winning team for this trick.
            // game won check, set gameOver to true if so.
        // }

        // Game over
        // Display message, fun stats about the game?
        // Play again?
    }

    #region game logic
    /**
     * Deal cards to players before the trick begins.
     * 
     * @return void
     */
    private function dealCards(): void
    {
        // Who is dealing the cards given the previous dealer's position?
        $this->dealerPosition = $this->getNextPlayerPosition($this->dealerPosition);
        $team = $this->dealerPosition[0] + 1;
        $player = $this->teams[$this->dealerPosition[0]]['players'][$this->dealerPosition[1]];
        echo "$player->name from team $team is dealing the cards!\n";

        // Player iteration for dealing cards.
        $deltCount = 0;
        $positionToDealTo = $this->dealerPosition;
        while ($deltCount != 4) {
            // Move cards from deck to player's hand.
            $player = $this->teams[$positionToDealTo[0]]['players'][$positionToDealTo[1]];
            for ($i = 0; $i < 5; $i++) $player->hand[] = array_pop($this->deck->cards);
            echo "Delt 5 cards to $player->name. ";
            
            $positionToDealTo = $this->getNextPlayerPosition($positionToDealTo);
            $deltCount++;
        }

        echo "\n";
    }

    /**
     * Given an player position, who should be the next player in the iteration?
     * TOOD - I hardcoded this, but it could be much better probably. I am just lazy :(
     * 
     * @return array
     */
    private function getNextPlayerPosition(array $position = []): array
    {
        if ($position && $position[0] == 0 && $position[1] == 0) return [1, 0]; // Was P1 from team 1, return P1 from team 2.
        if ($position && $position[0] == 1 && $position[1] == 0) return [0, 1]; // Was P1 from team 2, return P2 from team 1.
        if ($position && $position[0] == 0 && $position[1] == 1) return [1, 1]; // Was P2 from team 1, return P2 from team 2.

        return [0, 0]; // Game is starting or we just finished the rotation, return P1 from team 1.
    }
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
                        array_push($team['players'], new Human($input)); // Humans for now, implement Bots later though.
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
