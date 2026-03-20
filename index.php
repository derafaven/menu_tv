<?php
$configFile = __DIR__ . '/data/config.json';
$config = json_decode(file_get_contents($configFile), true) ?? [];

$registrosPorColumna = $config['registros_por_columna'] ?? 15;
$anchoColumna = $config['ancho_columna'] ?? '280px';
$tamanoTexto = $config['tamano_texto'] ?? '14px';
$ciudadDefault = $config['ciudad_default'] ?? 'Madrid';
$codigoPais = $config['codigo_pais'] ?? 'ES';

$dataId = isset($_GET['id']) ? preg_replace('/[^a-zA-Z0-9_]/', '', $_GET['id']) : '1';
$dataFile = __DIR__ . '/data/data_' . $dataId . '.json';

if (!file_exists($dataFile)) {
    $dataFile = __DIR__ . '/data/data_1.json';
    $dataId = '1';
}

$data = json_decode(file_get_contents($dataFile), true) ?? [];
$titulo = $data['titulo'] ?? 'Directorio de Enlaces';
$items = $data['items'] ?? [];
$mostrarBusqueda = $data['mostrar_busqueda'] ?? true;
$mostrarFechaClima = $data['mostrar_fecha_clima'] ?? true;

$searchValue = isset($_GET['b']) ? htmlspecialchars(trim($_GET['b'])) : '';

function buildUrl($item, $search) {
    if (empty($search)) {
        return $item['url'];
    }
    
    $bus = $item['bus'] ?? '';
    
    if (empty($bus)) {
        return $item['url'];
    }
    
    if (strpos($bus, 'http') === 0) {
        return $bus . urlencode($search);
    } else {
        return rtrim($item['url'], '/') . '/' . ltrim($bus, '/') . urlencode($search);
    }
}

function getWeatherIcon($code) {
    $icons = [
        0 => '☀️',
        1 => '🌤️',
        2 => '⛅',
        3 => '☁️',
        45 => '🌫️',
        48 => '🌫️',
        51 => '🌧️',
        53 => '🌧️',
        55 => '🌧️',
        61 => '🌧️',
        63 => '🌧️',
        65 => '🌧️',
        71 => '🌨️',
        73 => '🌨️',
        75 => '🌨️',
        77 => '❄️',
        80 => '🌦️',
        81 => '🌦️',
        82 => '🌦️',
        85 => '🌨️',
        86 => '🌨️',
        95 => '⛈️',
        96 => '⛈️',
        99 => '⛈️'
    ];
    return $icons[$code] ?? '🌡️';
}

function getGeoLocation($city, $countryCode) {
    $url = "https://geocoding-api.open-meteo.com/v1/search?name=" . urlencode($city) . "&count=1&language=es&format=json";
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $data = json_decode($response, true);
        if (!empty($data['results'])) {
            foreach ($data['results'] as $result) {
                if (strtoupper($result['country_code'] ?? '') === strtoupper($countryCode)) {
                    return ['lat' => $result['latitude'], 'lon' => $result['longitude'], 'name' => $result['name']];
                }
            }
            return ['lat' => $data['results'][0]['latitude'], 'lon' => $data['results'][0]['longitude'], 'name' => $data['results'][0]['name']];
        }
    }
    return null;
}

$geoData = getGeoLocation($ciudadDefault, $codigoPais);
$weatherData = null;
$hourlyForecast = [];

if ($geoData) {
    $lat = $geoData['lat'];
    $lon = $geoData['lon'];
    $url = "https://api.open-meteo.com/v1/forecast?latitude={$lat}&longitude={$lon}&current=temperature_2m,weather_code&hourly=temperature_2m,weather_code&timezone=auto&forecast_days=1";
    $context = stream_context_create(['http' => ['timeout' => 5]]);
    $response = @file_get_contents($url, false, $context);
    if ($response) {
        $weatherData = json_decode($response, true);
        if ($weatherData && isset($weatherData['hourly'])) {
            $currentHour = (int)date('G');
            for ($i = $currentHour; $i < min($currentHour + 12, count($weatherData['hourly']['time'])); $i++) {
                $hourlyForecast[] = [
                    'time' => date('H:i', strtotime($weatherData['hourly']['time'][$i])),
                    'temp' => round($weatherData['hourly']['temperature_2m'][$i]),
                    'code' => $weatherData['hourly']['weather_code'][$i]
                ];
            }
        }
    }
}

