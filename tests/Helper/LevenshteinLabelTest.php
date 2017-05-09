<?php

namespace Neo4j\Alexa\Tests\Helper;

use Neo4j\Alexa\Helper\LevenshteinLabel;
use PHPUnit\Framework\TestCase;

class LevenshteinLabelTest extends TestCase
{
    public function testClosestLabelIsReturned()
    {
        $input = 'users';
        $labels = ['Person', 'Movie', 'User'];

        $this->assertEquals('User', LevenshteinLabel::getNearest($input, $labels));
    }

    public function testExactMatchLabelIsReturned()
    {
        $input = 'user';
        $labels = ['Movie', 'Users', 'User'];

        $this->assertEquals('User', LevenshteinLabel::getNearest($input, $labels));
    }

    public function testEmptyLabelsIsSupported()
    {
        $input = 'user';
        $labels = [];

        $this->assertEquals('', LevenshteinLabel::getNearest($input, $labels));
    }

    public function testPhraseMatchWithLabels()
    {
        $input = 'users neo';
        $labels = ['Movie', 'Org', 'User'];

        $this->assertEquals('User', LevenshteinLabel::getNearest($input, $labels));
    }
}