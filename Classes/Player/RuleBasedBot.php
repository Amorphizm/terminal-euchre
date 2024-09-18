<?php

require_once(__DIR__ . '/Player.php');

class RuleBasedBot extends Player
{
    private array $suitCards = [];
    private array $trumpCards = [];
    private array $trashCards = [];
    private ?Card $partnerCard = null;

    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    /**
     * Determines the card to play based on the current game state.
     *
     * This function considers the cards that have been played, the suit to follow, 
     * whether the player can follow suit, and whether the player is going alone.
     * It returns the card that the player has chosen to play.
     *
     * @param ?string $suitToFollow The suit that the player must follow if possible.
     * @param bool $canFollowSuit Whether the player can follow suit.
     * @param string $trump The trump suit.
     * @param array $playedCards The cards that have been played so far.
     * @param bool $partnerCalledTrump Whether the player's partner called trump.
     * @param bool $goingAlone Whether the player is going alone.
     * @return Card The card that the player has chosen to play.
     */
    public function playCard(?string $suitToFollow, bool $canFollowSuit, string $trump, array $playedCards = [], bool $partnerCalledTrump = false, bool $goingAlone = false): Card
    {
        $cardToPlay = null;

        // Do we have only one card left? If so then just return it here.
        if (count($this->hand) == 1) $cardToPlay = $this->hand[0];

        if (!$cardToPlay) {
            // Find our partner's card.
            if (count($playedCards) >= 2 && !$goingAlone) $this->partnerCard = count($playedCards) == 2 ? $playedCards[0] : $playedCards[1];

            // Filter hand for certain card types.
            $this->trumpCards = array_values(array_filter($this->hand, fn($card) => $card->getSuit($trump) === $trump));
            $this->suitCards = array_values(array_filter($this->hand, fn($card) => $card->getSuit($trump) === $suitToFollow));
            $this->trashCards = array_values(array_filter($this->hand, fn($card) => $card->getSuit($trump) !== $trump && $card->suit !== $suitToFollow));

            $cardToPlay = (!$playedCards) ? $this->determineLeadCard($trump, $partnerCalledTrump) : $this->determineNonLeadCard($suitToFollow, $canFollowSuit, $trump, $playedCards);
            $this->removeCardFromHand($cardToPlay);
        }
        
        echo $this->name . " played the $cardToPlay->name.\n";
        sleep(4);

        return $cardToPlay;
    } 

    /**
     * Selects the trump suit based on the cards in the player's hand.
     *
     * @param bool $stickTheDealer whether the player is the dealer
     * @param string $rejectedSuit the suit that cannot be selected as trump
     * @return ?string the selected trump suit or null if no suit is selected
     */
    public function selectTrump(bool $stickTheDealer, string $rejectedSuit): ?string
    {
        // Figure out how much of each suit we have in our hand first. Ignore the flipped card's suit since we can't use that as trump.
        $suits = array_diff_key(['diamond' => 0, 'heart' => 0, 'spade' => 0, 'club' => 0], [$rejectedSuit => true]);

        foreach ($this->hand as $card) {
            if (!array_key_exists($card->suit, $suits)) continue;

            if ($card->type == 'Jack') { // Left and right bower values.
                $suits[$card->suit] += ($card->level + 5);
                $suits[$card->leftBower] += ($card->level + 4);
                continue;
            }

            $suits[$card->suit] += $card->level;
        }

        $highestSuit = array_search(max($suits), $suits);
        $stuck = ($stickTheDealer && $this->isDealer);

        if (!$stuck && $suits[$highestSuit] < 10) {
            echo "$this->name passes.\n";
            return null;
        } else {
            echo "$this->name named $highestSuit" . "s as trump!\n";
            return $highestSuit;
        }
    }

    public function processAloneCheck(): bool
    {
        // Do we have 5 trump in hand?

        return false; // Test value.
    }

    /**
     * Called if this player is the dealer and needs to pick up a card to replace with one in their hand.
     * @param Card $card The card that has been flipped.
     * @return void
     */
    public function processOrderUp(Card $card): void
    {
        // Find the lowest card in our hand and get rid of it.
        $cardToDiscard = $this->findCardByValue($this->hand, $card->suit)['card'];
        $this->removeCardFromHand($cardToDiscard);

        array_push($this->hand, $card);
        if (!$this->isDealer) echo $this->name . " picked up the $card->name.\n";
        sleep(2);
    }

