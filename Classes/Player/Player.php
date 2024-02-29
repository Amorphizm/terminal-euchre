<?php

abstract class Player 
{
    public string $name;
    public int $teamNum;
    public array $hand = [];
    public array $position = []; // team num, player num.
    public bool $isDealer = false;
    public bool $isSittingOut = false; // Set to true if their partner is going alone.
    public array $partnerPosition = [];
    public array $nextPlayerPosition = []; // Pointer to next player in a given iteration (dealing cards, tricks).

    public function __construct(string $name, int $teamNum, array $position)
    {
        $this->name = $name;
        $this->teamNum = $teamNum;
        $this->position = $position;
        $this->nextPlayerPosition = $this->getNextPlayerPosition($position);
        $this->partnerPosition = [$teamNum - 1, $this->position[1] ? 0 : 1];
    }

    /**
     * Allows the player to determine 
     * 
     * @return ?string $suit
     */
    abstract function selectTrump(bool $stickTheDealer): ?string;

    /**
     * Used to see if the player who determined trump would like to go alone or not.
     * 
     * @return bool
     */
    abstract function processAloneCheck(): bool;

    /**
     * Called if this player is the dealer and needs to pick up a card to replace with one in their hand.
     * @param Card $card
     * 
     * @return void
     */
    abstract function processOrderUp(Card $card): void;

    /**
     * See if this player wants to order up the card to the dealer to declare trump.
     * @param Card $card
     * @param string $dealerName
     * 
     * @return bool // does the player want the dealer to pick the card up?
     */
    abstract function orderUpCardCheck(Card $card, string $dealerName): bool;

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

        return [0, 0]; // Return P1 from team 1.
    }

    /**
     * Clears the terminal of text.
     * 
     * @return void
     */
    protected function clearScreen(): void
    {
        echo chr(27).chr(91).'H'.chr(27).chr(91).'J'; //^[H^[J
    }
}

?>
