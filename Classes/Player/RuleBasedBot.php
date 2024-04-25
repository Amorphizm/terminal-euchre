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

    public function playCard(?string $suitToFollow, bool $canFollowSuit, string $trump, array $playedCards = []): Card
    {
        $cardToPlay = null;

        // Do we have only one card left? If so then just return it here.
        if (count($this->hand) == 1) $cardToPlay = $this->hand[0];

        if (!$cardToPlay) {
            // Find our partner's card.
            if (count($playedCards) >= 2) $this->partnerCard = count($playedCards) == 2 ? $playedCards[0] : $playedCards[1];

            // Filter hand for certain card types.
            $handCopy = $this->hand;
            $this->trumpCards = array_values(array_filter($handCopy, fn($card) => $card->getSuit($trump) === $trump));
            $this->suitCards = array_values(array_filter($handCopy, fn($card) => $card->getSuit($trump) === $suitToFollow));
            $this->trashCards = array_values(array_filter($handCopy, fn($card) => $card->getSuit($trump) !== $trump && $card->suit !== $suitToFollow));

            $cardToPlay = (!$playedCards) ? $this->determineLeadCard($trump) : $this->determineNonLeadCard($suitToFollow, $canFollowSuit, $trump, $playedCards);

            // Remove the card we want to play from the hand and recontruct it.
            foreach ($this->hand as $key => $card) {
                if ($card->type === $cardToPlay->type && $card->suit === $cardToPlay->suit) {
                    unset($this->hand[$key]);
                    $this->hand = array_values($this->hand);
                    break;
                }
            }
        }
        
        echo $this->name . " played the $cardToPlay->name.\n";
        // echo json_encode($this->hand) . "\n";
        sleep(4);

        return $cardToPlay;
    } 

    public function selectTrump(bool $stickTheDealer): ?string
    {
        // Return the suit that we have most of?

        return $this->hand[0]->suit; // Test value;
    }

    public function processAloneCheck(): bool
    {
        // Do we have 5 trump in hand?

        return false; // Test value.
    }

    public function processOrderUp(Card $card): void
    {
        // Replace lowest card in hand.

        $this->hand[0] = $card; // Test value.
    }

    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        // Do we have 3 or more of the flipped card suit in hand?
        
        echo $this->name . " has ordered up the $card->name!\n";
        return true; // test value.
    }

    private function determineLeadCard(string $trump): Card 
    {
        // Off hand ace lead.
        foreach ($this->trashCards as $card) if ($card->type == 'Ace' && $card->suit != $trump) return $card;

        return $this->hand[0]; // Test value.
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
                echo "Can't follow suit, partner doesn't have it, and has trump cards. Find card that wins\n";
                $selectedCard = $this->findCardByValueToBeat($this->trumpCards, $highestCardPlayedValue, $trump, $suitToFollow);
            }   
            
            // No trump cards OR partner has trick point OR we can't beat the best card played with any of our trump cards, lets play the lowest trash card.
            if (!$selectedCard) {
                echo "Can't follow suit and its all bad, play worst card\n";
                $selectedCard = $this->findCardByValue($this->trashCards, $trump)['card'];
            }
        } else if (count($this->suitCards) == 1) { // Only one suit card so play it.
            echo "Only one suit card, play it\n";
            $selectedCard = $this->suitCards[0];
        } else if ($partnerHasTrickPoint) { // Partner is winning so play the lowest suit card we have.
            echo "Can follow suit and partner has trick point, play worst card\n";
            $selectedCard = $this->findCardByValue($this->suitCards, $trump, $suitToFollow)['card'];
        } else if ($winningCard = $this->findCardByValueToBeat($this->suitCards, $highestCardPlayedValue, $trump, $suitToFollow)) { // Play winning suit card to play.
            echo "Can follow suit and partner does not have trick point. Play winning card\n";
            $selectedCard = $winningCard;
        } else { // Throw away our worst suit card.
            echo "Can follow suit and can't find a better card and partner does not have trick point. Play trash card??\n";
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
     * @param bool $ignoreSuitPlayed Whether to ignore the suit that has been played
     *
     * @return Card The best card or null if there are no cards from the specified suit
     */
    private function findCardByValue(array $cards, string $trump, ?string $suitToFollow = null, bool $lookForHighestValue = false): array 
    {
        $bestCard = null;
        foreach ($cards as $card) {
            if ($bestCard === null || $this->compareCardRank($card, $bestCard, $trump, $suitToFollow, $lookForHighestValue)) {
                $bestCard = $card;
            }
        }

        return ['card' => $bestCard, 'value' => $bestCard->getValue($suitToFollow, $trump)];
    }

    /**
     * Finds a card that beats the specified value.
     *
     * @param Card[] $cards List of cards
     * @param int $valueToBeat The value that the card should beat
     * @param ?string $suitToFollow The suit that needs to be followed if possible
     * @param string $trump The trump suit
     *
     * @return Card|null The best card or null if there are no cards that beat the specified value
     */
    private function findCardByValueToBeat(array $cards, int $valueToBeat, string $trump, ?string $suitToFollow = null, bool $lookForHighestValue = false): ?Card
    {
        $bestCard = null;
        foreach ($cards as $card) {
            if ($card->getValue($suitToFollow, $trump) > $valueToBeat && (!$bestCard || $this->compareCardRank($bestCard, $card, $trump, $suitToFollow, $lookForHighestValue))) {
                $bestCard = $card;
            }
        }

        return $bestCard;
    }

    /**
     * Compares the rank of two cards to see which is better.
     *
     * @param Card $a The first card
     * @param Card $b The second card
     * @param string $trump The trump suit
     * @param ?string $suitToFollow The suit that needs to be followed if possible
     * @param bool $lookForHighestValue Whether to return the card with the highest or lowest rank
     * @param bool $ignoreSuitPlayed Whether to ignore the suit that has been played
     *
     * @return bool Returns true if $a is better than $b, false otherwise
     */
    private function compareCardRank(Card $a, Card $b, string $trump, ?string $suitToFollow, bool $lookForHighestValue): bool 
    {
        $rankA = $a->getValue($suitToFollow, $trump);
        $rankB = $b->getValue($suitToFollow, $trump);

        return $lookForHighestValue ? $rankA > $rankB : $rankA < $rankB;
    }
}

?>
