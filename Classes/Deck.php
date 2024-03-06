<?php

require('Card.php');

class Deck
{
    public array $cards = [];
    private array $suits = [['diamond', 'heart'], ['spade', 'club'], ['heart', 'diamond'], ['club', 'spade']]; // right and left relationships [right, left]
    private array $types = [
        '9' => ['level' => 1, 'trumpLevel' => 7],
        '10' => ['level' => 2, 'trumpLevel' => 8],
        'Jack' => ['level' => 3, 'trumpLevel' => 13], // left bower will get 12.
        'Queen' => ['level' => 4, 'trumpLevel' => 9],
        'King' => ['level' => 5, 'trumpLevel' => 10],
        'Ace' => ['level' => 6, 'trumpLevel' => 11],
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
        foreach ($this->suits as $suitWithLeft) {
            foreach ($this->types as $type => $level) {
                array_push($this->cards, new Card($suitWithLeft, $type, $level['level'], $level['trumpLevel']));
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
