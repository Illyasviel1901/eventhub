<?php

declare(strict_types=1);

function weatherHttpJson(string $url): ?array
{
    $context = stream_context_create([
        'http' => ['timeout' => 3, 'user_agent' => 'EventHub/1.0'],
        'https' => ['timeout' => 3, 'user_agent' => 'EventHub/1.0'],
    ]);
    $response = @file_get_contents($url, false, $context);
    if ($response === false) {
        return null;
    }

    $data = json_decode($response, true);
    return is_array($data) ? $data : null;
}

function venueCityFromAddress(string $address): string
{
    $parts = array_values(array_filter(array_map('trim', explode(',', $address))));
    $candidate = $parts === [] ? '' : (string) end($parts);
    return preg_match('/[[:alpha:]]/u', $candidate) ? $candidate : 'București';
}

function weatherCodeLabel(int $code): string
{
    return match (true) {
        $code === 0 => 'Cer senin',
        in_array($code, [1, 2], true) => 'Parțial noros',
        $code === 3 => 'Înnorat',
        in_array($code, [45, 48], true) => 'Ceață',
        in_array($code, [51, 53, 55, 56, 57], true) => 'Burniță',
        in_array($code, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'Ploaie',
        in_array($code, [71, 73, 75, 77, 85, 86], true) => 'Ninsoare',
        in_array($code, [95, 96, 99], true) => 'Furtună',
        default => 'Condiții variabile',
    };
}

function dateIsInWeatherWindow(string $date): bool
{
    $selected = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    if ($selected === false || $selected->format('Y-m-d') !== $date) {
        return false;
    }

    $today = new DateTimeImmutable('today');
    return $selected >= $today && $selected <= $today->modify('+7 days');
}

/** @return array{latitude: float, longitude: float, city: string}|null */
function weatherCoordinatesForAddress(string $address): ?array
{
    $city = venueCityFromAddress($address);
    $cacheKey = 'weather_coordinates_' . hash('sha256', strtolower($city));
    $cached = $_SESSION[$cacheKey] ?? null;
    if (is_array($cached) && isset($cached['expires_at'], $cached['data']) && (int) $cached['expires_at'] > time()) {
        return $cached['data'];
    }

    $url = 'https://geocoding-api.open-meteo.com/v1/search?' . http_build_query([
        'name' => $city,
        'count' => 1,
        'language' => 'ro',
        'format' => 'json',
    ]);
    $response = weatherHttpJson($url);
    $location = $response['results'][0] ?? null;
    if (!is_array($location) || !isset($location['latitude'], $location['longitude'])) {
        return null;
    }

    $data = [
        'latitude' => (float) $location['latitude'],
        'longitude' => (float) $location['longitude'],
        'city' => (string) ($location['name'] ?? $city),
    ];
    $_SESSION[$cacheKey] = ['expires_at' => time() + 86400, 'data' => $data];

    return $data;
}

/** @return array{city: string, date: string, temperature_max: float, temperature_min: float, precipitation_probability: int, wind_speed: float, description: string}|null */
function getVenueWeatherForDate(string $address, string $date): ?array
{
    if (!dateIsInWeatherWindow($date)) {
        return null;
    }

    $city = venueCityFromAddress($address);
    $cacheKey = 'weather_forecast_' . hash('sha256', strtolower($city) . '|' . $date);
    $cached = $_SESSION[$cacheKey] ?? null;
    if (is_array($cached) && isset($cached['expires_at'], $cached['data']) && (int) $cached['expires_at'] > time()) {
        return $cached['data'];
    }

    $coordinates = weatherCoordinatesForAddress($address);
    if ($coordinates === null) {
        return null;
    }

    $url = 'https://api.open-meteo.com/v1/forecast?' . http_build_query([
        'latitude' => $coordinates['latitude'],
        'longitude' => $coordinates['longitude'],
        'daily' => 'weather_code,temperature_2m_max,temperature_2m_min,precipitation_probability_max,wind_speed_10m_max',
        'timezone' => 'Europe/Bucharest',
        'start_date' => $date,
        'end_date' => $date,
    ]);
    $forecast = weatherHttpJson($url);
    $daily = $forecast['daily'] ?? null;
    if (!is_array($daily)
        || !isset($daily['time'][0], $daily['weather_code'][0], $daily['temperature_2m_max'][0], $daily['temperature_2m_min'][0], $daily['wind_speed_10m_max'][0])) {
        return null;
    }

    $data = [
        'city' => $coordinates['city'],
        'date' => (string) $daily['time'][0],
        'temperature_max' => (float) $daily['temperature_2m_max'][0],
        'temperature_min' => (float) $daily['temperature_2m_min'][0],
        'precipitation_probability' => (int) ($daily['precipitation_probability_max'][0] ?? 0),
        'wind_speed' => (float) $daily['wind_speed_10m_max'][0],
        'description' => weatherCodeLabel((int) $daily['weather_code'][0]),
    ];
    $_SESSION[$cacheKey] = ['expires_at' => time() + 1800, 'data' => $data];

    return $data;
}
