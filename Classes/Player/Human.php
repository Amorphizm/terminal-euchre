<?php

require_once(__DIR__ . '/Player.php');

class Human extends Player
{
    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    public function playCard(?string $suitToFollow, bool $canFollowSuit, string $trump): Card
    {
        $this->displayHand();
        
        while (true) {
            if ($suitToFollow) echo "Suit to follow is $suitToFollow" . "s.\n";
            $input = readline("Enter the position of the card that you would like to play: ");

            if (is_numeric($input) && array_key_exists(((int) $input) - 1, $this->hand)) {
                $card =  $this->hand[((int) $input) - 1];
                if (!$suitToFollow || $canFollowSuit && ($card->suit == $suitToFollow || ($card->type == 'Jack' && $card->leftBower == $trump && $suitToFollow == $trump)) || !$canFollowSuit) {
                    echo "$this->name played the $card->name\n";
                    unset($this->hand[((int) $input) - 1]);
                    return $card;
                }
            }

            echo "Whoops! Couldn't find a card or you need to follow suit. Try again!\n";
        }
    }

    public function selectTrump(bool $stickTheDealer): ?string 
    {
        $this->displayHand();
        $message = "Enter the position of the card that has the suit you would like to be trump";

        $stickTheDealer = $this->isDealer && $stickTheDealer;
        $message .= $stickTheDealer ? ': ' : ' OR p to pass: ';

        while (true) {
            $input = readLine($message);

            // Is not the dealer OR is the dealer and its not stick the dealer and they want to pass the turn.
            if (!is_numeric($input) && !($this->isDealer && $stickTheDealer) && in_array(strtolower($input), ['p', 'pass'])) break;
            
            if (is_numeric($input) && array_key_exists(((int) $input) - 1, $this->hand)) {
                $suit = $this->hand[((int) $input) - 1]->suit;
                echo "$this->name named $suit" . "s as trump!\n";

                return $suit;
            }

            echo "Whoops! Couldn't find a card at that position. Try again!\n";
        }

        echo "$this->name passes.\n";
        return null;
    }

    public function processAloneCheck(): bool
    {
        $this->displayHand();

        while (true) {
            $input = strtolower(readline("$this->name, would you like to go alone for this trick? (y or n): "));

            if (in_array($input, ['y', 'yes', 'n', 'no'])) break;
            echo "Whoops! You entered something incorrect. Please try again!\n";
        }

        $this->clearScreen();
        return in_array($input, ['y', 'yes']);
    }

    public function processOrderUp(Card $card): void
    {
        if ($this->isSittingOut) return;

        $this->displayHand();
        while (true) {
            $input = readline("Enter the position of the card you would like to replace with the $card->name (eg. 1 - 5): ");

            if (is_numeric($input) && array_key_exists(((int) $input) - 1, $this->hand)) {
                $this->hand[((int) $input) - 1] = $card;
                break;
            }

            echo "Whoops! Couldn't find a card at that position. Try again!\n";
        }
    }

    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        $input = null;
        $validInput = false;
        $validInputs = ['y', 'yes', 'n', 'no'];
        $this->displayHand();

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

        echo "Your hand $this->name - \n$cards\n";
    }
}

?>
