<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\GeolocationService;
use PHPUnit\Framework\TestCase;

class GeolocationServiceTest extends TestCase
{
    private GeolocationService $geolocationService;

    protected function setUp(): void
    {
        $this->geolocationService = new GeolocationService();
    }

    public function testCalculateDistanceWithKnownValues(): void
    {
        // Coordinates for Warsaw, Poland
        $lat1 = 52.2297;
        $lon1 = 21.0122;

        // Coordinates for Berlin, Germany
        $lat2 = 52.5200;
        $lon2 = 13.4050;

        // Expected distance is approximately 517km, so around 517000m
        $expectedDistance = 517430; // More precise value
        $calculatedDistance = $this->geolocationService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        $this->assertEqualsWithDelta($expectedDistance, $calculatedDistance, 1000); // Allow a tolerance of 1km
    }

    public function testCalculateDistanceWithSameCoordinates(): void
    {
        $lat = 52.2297;
        $lon = 21.0122;

        $this->assertEquals(0.0, $this->geolocationService->calculateDistance($lat, $lon, $lat, $lon));
    }

    public function testIsWithinDistanceReturnsTrue(): void
    {
        $lat1 = 52.2297;
        $lon1 = 21.0122;
        $lat2 = 52.2307; // Approx 111 meters away
        $lon2 = 21.0122;

        $this->assertTrue($this->geolocationService->isWithinDistance($lat1, $lon1, $lat2, $lon2, 200));
    }

    public function testIsWithinDistanceReturnsFalse(): void
    {
        $lat1 = 52.2297;
        $lon1 = 21.0122;
        $lat2 = 52.2307; // Approx 111 meters away
        $lon2 = 21.0122;

        $this->assertFalse($this->geolocationService->isWithinDistance($lat1, $lon1, $lat2, $lon2, 100));
    }

    public function testIsWithinDistanceAtBoundary(): void
    {
        $lat1 = 52.2297;
        $lon1 = 21.0122;
        $lat2 = 52.2307; // Approx 111 meters away
        $lon2 = 21.0122;
        
        $distance = $this->geolocationService->calculateDistance($lat1, $lon1, $lat2, $lon2);

        $this->assertTrue($this->geolocationService->isWithinDistance($lat1, $lon1, $lat2, $lon2, (int)ceil($distance)));
        $this->assertFalse($this->geolocationService->isWithinDistance($lat1, $lon1, $lat2, $lon2, (int)floor($distance -1)));

    }
}
