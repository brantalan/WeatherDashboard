<?php
require 'config.php';
require 'classes/weather.php';
require 'templates/header.php';

$weather = new Weather();
$weather_data = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $city = htmlspecialchars($_POST['city']);
    $state = htmlspecialchars($_POST['state']);
    $country_code = htmlspecialchars($_POST['country_code']);
    try {
        $weather_data = $weather->fetchWeatherData($city, $state, $country_code);
    } catch (Exception $e) {
        $error = "Could not fetch weather data. Please try again.";
    }
}
?>

<div class="container">
    <h1>Weather Dashboard</h1>
    <form method="POST">
        <input type="text" name="city" placeholder="City Name" required>
        <input type="text" name="state" placeholder="State" required>
        <input type="text" name="country_code" placeholder="Country Code (Optional)">
        <button type="submit">Get Weather</button>
    </form>

    <?php if ($error): ?>
        <p class="error"><?= $error ?></p>
    <?php elseif ($weather_data): ?>
        <div class="weather-info">
            <h2>Weather in <?= $weather_data['name'] ?></h2>
            <p>Temperature: <?= $weather_data['main']['temp'] ?>°F</p>
            <p>Humidity: <?= $weather_data['main']['humidity'] ?></p>
            <p>Wind Speed: <?= $weather_data['wind']['speed'] ?> m/s</p>
            <p>Description: <?= $weather_data['weather'][0]['description'] ?></p>
        </div>
    <?php endif; ?>
</div>

<?php require 'templates/footer.php'; ?>
