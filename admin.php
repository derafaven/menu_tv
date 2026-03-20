<?php
session_start();

$configFile = __DIR__ . '/data/config.json';

function getConfig() {
    global $configFile;
    return json_decode(file_get_contents($configFile), true) ?? [];
}

function saveConfig($config) {
    global $configFile;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return true;
}

function getDataFiles() {
    global $dataDir;
    $files = glob(__DIR__ . '/data/data_*.json');
    $result = [];
    foreach ($files as $file) {
        $id = preg_replace('/.*data_(\d+)\.json.*/', '$1', $file);
        $json = json_decode(file_get_contents($file), true);
        $result[$id] = [
            'titulo' => $json['titulo'] ?? 'Sin título',
            'file' => basename($file)
        ];
    }
    ksort($result);
    return $result;
}

function getDataById($id) {
    global $dataDir;
    $file = __DIR__ . '/data/data_' . $id . '.json';
    if (file_exists($file)) {
        return json_decode(file_get_contents($file), true);
    }
    return null;
}

function saveData($id, $data) {
    $file = __DIR__ . '/data/data_' . $id . '.json';
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

function createDataFile($id) {
    $file = __DIR__ . '/data/data_' . $id . '.json';
    if (!file_exists($file)) {
        $data = ['titulo' => 'Nuevo Directorio', 'items' => [], 'mostrar_busqueda' => true, 'mostrar_fecha_clima' => true];
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return true;
    }
    return false;
}

function deleteDataFile($id) {
    $file = __DIR__ . '/data/data_' . $id . '.json';
    if (file_exists($file) && $id != '1') {
        return unlink($file);
    }
    return false;
}

function getNextDataId() {
    $files = glob(__DIR__ . '/data/data_*.json');
    $maxId = 0;
    foreach ($files as $file) {
        $id = (int)preg_replace('/.*data_(\d+)\.json.*/', '$1', $file);
        if ($id > $maxId) $maxId = $id;
    }
    return $maxId + 1;
}

$message = '';
$messageType = '';
$isLoggedIn = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login':
                $config = getConfig();
                if ($_POST['password'] === ($config['clave_admin'] ?? 'admin')) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_login_time'] = time();
                    header('Location: admin.php');
                    exit;
                } else {
                    $message = 'Contraseña incorrecta.';
                    $messageType = 'error';
                }
                break;
                
            case 'logout':
                session_destroy();
                header('Location: admin.php');
                exit;
                break;
        }
    }
}

if (!$isLoggedIn):
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Panel de Administración</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="login-card">
            <h1>🔐 Panel de Administración</h1>
            <p class="login-subtitle">Ingresa la contraseña para continuar</p>
            
            <?php if ($message): ?>
            <div class="alert alert-error"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <form method="post">
                <input type="hidden" name="action" value="login">
                <div class="form-group">
                    <label>Contraseña</label>
                    <input type="password" name="password" required autofocus>
                </div>
                <button type="submit" class="btn" style="width:100%;">Ingresar</button>
            </form>
            
            <div style="text-align:center;margin-top:20px;">
                <a href="index.php" style="color:var(--text-secondary);font-size:0.9rem;">← Volver al inicio</a>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