$globFiles = glob(__DIR__ . '/data/data_*.json');
$availableData = [];
foreach ($globFiles as $file) {
    $id = preg_replace('/.*data_(\d+)\.json.*/', '$1', $file);
    $json = json_decode(file_get_contents($file), true);
    $availableData[$id] = $json['titulo'] ?? 'Sin título';
}
ksort($availableData);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .columns { --column-width: <?= $anchoColumna ?>; }
        .column a { --text-size: <?= $tamanoTexto ?>; }
    </style>
</head>
<body>
    <div class="admin-bar">
        <a href="admin.php">&larr; Panel de Administración</a>
        <div>
            <?php if (count($availableData) > 1): ?>
            <select onchange="window.location.href='index.php?id='+this.value" style="padding:6px 12px;background:var(--bg-tertiary);color:var(--text-primary);border:1px solid var(--border);border-radius:6px;cursor:pointer;">
                <?php foreach ($availableData as $id => $title): ?>
                <option value="<?= $id ?>" <?= $id == $dataId ? 'selected' : '' ?>><?= htmlspecialchars($title) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
        </div>
    </div>

    <div class="container">
        <header>
            <h1><?= htmlspecialchars($titulo) ?></h1>
            <?php if ($mostrarBusqueda): ?>
            <form class="search-bar" method="get">
                <input type="hidden" name="id" value="<?= htmlspecialchars($dataId) ?>">
                <input type="text" name="b" placeholder="Buscar..." value="<?= $searchValue ?>">
                <button type="submit">Buscar</button>
            </form>
            <?php endif; ?>
        </header>

        <?php if ($mostrarFechaClima): ?>
        <div class="weather-widget" id="weather-widget">
            <div class="weather-main">
                <div class="weather-info">
                    <span class="weather-city"><?= htmlspecialchars($geoData['name'] ?? $ciudadDefault) ?></span>
                    <div class="weather-current">
                        <span class="weather-icon" id="weather-icon"><?= getWeatherIcon($weatherData['current']['weather_code'] ?? 0) ?></span>
                        <span class="weather-temp" id="weather-temp"><?= round($weatherData['current']['temperature_2m'] ?? 0) ?>°C</span>
                    </div>
                </div>
                <div class="weather-time">
                    <div class="clock" id="clock">00:00:00</div>
                    <div class="date" id="date"></div>
                </div>
            </div>
            <?php if (!empty($hourlyForecast)): ?>
            <div class="hourly-forecast">
                <?php foreach ($hourlyForecast as $hour): ?>
                <div class="hour-item">
                    <span class="hour-time"><?= $hour['time'] ?></span>
                    <span class="hour-icon"><?= getWeatherIcon($hour['code']) ?></span>
                    <span class="hour-temp"><?= $hour['temp'] ?>°</span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (empty($items)): ?>
        <div class="no-data">
            <p>No hay enlaces disponibles.</p>
        </div>
        <?php else: ?>
        <div class="columns">
            <?php
            $chunks = array_chunk($items, $registrosPorColumna);
            foreach ($chunks as $index => $chunk):
            ?>
            <div class="column">
                <div class="column-title">Enlaces <?= ($index * $registrosPorColumna + 1) ?>-<?= min(($index + 1) * $registrosPorColumna, count($items)) ?></div>
                <ul>
                    <?php foreach ($chunk as $item): 
                        $url = buildUrl($item, $searchValue);
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer">
                            <?= htmlspecialchars($item['nombre']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <footer>
            <p>Directorio de Enlaces &copy; <?= date('Y') ?></p>
        </footer>
    </div>

    <script>
    function updateClock() {
        const now = new Date();
        const timeStr = now.toLocaleTimeString('es-ES', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        const dateStr = now.toLocaleDateString('es-ES', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
        
        const clockEl = document.getElementById('clock');
        const dateEl = document.getElementById('date');
        
        if (clockEl) clockEl.textContent = timeStr;
        if (dateEl) dateEl.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
    }
    
    updateClock();
    setInterval(updateClock, 1000);
    </script>
</body>
</html>
