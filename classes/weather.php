<?php
class Weather
{
    /**
     * @throws Exception
     */
    public function fetchWeatherData($city, $state, $countryCode)
    {
        $request = [
            'city' => $city,
            'state' => $state,
            'country_code' => $countryCode
        ];

        $cachedWeatherData = $this->getCachedWeatherData($request);

        if ($cachedWeatherData) {
            return $cachedWeatherData;
        }

        $parameters = $this->cleanParameters($request);
        $geolocation = $this->getGeolocation($parameters);

        if ($geolocation) {
            $coordinates = $this->parseGeolocationData($geolocation);
            $request['latitude'] = $coordinates['latitude'];
            $request['longitude'] = $coordinates['longitude'];

            $currentWeather = $this->getCurrentWeather($request);
            return $this->parseCurrentWeatherData($currentWeather);
        }

        return null;
    }

    private function cleanParameters($parameters): string
    {
        $filteredParameters = array_filter($parameters, function ($value) {
            return !empty(trim($value));
        });
        return implode(',', $filteredParameters);
    }

    private function getCachedWeatherData($request)
    {
        $cacheFile = 'cache/weather_cache.json';

        if (!file_exists($cacheFile)) {
            return null;
        }

        $cachedData = json_decode(file_get_contents($cacheFile), true);

        if ($cachedData['city/state'] === $request['city'] . ',' . $request['state'] && (time() - $cachedData['timestamp']) < 600) {
            return $cachedData['data'];
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getCurrentWeather($request)
    {
        $urlParameters = '?lat=' . $request['latitude'] . '&lon=' . $request['longitude'] . '&appid=' . API_KEY . '&units=imperial';
        $response = $this->performCurlRequest(FETCH_WEATHER_URL . $urlParameters);
        $data = json_decode($response, true);
        $this->cacheWeatherData($request, $data);
        return $data;
    }

    /**
     * @throws Exception
     */
    public function getGeolocation($location)
    {
        $urlParameters = '?q=' . urlencode($location) . '&appid=' . API_KEY;
        $response = $this->performCurlRequest(GEOLOCATION_URL . $urlParameters);
        return json_decode($response, true);
    }

    private function parseGeolocationData($data): ?array
    {
        foreach ($data as $location) {
            return [
                'latitude' => $location['lat'],
                'longitude' => $location['lon']
            ];
        }
        return null;
    }

    private function performCurlRequest($url): bool|string
    {
        $curlHandle = curl_init();
        curl_setopt($curlHandle, CURLOPT_URL, $url);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT, 10); // Increased timeout for reliability
        curl_setopt($curlHandle, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curlHandle, CURLOPT_SSL_VERIFYPEER, true); // SSL verification should be enabled in production

        $response = curl_exec($curlHandle);

        if ($response === false) {
            $error = curl_error($curlHandle);
            curl_close($curlHandle);
            throw new Exception('Curl error: ' . $error);
        }

        curl_close($curlHandle);
        return $response;
    }

    private function cacheWeatherData($request, $data): void
    {
        $cacheFile = 'cache/weather_cache.json';
        $cacheData = [
            'city/state' => $request['city'] . ',' . $request['state'],
            'data' => $data,
            'timestamp' => time()
        ];
        file_put_contents($cacheFile, json_encode($cacheData));
    }

    private function parseCurrentWeatherData($currentWeather): array
    {
        return [
            'coordinates' => [
                'latitude' => $currentWeather['coord']['lat'],
                'longitude' => $currentWeather['coord']['lon']
            ],
            'weather' => $this->convertWeather($currentWeather['weather']),
            'name' => $currentWeather['name'],
            'main' => $this->convertTemperatures($currentWeather['main']),
            'wind' => $this->convertWind($currentWeather['wind']),
            'visibility' => $currentWeather['visibility'],
            'clouds' => $this->convertClouds($currentWeather['clouds']),
            'rain' => $this->convertRain($currentWeather['rain'] ?? []),
            'snow' => $this->convertSnow($currentWeather['snow'] ?? []),
            'dt' => $currentWeather['dt'],
            'sys' => $this->convertSys($currentWeather['sys']),
            'timezone' => $currentWeather['timezone'],
            'id' => $currentWeather['id'],
            'cod' => $currentWeather['cod']
        ];
    }

    private function convertWeather($weather): array
    {
        return array_map(function ($w) {
            return [
                'id' => $w['id'],
                'main' => $w['main'],
                'description' => ucwords($w['description']),
                'icon' => $w['icon']
            ];
        }, $weather);
    }

    private function convertTemperatures($main): array
    {
        return [
            'temp' => $main['temp'],
            'feels_like' => $main['feels_like'],
            'temp_min' => $main['temp_min'],
            'temp_max' => $main['temp_max'],
            'pressure' => $main['pressure'] ?? '',
            'humidity' => $main['humidity'] . '%',
            'sea_level' => $main['sea_level'],
            'ground_level' => $main['grnd_level']
        ];
    }

    private function convertWind($wind): ?array
    {
        return $wind ? [
            'speed' => $wind['speed'] ?? '',
            'deg' => $wind['deg'] ?? '',
            'gust' => $wind['gust'] ?? ''
        ] : null;
    }

    private function convertClouds($clouds): ?array
    {
        return $clouds ? [
            'all' => $clouds['all'] ?? ''
        ] : null;
    }

    private function convertRain($rain): ?array
    {
        return $rain ? [
            '1h' => $rain['1h'] ?? '',
            '3h' => $rain['3h'] ?? ''
        ] : null;
    }

    private function convertSnow($snow): ?array
    {
        return $snow ? [
            '1h' => $snow['1h'] ?? '',
            '3h' => $snow['3h'] ?? ''
        ] : null;
    }

    private function convertSys($sys): ?array
    {
        return $sys ? [
            'type' => $sys['type'] ?? '',
            'id' => $sys['id'] ?? '',
            'country' => $sys['country'] ?? '',
            'sunrise' => $sys['sunrise'] ?? '',
            'sunset' => $sys['sunset'] ?? ''
        ] : null;
    }
}