<?php

namespace Tests\Unit;

use App\Statistics\Percentiles;
use PHPUnit\Framework\TestCase;

class PercentilesTest extends TestCase
{
    public function test_median_of_odd_count(): void
    {
        $this->assertSame(5.0, Percentiles::median([9, 1, 5, 3, 7]));
    }

    public function test_median_of_even_count_averages_the_middle_pair(): void
    {
        $this->assertSame(4.0, Percentiles::median([1, 3, 5, 7]));
    }

    public function test_median_is_robust_against_a_single_outlier(): void
    {
        $this->assertSame(2000.0, Percentiles::median([1000, 2000, 3000, 500000]) - 500.0);
    }

    public function test_empty_list_has_no_median(): void
    {
        $this->assertNull(Percentiles::median([]));
    }
}
