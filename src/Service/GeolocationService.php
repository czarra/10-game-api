<?php

declare(strict_types=1);

namespace App\Service;

class GeolocationService
{
    /**
     * Calculates the distance between two geographical points using the Haversine formula.
     *
     * @param float $lat1 Latitude of point 1.
     * @param float $lon1 Longitude of point 1.
     * @param float $lat2 Latitude of point 2.
     * @param float $lon2 Longitude of point 2.
     * @return float Distance in meters.
     */
    public function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000; // meters

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Checks if the distance between two points is within a specified allowed distance.
     *
     * @param float $lat1 Latitude of point 1.
     * @param float $lon1 Longitude of point 1.
     * @param float $lat2 Latitude of point 2.
     * @param float $lon2 Longitude of point 2.
     * @param int $allowedDistance The maximum allowed distance in meters.
     * @return bool True if within allowed distance, false otherwise.
     */
    public function isWithinDistance(float $lat1, float $lon1, float $lat2, float $lon2, int $allowedDistance): bool
    {
        return $this->calculateDistance($lat1, $lon1, $lat2, $lon2) <= $allowedDistance;
    }
}
