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

    public function selectTrump(bool $stickTheDealer): ?string
    {
        // Figure out how much of each suit we have in our hand first.
        $suits = ['diamond' => 0, 'heart' => 0, 'spade' => 0, 'club' => 0];
        foreach ($this->hand as $card) {
            if ($card->type == 'Jack') { // Left and right bower values.
                $suits[$card->suit] += ($card->level + 5);
                $suits[$card->leftBower] += ($card->level + 4);
                continue;
            }

            $suits[$card->suit] += $card->level;
        }

        $highestSuit = array_search(max($suits), $suits);
        $stuck = ($stickTheDealer && $this->isDealer);
        return !$stuck && $suits[$highestSuit] < 10 ? null : $highestSuit;
    }

    public function processAloneCheck(): bool
    {
        // Do we have 5 trump in hand?

        return false; // Test value.
    }

    public function processOrderUp(Card $card): void
    {
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
                $selectedCard = $this->findCardByValueToBeat($this->trumpCards, $highestCardPlayedValue, $trump, $suitToFollow);
            }   
            
            // No winning trump cards OR partner has trick point OR we can't beat the best card played with any of our trump cards, lets play the lowest trash OR trump card.
            if (!$selectedCard) $selectedCard = $this->findCardByValue(array_merge($this->trashCards, $this->trumpCards), $trump)['card'];
        } else if (count($this->suitCards) == 1) { // Only one suit card so play it.
            $selectedCard = $this->suitCards[0];
        } else if ($partnerHasTrickPoint) { // Partner is winning so play the lowest suit card we have.
            $selectedCard = $this->findCardByValue($this->suitCards, $trump, $suitToFollow)['card'];
        } else if ($winningCard = $this->findCardByValueToBeat($this->suitCards, $highestCardPlayedValue, $trump, $suitToFollow)) { // Play winning suit card to play.
            $selectedCard = $winningCard;
        } else { // Throw away our worst suit card.
            $selectedCard = $this->findCardByValue($this->suitCards, $trump. $suitToFollow)['card'];  
        }

        return $selectedCard;
    }

    /**
     * Gets the highest or lowest value card from a list of cards.
     *
     * @param array $cards List of cards
     * @param string $trump The trump suit
     * @param string $suitToFollow The suit that needs to be followed if possible
     * @param bool $lookForHighestValue Whether to return the card with the highest or lowest rank
     * @return array The target card or null if there are no cards from the specified suit
     */
    private function findCardByValue(array $cards, string $trump, ?string $suitToFollow = null, bool $lookForHighestValue = false): array 
    {
        $targetCard = null;
        foreach ($cards as $card) {
            if (!$targetCard || $this->compareCardRank($card, $targetCard, $trump, $suitToFollow, $lookForHighestValue)) {
                $targetCard = $card;
            }
        }

        return ['card' => $targetCard, 'value' => $targetCard ? $targetCard->getValue($suitToFollow, $trump) : 0];
    }

    /**
     * Finds a card that beats the specified value.
     *
     * @param Card[] $cards List of cards
     * @param int $valueToBeat The value that the card should beat
     * @param string $trump The trump suit
     * @param ?string $suitToFollow The suit that needs to be followed if possible 
     * @param bool $lookForHighestValue Whether to return the card with the highest or lowest rank
     * @return Card|null The target card or null if there are no cards that beat the specified value
     */
    private function findCardByValueToBeat(array $cards, int $valueToBeat, string $trump, ?string $suitToFollow = null, bool $lookForHighestValue = false): ?Card
    {
        $targetCard = null;
        foreach ($cards as $card) {
            if ($card->getValue($suitToFollow, $trump) > $valueToBeat && (!$targetCard || $this->compareCardRank($targetCard, $card, $trump, $suitToFollow, $lookForHighestValue))) {
                $targetCard = $card;
            }
        }

        return $targetCard;
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

    private function removeCardFromHand(Card $card) {
        foreach ($this->hand as $key => $cardInHand) {
            if ($cardInHand->type === $card->type && $cardInHand->suit === $card->suit) {
                unset($this->hand[$key]);
                $this->hand = array_values($this->hand);
                break;
            }
        }
    }
}

?>
