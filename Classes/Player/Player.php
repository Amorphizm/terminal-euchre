<?php

abstract class Player 
{
    public string $name;
    public int $teamNum;
    public array $hand = [];
    public array $position = []; // team num, player num.
    public array $nextPlayerPosition = []; // Pointer to next player in a given iteration (dealing cards, tricks).

    public function __construct(string $name, int $teamNum, array $position)
    {
        $this->name = $name;
        $this->teamNum = $teamNum;
        $this->position = $position;
        $this->nextPlayerPosition = $this->getNextPlayerPosition($position);
    }

    /**
     * See if this player wants to order up the card to the dealer to declare trump.
     * @param Card $card
     * @param string $dealerName
     * @param bool $isDealer
     * 
     * @return bool
     */
    abstract function orderUpCardCheck(Card $card, string $dealerName, bool $isDealer): bool;

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
}

?>
