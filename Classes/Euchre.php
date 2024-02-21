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
    public ?Player $dealer = null;
    public string|null $trump = null;
    public array $dealerPosition = [0, 0]; // index 0 is the team, index 1 is the player, used to traverse 2d teams array.
    // The first partnership to score 5, 7 or 10 points, as agreed beforehand, wins the game.
    private array $pointsToWinChoices = ['5', '7', '10'];

    public function __construct()
    {
        // Game setup
        $this->getPointsToWin();
        $this->getStickTheDealerValue();
        $this->createTeams();
        $this->clearScreen();

        // Start game
        // while (!$this->gameOver) {
            // Who is dealing this trick?
            $this->dealer = $this->getPlayerAtPosition(!$this->dealer ? [0, 0] : $this->dealer->nextPlayerPosition);
            $this->dealer->isDealer = true;
            // Init a new shuffled deck.
            $this->deck = new Deck();
            // deal cards
            $this->dealCards();
            // go around and see who wants to call it
                // stick the dealer or no? Handle it.
            $this->determineTrump();
            // trick begin, loop over players for turns.
            // trick over, apply points to winning team for this trick.
            // game won check, set gameOver to true if so.
            // set new dealer position if game not over.
        // }

        // Game over
        // Display message, fun stats about the game?
        // Play again?
    }

    #region game logic
    /**
     * Iterate over each player and see who wants to call it.
     * 
     * @return string|null
     */
    private function determineTrump(): string|null
    {
        $flippedCard = $this->deck->cards[0];
        echo "{$this->dealer->name} flipped a $flippedCard->type of $flippedCard->suit's!\n";

        // Iterate over players and see who wants to order up the flipped card.
        $player = null;
        for ($i = 0; $i < 4; $i++) {
            $player = $this->getPlayerAtPosition($player?->nextPlayerPosition ?? $this->dealer->nextPlayerPosition);
            
            if ($player->orderUpCardCheck($flippedCard, $this->dealer->name)) {
                // $this->dealer->processOrderUp
            }
        }

        // Card wasn't ordered up? Iterate over the players again and see if anyone wants to call it.

        // Nobody called it? Figure out if its stick the dealer or not.

        return null;
    }

    /**
     * Deal cards to players before the trick begins.
     * 
     * @return void
     */
    private function dealCards(): void
    {
        // Specify who the dealer is.
        echo "{$this->dealer->name} from team {$this->dealer->teamNum} is dealing the cards!\n";

        // Player iteration for dealing cards. Deal cards to dealer last.
        $player = null;
        for ($i = 0; $i < 4; $i++) {
            $player = $this->getPlayerAtPosition($player?->nextPlayerPosition ?? $this->dealer->nextPlayerPosition);

            for ($j = 0; $j < 5; $j++) $player->hand[] = array_pop($this->deck->cards);
            echo "Delt 5 cards to $player->name. ";
        }

        echo "\n";
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
                        array_push($team['players'], new Human( // Humans for now, implement Bots later though.
                            $input, 
                            $teamNum,
                            [$teamNum - 1, $i],
                        ));
                        $validInput = true;
                    }
                }
            }

            array_push($this->teams, $team);
        }
    }
    #endregion

    #region helper functions
    /**
     * Returns the player object at the given position. 
     * 
     * @return Player
     */
    private function getPlayerAtPosition(array $position): Player
    {
        return $this->teams[$position[0]]['players'][$position[1]];
    }

    /**
     * Clears the terminal of text.
     * 
     * @return void
     */
    private function clearScreen(): void
    {
        echo chr(27).chr(91).'H'.chr(27).chr(91).'J'; //^[H^[J
    }
    #endregion
}

?>
