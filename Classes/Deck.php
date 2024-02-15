<?php

require('Card.php');

class Deck
{
    public array $cards = [];
    private array $suits = ['diamond', 'spade', 'heart', 'club'];
    private array $faces = [
        '9' => 1,
        '10' => 2,
        'J' => 3,
        'Q' => 4,
        'K' => 5,
        'A' => 6,
    ];

    public function __construct()
    {
        $this->initDeck();
        $this->shuffle();
    }

    /**
     * Initializes the deck of cards.
     * 
     * @return void
     */
    private function initDeck(): void
    {
        foreach ($this->suits as $suit) {
            foreach ($this->faces as $face => $level) {
                array_push($this->cards, new Card($suit, $face, $level));
            }
        }
    }

    /**
     * Suffles the deck of cards using the Knuth shuffle algorithm.
     * 
     * @return void
     */
    public function shuffle(): void
    {
        $lastIndex = count($this->cards) - 1;
        while ($lastIndex > 0) {
            $randomIndex = rand(0, $lastIndex);
            $lastCard = $this->cards[$lastIndex];

            $this->cards[$lastIndex] = $this->cards[$randomIndex];
            $this->cards[$randomIndex] = $lastCard;
            $lastIndex--;
        }
    }
}

?>
