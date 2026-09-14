<?php

namespace Tests\Unit;

use App\Game\ResultPhrase;
use Tests\TestCase;

class ResultPhraseTest extends TestCase
{
    public function test_phrases_follow_distance_bands(): void
    {
        $this->assertSame('Bijna op het perron.', ResultPhrase::for(312));
        $this->assertSame('Die zat heel dichtbij.', ResultPhrase::for(1500));
        $this->assertSame('Netjes geprikt.', ResultPhrase::for(8000));
        $this->assertSame('Niet verkeerd.', ResultPhrase::for(18700));
        $this->assertSame('Daar zat nog wat spoor tussen.', ResultPhrase::for(40000));
        $this->assertSame('Oeps, verkeerde regio.', ResultPhrase::for(120000));
    }

    public function test_distance_sentence_uses_meters_below_a_kilometer(): void
    {
        $this->assertSame('op het perron', ResultPhrase::distanceSentence(0));
        $this->assertSame('312 meter ernaast', ResultPhrase::distanceSentence(312));
        $this->assertSame('12,4 km ernaast', ResultPhrase::distanceSentence(12400));
    }

    public function test_emoji_buckets(): void
    {
        $this->assertSame('🟢', ResultPhrase::emojiForScore(997));
        $this->assertSame('🟡', ResultPhrase::emojiForScore(812));
        $this->assertSame('🟠', ResultPhrase::emojiForScore(641));
        $this->assertSame('🔴', ResultPhrase::emojiForScore(120));
    }
}