    /**
     * Check if the player wants to order up the card to the dealer to declare trump.
     *
     * @param Card $card The card that has been flipped.
     * @param string $dealerName The name of the dealer.
     * @return bool Returns true if the player wants to order up the card, false otherwise.
     */
    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        $suitCount = 0;
        $offSuitAceCount = 0;
        $bowerInHand = false;

        foreach ($this->hand as $cardInHand) {
            if ($cardInHand->suit == $card->suit) {
                $suitCount += 1;
                if ($card->getValue(null, $card->suit) >= 12) {
                    $bowerInHand = true;
                }
            } else if ($card->type == 'Ace') {
                $offSuitAceCount += 1;
            }
        }

        // Check if our hand meets the conditions to order up the card.
            // 3 suit matches on flipped card with one being the bower.
            // 3 suit matches on the flipped card with one being an off suit ace.
            // 2 suit matches on the flipped card with 2 off suit aces.
        if (
            ($suitCount >= ($this->isDealer ? 2 : 3) && ($offSuitAceCount >= 1 || $bowerInHand)) ||
            ($suitCount >= ($this->isDealer ? 1 : 2) && $offSuitAceCount >= 2)
        ) {
            $message = $this->isDealer ? " is picking " : " has ordered $dealerName to pick ";
            echo $this->name . $message . "up the $card->name!\n";
            return true;
        }
        