exit;
endif;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_config':
                $config = [
                    'registros_por_columna' => (int)$_POST['registros_por_columna'],
                    'ancho_columna' => $_POST['ancho_columna'],
                    'tamano_texto' => $_POST['tamano_texto'],
                    'ciudad_default' => $_POST['ciudad_default'],
                    'codigo_pais' => $_POST['codigo_pais'],
                    'clave_admin' => $_POST['clave_admin']
                ];
                saveConfig($config);
                $message = 'Configuración guardada correctamente.';
                $messageType = 'success';
                break;
                
            case 'save_data_text':
                $id = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['data_id']);
                $jsonText = $_POST['json_text'];
                $data = json_decode($jsonText, true);
                if ($data !== null) {
                    saveData($id, $data);
                    $message = 'Datos guardados correctamente.';
                    $messageType = 'success';
                } else {
                    $message = 'Error: JSON inválido.';
                    $messageType = 'error';
                }
                break;
                
            case 'save_item':
                $id = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['data_id']);
                $data = getDataById($id);
                if (!$data) {
                    $data = ['titulo' => '', 'items' => []];
                }
                $data['titulo'] = $_POST['titulo'];
                $data['mostrar_busqueda'] = isset($_POST['mostrar_busqueda']);
                $data['mostrar_fecha_clima'] = isset($_POST['mostrar_fecha_clima']);
                
                $itemIndex = isset($_POST['item_index']) ? (int)$_POST['item_index'] : -1;
                $item = [
                    'nombre' => $_POST['item_nombre'],
                    'url' => $_POST['item_url'],
                    'bus' => $_POST['item_bus']
                ];
                
                if ($itemIndex >= 0 && isset($data['items'][$itemIndex])) {
                    $data['items'][$itemIndex] = $item;
                } else {
                    $data['items'][] = $item;
                }
                
                saveData($id, $data);
                $message = 'Item guardado correctamente.';
                $messageType = 'success';
                break;
                
            case 'delete_item':
                $id = preg_replace('/[^a-zA-Z0-9]/', '', $_POST['data_id']);
                $data = getDataById($id);
                if ($data && isset($_POST['item_index'])) {
                    array_splice($data['items'], (int)$_POST['item_index'], 1);
                    saveData($id, $data);
                    $message = 'Item eliminado correctamente.';
                    $messageType = 'success';
                }
                break;
                
            case 'create_data':
                $newId = getNextDataId();
                createDataFile($newId);
                header('Location: admin.php?action=edit_data&id=' . $newId);
                exit;
                break;
                
            case 'delete_data':
                if (isset($_POST['data_id']) && $_POST['data_id'] != '1') {
                    deleteDataFile($_POST['data_id']);
                    $message = 'Archivo eliminado correctamente.';
                    $messageType = 'success';
                }
                break;
        }
    }
}

