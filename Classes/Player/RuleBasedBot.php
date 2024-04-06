<?php

require_once(__DIR__ . '/Player.php');

class RuleBasedBot extends Player
{
    function __construct(string $name, int $teamNum, array $position) 
    {
      parent::__construct($name, $teamNum, $position);
    }

    public function playCard(?string $suitToFollow, bool $canFollowSuit, string $trump, array $playedCards = []): Card
    {
        // Do we have only one card left? If so then just return it here.

        // What do we need to make decisions?
        // $suitCards Filter hand for cards that can follow suit.
        // $trumpCards Filter hand for cards that are trump.
        // $trashCards Remaining cards in hand belong to their own group.
        // $partnerCard Find from $playedCards, can be null if we are playing before our partner.

        // Can't follow suit
        // Do we have trump?
            // YES - Has a trump card been played that we can't beat?
                // YES - throw away lowest trash card.
                // NO - Is our partner currently winning the trick point?
                    // YES - throw away the lowest trash card.
                    // NO  - play a trump card that wins the current trick point.
            // NO - throw away the lowest trash card.

        // Can follow suit
        // Do we only have one suit card we can play?
            // YES - play it since we have to follow suit.
            // NO - Do we have a suit card that can win the current trick point at the moment?
                // YES - Is our partner currently winning the trick point?
                    // YES - throw away lowest suit card.
                    // NO - play the highest rank suit card we have.
                // NO - throw away lowest suit card.
    } 

    public function selectTrump(bool $stickTheDealer): ?string
    {

    }

    public function processAloneCheck(): bool
    {
        
    }

    public function processOrderUp(Card $card): void
    {
        
    }

    public function orderUpCardCheck(Card $card, string $dealerName): bool
    {
        
    }
}

?>
