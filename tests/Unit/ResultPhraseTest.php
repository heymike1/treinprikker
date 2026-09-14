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
        $this->assertSame('precies goed', ResultPhrase::distanceSentence(0));
        $this->assertSame('312 meter ernaast', ResultPhrase::distanceSentence(312));
        $this->assertSame('12,4 km ernaast', ResultPhrase::distanceSentence(12400));
    }

    public function test_emoji_buckets(): void
    {
        $this->assertSame('🟢', ResultPhrase::emojiForDistance(0));
        $this->assertSame('🟢', ResultPhrase::emojiForDistance(499));
        $this->assertSame('🟡', ResultPhrase::emojiForDistance(3200));
        $this->assertSame('🟠', ResultPhrase::emojiForDistance(12400));
        $this->assertSame('🔴', ResultPhrase::emojiForDistance(47000));
    }

    public function test_badge_follows_distance_not_score(): void
    {
        // 47 km scores ~526 points but is not "in de buurt".
        $this->assertSame('Ver weg', ResultPhrase::labelForDistance(47000));
        $this->assertSame('ver', ResultPhrase::bucketKeyForDistance(47000));
        $this->assertSame('In de buurt', ResultPhrase::labelForDistance(12400));
        $this->assertSame('Dichtbij', ResultPhrase::labelForDistance(3200));
        $this->assertSame('Raak', ResultPhrase::labelForDistance(0));
        $this->assertSame('raak', ResultPhrase::bucketKeyForDistance(499));
    }
}