$action = $_GET['action'] ?? 'list';
$dataId = preg_replace('/[^a-zA-Z0-9]/', '', $_GET['id'] ?? '');
$config = getConfig();
$dataFiles = getDataFiles();
$currentData = $dataId ? getDataById($dataId) : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <div class="admin-bar" style="margin: -20px -20px 20px -20px;">
            <a href="index.php">&larr; Ver Sitio</a>
            <span>Panel de Administración</span>
            <form method="post" style="margin:0;">
                <input type="hidden" name="action" value="logout">
                <button type="submit" class="btn btn-small btn-secondary">Cerrar Sesión</button>
            </form>
        </div>

        <?php if ($message): ?>
        <div class="alert alert-<?= $messageType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
        <div class="card">
            <h2>Configuración General</h2>
            <form method="post">
                <input type="hidden" name="action" value="save_config">
                <div class="form-group">
                    <label>Registros por columna</label>
                    <input type="number" name="registros_por_columna" value="<?= $config['registros_por_columna'] ?? 15 ?>" min="1" max="100">
                </div>
                <div class="form-group">
                    <label>Ancho de columna (CSS)</label>
                    <input type="text" name="ancho_columna" value="<?= htmlspecialchars($config['ancho_columna'] ?? '280px') ?>">
                </div>
                <div class="form-group">
                    <label>Tamaño de texto (CSS)</label>
                    <input type="text" name="tamano_texto" value="<?= htmlspecialchars($config['tamano_texto'] ?? '14px') ?>">
                </div>
                <hr style="border-color:var(--border);margin:20px 0;">
                <h3 style="margin-bottom:15px;color:var(--accent);">Configuración del Clima</h3>
                <div class="form-group">
                    <label>Ciudad (para el clima)</label>
                    <input type="text" name="ciudad_default" value="<?= htmlspecialchars($config['ciudad_default'] ?? 'Madrid') ?>">
                </div>
                <div class="form-group">
                    <label>Código de país (2 letras, ej: ES, MX, AR)</label>
                    <input type="text" name="codigo_pais" value="<?= htmlspecialchars($config['codigo_pais'] ?? 'ES') ?>" maxlength="2" style="width:80px;">
                </div>
                <hr style="border-color:var(--border);margin:20px 0;">
                <h3 style="margin-bottom:15px;color:var(--accent);">Seguridad</h3>
                <div class="form-group">
                    <label>Contraseña de administrador</label>
                    <input type="password" name="clave_admin" value="<?= htmlspecialchars($config['clave_admin'] ?? 'admin') ?>">
                </div>
                <button type="submit" class="btn">Guardar Configuración</button>
            </form>
        </div>

        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <h2>Archivos de Datos</h2>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="action" value="create_data">
                    <button type="submit" class="btn">+ Crear Nuevo</button>
                </form>
            </div>
            <ul class="file-list">
                <?php foreach ($dataFiles as $id => $info): ?>
                <li>
                    <div>
                        <strong><?= htmlspecialchars($info['titulo']) ?></strong>
                        <br><span><?= $info['file'] ?></span>
                    </div>
                    <div class="file-actions">
                        <a href="admin.php?action=edit_data&id=<?= $id ?>" class="btn btn-small btn-secondary">Editar</a>
                        <?php if ($id != '1'): ?>
                        <form method="post" style="display:inline;">
                            <input type="hidden" name="action" value="delete_data">
                            <input type="hidden" name="data_id" value="<?= $id ?>">
                            <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('¿Eliminar este archivo?')">Eliminar</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <?php elseif ($action === 'edit_data' && $currentData): ?>
        <div class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                <div>
                    <a href="admin.php" class="back-link">&larr; Volver</a>
                    <h2 style="margin-top:10px;">Editar: <?= htmlspecialchars($currentData['titulo']) ?></h2>
                </div>
                <div>
                    <a href="index.php?id=<?= $dataId ?>" class="btn btn-small btn-secondary" target="_blank">Ver</a>
                </div>
            </div>

            <div style="display:flex; gap:10px; margin-bottom:20px;">
                <button class="btn" onclick="showForm()">+ Agregar Item</button>
                <button class="btn btn-secondary" onclick="showJson()">Editar JSON</button>
            </div>

            <div id="form-section" style="display:none;">
                <div class="card" style="background:var(--bg-tertiary);">
                    <h3 id="form-title">Agregar Nuevo Item</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="save_item">
                        <input type="hidden" name="data_id" value="<?= $dataId ?>">
                        <input type="hidden" name="item_index" id="item_index" value="-1">
                        <div class="form-group">
                            <label>Título del directorio</label>
                            <input type="text" name="titulo" id="form_titulo" value="<?= htmlspecialchars($currentData['titulo']) ?>" required>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="mostrar_busqueda" id="form_mostrar_busqueda" <?= ($currentData['mostrar_busqueda'] ?? true) ? 'checked' : '' ?>>
                                    Mostrar búsqueda
                                </label>
                            </div>
                            <div class="form-group">
                                <label>
                                    <input type="checkbox" name="mostrar_fecha_clima" id="form_mostrar_fecha_clima" <?= ($currentData['mostrar_fecha_clima'] ?? true) ? 'checked' : '' ?>>
                                    Mostrar fecha y clima
                                </label>
                            </div>
                        </div>
                        <hr style="border-color:var(--border);margin:15px 0;">
                        <h4 style="margin-bottom:15px;">Datos del enlace</h4>
                        <div class="form-group">
                            <label>Nombre del enlace</label>
                            <input type="text" name="item_nombre" id="item_nombre" required>
                        </div>
                        <div class="form-group">
                            <label>URL base</label>
                            <input type="url" name="item_url" id="item_url" required placeholder="https://ejemplo.com">
                        </div>
                        <div class="form-group">
                            <label>URL de búsqueda (opcional)</label>
                            <input type="text" name="item_bus" id="item_bus" placeholder="/?s= o https://ejemplo.com/search?q=">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Guardar</button>
                            <button type="button" class="btn btn-secondary" onclick="hideForm()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="json-section" style="display:none;">
                <div class="card" style="background:var(--bg-tertiary);">
                    <h3>Editor JSON</h3>
                    <form method="post">
                        <input type="hidden" name="action" value="save_data_text">
                        <input type="hidden" name="data_id" value="<?= $dataId ?>">
                        <div class="form-group">
                            <textarea name="json_text" id="json_editor"><?= htmlspecialchars(json_encode($currentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn">Guardar JSON</button>
                            <button type="button" class="btn btn-secondary" onclick="hideJson()">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="items-list">
                <h3 style="margin-bottom:15px;">Items (<?= count($currentData['items'] ?? []) ?>)</h3>
                <?php if (!empty($currentData['items'])): ?>
                <div class="items-editor">
                    <div class="item-row items-header">
                        <span>Nombre</span>
                        <span>URL</span>
                        <span>Búsqueda</span>
                        <span>Acciones</span>
                    </div>
                    <?php foreach ($currentData['items'] as $index => $item): ?>
                    <div class="item-row">
                        <span><?= htmlspecialchars($item['nombre']) ?></span>
                        <span style="font-size:12px;color:var(--text-secondary);"><?= htmlspecialchars($item['url']) ?></span>
                        <span style="font-size:12px;color:var(--accent);"><?= htmlspecialchars($item['bus'] ?? '-') ?></span>
                        <div class="file-actions">
                            <button type="button" class="btn btn-small btn-secondary" onclick="editItem(<?= $index ?>)">Editar</button>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="action" value="delete_item">
                                <input type="hidden" name="data_id" value="<?= $dataId ?>">
                                <input type="hidden" name="item_index" value="<?= $index ?>">
                                <button type="submit" class="btn btn-small btn-danger" onclick="return confirm('¿Eliminar este item?')">X</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="no-data">No hay items. Agrega uno nuevo.</p>
                <?php endif; ?>
            </div>
        </div>

        <script>
        const items = <?= json_encode($currentData['items'] ?? []) ?>;
        const currentDataGlobal = <?= json_encode($currentData) ?>;
        
        function showForm() {
            document.getElementById('form-section').style.display = 'block';
            document.getElementById('json-section').style.display = 'none';
            document.getElementById('form-title').textContent = 'Agregar Nuevo Item';
            document.getElementById('item_index').value = -1;
            document.getElementById('item_nombre').value = '';
            document.getElementById('item_url').value = '';
            document.getElementById('item_bus').value = '';
            document.getElementById('form_titulo').value = currentDataGlobal.titulo || '';
            document.getElementById('form_mostrar_busqueda').checked = currentDataGlobal.mostrar_busqueda !== false;
            document.getElementById('form_mostrar_fecha_clima').checked = currentDataGlobal.mostrar_fecha_clima !== false;
        }
        
        function hideForm() {
            document.getElementById('form-section').style.display = 'none';
        }
        
        function showJson() {
            document.getElementById('json-section').style.display = 'block';
            document.getElementById('form-section').style.display = 'none';
        }
        
        function hideJson() {
            document.getElementById('json-section').style.display = 'none';
        }
        
        function editItem(index) {
            const item = items[index];
            document.getElementById('form-section').style.display = 'block';
            document.getElementById('json-section').style.display = 'none';
            document.getElementById('form-title').textContent = 'Editar Item';
            document.getElementById('item_index').value = index;
            document.getElementById('item_nombre').value = item.nombre || '';
            document.getElementById('item_url').value = item.url || '';
            document.getElementById('item_bus').value = item.bus || '';
            document.getElementById('form_titulo').value = currentDataGlobal.titulo || '';
            document.getElementById('form_mostrar_busqueda').checked = currentDataGlobal.mostrar_busqueda !== false;
            document.getElementById('form_mostrar_fecha_clima').checked = currentDataGlobal.mostrar_fecha_clima !== false;
        }
        </script>
        <?php endif; ?>

        <footer>
            <p>Panel de Administración &copy; <?= date('Y') ?></p>
        </footer>
    </div>
</body>
</html>
