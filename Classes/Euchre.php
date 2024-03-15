<?php

require_once(__DIR__ . '/Deck.php');
require_once(__DIR__ . '/Player/Human.php');

class Euchre
{
    public Deck $deck;
    public int $pointsToWin;
    public array $teams = [];
    public bool $stickTheDealer;
    public ?Player $dealer = null;
    public string|null $trump = null;
    public array $sittingOutPosition = []; // position of a player who is sitting out in a given trick. 
    public string $cardsPlayedDisplay = '';
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
        while (true) if ($this->isGameOver()) break;

        // Game over
        // Display message, fun stats about the game?
        // Play again?
    }

    #region game logic
    /**
     * The main game loop! 
     * 
     * @return bool true if the game is over
     */
    private function isGameOver(): bool
    {
        $this->setValuesForNextTrick();

        // Determine what trump is for this trick.
        while (true) {
            // Init a new deck and deal the cards.
            $this->deck = new Deck();
            $this->dealCards();

            // Go around and see who wants to call trump.
            $this->trump = $this->determineTrump();
            if ($this->trump) break;

            // Trump could not be determined. Set the next dealer and try again.
            $this->clearScreen();
            echo "Could not determine trump :( Lets re-deal the cards and try again!\n";
            $this->setDealer();
            sleep(3);
            $this->clearScreen();
        }

        $this->clearScreen();
        $this->playTrick();
        // $this->playTrick() ? 
        // if we have a trump then the trick begins, loop over players for turns.
        // Note that if a players isSittingOut value is true then skip them. That means their partner is going alone.
        // trick over, apply points to winning team for this trick.
        // game won check, break if so.

        return true; // for testing!
    }

    private function playTrick(): void
    {
        // Left of the dealer starts. Then use player position that is passed in since they had the highest card on the previous point.
        $playerToStartPos = $this->dealer->nextPlayerPosition;
        for ($i = 0; $i < 5; $i++) $playerToStartPos = $this->playTrickPoint($playerToStartPos);

        // Verify trick points after playing one out. Then decide how to actually apply that to the overall match points.
        echo json_encode($this->teams);
    }

    private function playTrickPoint(array $positionToStart): array
    {
        $player = null;
        $playedCards = [];
        $winningTeamNum = 0;
        $suitToFollow = null;
        $playerWithHighestCard = null;

        for ($i = 0; $i < 4; $i++) {
            $player = $this->getPlayerAtPosition($player?->nextPlayerPosition ?? $positionToStart);
            if ($player->isSittingOut) continue;

            // Display the cards that have been played.
            echo "Trump for this trick is $this->trump" . "s!\n";
            if ($this->cardsPlayedDisplay) echo $this->cardsPlayedDisplay . "\n" ;

            $canFollowSuit = $this->canFollowSuit($player, $suitToFollow, $this->trump);
            $playedCards[] = $player->playCard($suitToFollow, $canFollowSuit, $this->trump);
            if (!$suitToFollow) {
                if ($playedCards[$i]->type == 'Jack' && $playedCards[$i]->leftBower == $this->trump) {
                    $suitToFollow = $this->trump;   
                } else {
                    $suitToFollow = $playedCards[$i]->suit;
                }
            }

            $this->cardsPlayedDisplay .= $playedCards[$i]->name . " -> ";
            // echo json_encode($playedCards) . "\n";
            if ($i != 0) echo "Comparing " . $playedCards[$i - 1]->getValue($suitToFollow, $this->trump) ." to " . $playedCards[$i]->getValue($suitToFollow, $this->trump) . "\n";
            if ( // First card to be played or is better than the previous card then set their team num as the current winning team.
                $i == 0 || 
                $playedCards[$i - 1]->getValue($suitToFollow, $this->trump) < $playedCards[$i]->getValue($suitToFollow, $this->trump)
            ) {
                $winningTeamNum = $player->teamNum - 1;
                $playerWithHighestCard = $player;
            }

            // $this->clearScreen();
        }

        echo json_encode($this->teams[$winningTeamNum]['trickPoints']) . "\n";
        $this->cardsPlayedDisplay = '';
        $this->teams[$winningTeamNum]['trickPoints'] += 1;
        return $playerWithHighestCard->position;
    }

    /**
     * Upkeep that needs to be done before starting a trick.
     * 
     * @return void
     */
    private function setValuesForNextTrick(): void
    {
        $this->trump = null;

        if ($this->sittingOutPosition) {
            ($this->getPlayerAtPosition($this->sittingOutPosition))->isSittingOut = false;
            $this->sittingOutPosition = [];
        }

        foreach ($this->teams as $team) {
            $team['calledTrump'] = false;
            $team['trickPoints'] = 0;
        }

        $this->setDealer();
    }

    /**
     * Iterate over each player and see who wants to call it.
     * 
     * @return string|null
     */
    private function determineTrump(): string|null
    {
        $flippedCard = $this->deck->cards[0];
        echo "{$this->dealer->name} flipped a $flippedCard->name!\n";

        // Iterate over players and see who wants to order up the flipped card.
        $player = null;
        for ($i = 0; $i < 4; $i++) {
            $player = $this->getPlayerAtPosition($player?->nextPlayerPosition ?? $this->dealer->nextPlayerPosition);
            
            if ($player->orderUpCardCheck($flippedCard, $this->dealer->name)) {
                $this->clearScreen();
                echo "$player->name has ordered up the $flippedCard->name.\n";
                $this->teams[$player->teamNum]['calledTrump'] = true;

                $this->aloneCheck($player);
                $this->dealer->processOrderUp($flippedCard);
                return $flippedCard->suit;
            }
        }

        $this->clearScreen();

        // Card wasn't ordered up? Iterate over the players again and see if anyone wants to call it.
        $player = null;
        for ($i = 0; $i < 4; $i++) {
            $player = $this->getPlayerAtPosition($player?->nextPlayerPosition ?? $this->dealer->nextPlayerPosition);
            
            $suit = $player->selectTrump($this->stickTheDealer);
            if ($suit) {
                $this->teams[$player->teamNum]['calledTrump'] = true;
                $this->aloneCheck($player);
                return $suit;
            }
        }

        return null;
    }

    /**
     * See if the given player would like to go alone and if so set their partners isSittingOut value to true.
     * 
     * @return void
     */
    private function aloneCheck(Player $player): void
    {
        if (!$player->processAloneCheck()) return;
        $partner = $this->getPlayerAtPosition($player->partnerPosition);

        echo "$partner->name is sitting out this trick!\n";
        sleep(3);
        $partner->isSittingOut = true;
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

            // Clear out the player's old hand before adding cards.
            $player->hand = [];
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

        $this->clearScreen();
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
            $this->clearScreen();
            $team = [ // Should this be its own class?
                'points' => 0,
                'players' => [],
                'calledTrump' => false,
                'trickPoints' => 0,
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
                        array_push($team['players'], new Human( // Humans for now, implement Bots later though. 1st player is a human, remaining 3 should be bots.
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
     * Handles setting the dealer for the trick. 
     * 
     * @return void
     */
    private function setDealer(): void 
    {
        // Set old dealer's isDealer attribute to false first.
        if ($this->dealer) $this->dealer->isDealer = false;
        $this->dealer = $this->getPlayerAtPosition(!$this->dealer ? [0, 0] : $this->dealer->nextPlayerPosition);
        $this->dealer->isDealer = true;
    }

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

    /**
     * Checks the players current hand to see if they can follow suit or not.
     * If suitToFollow is null then that means the user is playing the first card and setting the suit to follow.
     * 
     * @return bool
     */
    protected function canFollowSuit(Player $player, ?string $suitToFollow, string $trump): bool
    {
        if (!$suitToFollow) return true;
        foreach ($player->hand as $card) if ($card->suit == $suitToFollow || $suitToFollow == $trump && $card->type == 'Jack' && $card->leftBower == $trump) return true;

        return false;
    }
    #endregion
}

?>