        echo $this->name . " passes on the flipped $card->name.\n";
        sleep(2);
        return false;
    }

    /**
     * Determine the lead card based on the given trump suit and partner's call.
     *
     * This function considers various factors such as the number of trump cards,
     * the presence of off-hand aces, and the partner's call to determine the lead card.
     *
     * @param string $trump The trump suit.
     * @param bool $partnerCalledTrump Whether the partner called trump.
     * @return Card The determined lead card.
     */
    private function determineLeadCard(string $trump, bool $partnerCalledTrump): Card 
    {
        $selectedCard = $offHandAce = $right = $left = null;

        // Figure out how much of each suit we have in our hand first.
        $suitCounts = [];
        foreach ($this->hand as $card) {
            if (!array_key_exists($card->suit, $suitCounts)) {
                $suitCounts[$card->suit] = 1;
            } else {
                $suitCounts[$card->suit] += 1;
            }
        }

        // Queue up an off hand ace, right, and left lead.
        foreach ($this->hand as $card) {
            if ($card->getValue(null, $trump) == 12) $left = $card;
            if ($card->getValue(null, $trump) == 13) $right = $card;
            if ($card->type == 'Ace' && $card->suit !== $trump && ($suitCounts[$card->suit] - 1) <= 2) $offHandAce = $card;
        }

        // Find the lowest trump card we have.
        $lowestTumpCard = $this->findCardByValue($this->trumpCards, $trump)['card'];

        // Right or left or lowest trump card.
        $selectedCard = isset($right) ? $right : (isset($left) ? $left : $lowestTumpCard);
        if ($selectedCard) return $selectedCard;

        // No trump cards. Off hand ace?
        if ($offHandAce) return $offHandAce;

        $lookForHighestValue = !($partnerCalledTrump && count($this->hand) >= 4);
        return $this->findCardByValue($this->hand, $trump, lookForHighestValue: $lookForHighestValue)['card'];
    }

    /**
     * Determines the non-lead card to play based on the current game state.
     *
     * @param string $suitToFollow The suit that needs to be followed.
     * @param bool $canFollowSuit Boolean indicating if the player can follow suit.
     * @param string $trump The trump suit in the game.
     * @param array $playedCards Array of cards that have been played in the current trick.
     * @return Card The selected card to play.
     */
    private function determineNonLeadCard(string $suitToFollow, bool $canFollowSuit, string $trump, array $playedCards): Card
    {
        $selectedCard = null;

        // Get the best played card thus far.
        $highestCardPlayedValue = $this->findCardByValue($playedCards, $trump, $suitToFollow, lookForHighestValue: true)['value'];

        // Is the best played card our partner's card?
        $partnerHasTrickPoint = isset($this->partnerCard) && $highestCardPlayedValue == $this->partnerCard->getValue($suitToFollow, $trump) ? true : false;

        if (!$canFollowSuit) {
            // If our partner does not have the trick point then lets find the best card we can play. We can't follow suit so we need to play a trump card.
            if (!$partnerHasTrickPoint && count($this->trumpCards)) {
                // Find the lowest possible trump card we have that beats the highest card played. $selectedCard will remain null if we can't find one.
                $selectedCard = $this->findCardByValue($this->trumpCards, $trump, $suitToFollow, false, $highestCardPlayedValue)['card'];
            }   
            
            // No winning trump cards OR partner has trick point OR we can't beat the best card played with any of our trump cards, lets play the lowest trash OR trump card.
            if (!$selectedCard) $selectedCard = $this->findCardByValue(array_merge($this->trashCards, $this->trumpCards), $trump)['card'];
        } else if (count($this->suitCards) == 1) { // Only one suit card so play it.
            $selectedCard = $this->suitCards[0];
        } else if ($partnerHasTrickPoint) { // Partner is winning so play the lowest suit card we have.
            $selectedCard = $this->findCardByValue($this->suitCards, $trump, $suitToFollow)['card'];
        } else if ($winningCard = $this->findCardByValue($this->suitCards, $trump, $suitToFollow, false, $highestCardPlayedValue)['card']) {
            $selectedCard = $winningCard;
        } else { // Throw away our worst suit card.
            $selectedCard = $this->findCardByValue($this->suitCards, $trump. $suitToFollow)['card'];  
        }

        return $selectedCard;
    }

    /**
     * Finds a card in the given array that meets the given criteria
     *
     * @param array $cards The array of cards to search through
     * @param string $trump The trump suit
     * @param ?string $suitToFollow The suit that needs to be followed if possible
     * @param bool $lookForHighestValue Whether to return the card with the highest or lowest rank
     * @param ?int $minValue The minimum value of the card to return. If null, no minimum is applied
     * @return array An array with a 'card' key pointing to the selected card and a 'value' key pointing to the value of the card
     */
    private function findCardByValue(array $cards, string $trump, ?string $suitToFollow = null, bool $lookForHighestValue = false, ?int $minValue = null): array
    {
        $targetCard = null;
        foreach ($cards as $card) {
            $cardValue = $card->getValue($suitToFollow, $trump);

            // If we passed in a min value and the card's value is less than the min value, skip it.
            if ($minValue !== null && $cardValue <= $minValue) continue;

            if (!$targetCard || $this->compareCardRank($card, $targetCard, $trump, $suitToFollow, $lookForHighestValue)) {
                $targetCard = $card;
            }
        }

        // NOTE - $targetCard will be null if $minValue is passed in and no cards meet the min value.
        return ['card' => $targetCard, 'value' => $targetCard ? $targetCard->getValue($suitToFollow, $trump) : 0];
    }

    /**
     * Compares the rank of two cards to see which is better.
     *
     * @param Card $a The first card
     * @param Card $b The second card
     * @param string $trump The trump suit
     * @param ?string $suitToFollow The suit that needs to be followed if possible
     * @param bool $lookForHighestValue Whether to return the card with the highest or lowest rank
     * @return bool Returns true if $a is better than $b, false otherwise
     */
    private function compareCardRank(Card $a, Card $b, string $trump, ?string $suitToFollow, bool $lookForHighestValue): bool 
    {
        $rankA = $a->getValue($suitToFollow, $trump);
        $rankB = $b->getValue($suitToFollow, $trump);

        return $lookForHighestValue ? $rankA > $rankB : $rankA < $rankB;
    }

    /**
     * Removes a card from the player's hand.
     *
     * @param Card $card The card to remove
     * @return void
     */
    private function removeCardFromHand(Card $card)
    {
        // Loop through the player's hand and unset the card if it matches the type and suit of the card to remove
        foreach ($this->hand as $key => $cardInHand) {
            if ($cardInHand->type === $card->type && $cardInHand->suit === $card->suit) {
                unset($this->hand[$key]);
                break;
            }
        }

        // Rebase the keys after unsetting the card
        $this->hand = array_values($this->hand);
    }

}

?>
