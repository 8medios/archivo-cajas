<?php
// error_log_viewer.php - Visor de logs de Apache (compatible con Windows)

// CONFIGURACIÓN: AJUSTA ESTA RUTA SEGÚN TU INSTALACIÓN DE XAMPP
// Por ejemplo, si XAMPP está en C:\xampp, la ruta sería:
$logFilePath = 'C:\\xampp\\apache\\logs\\error.log';
// Si quieres ver los logs de PHP en XAMPP, podría ser:
// $logFilePath = 'C:\\xampp\\php\\logs\\php_error_log';
// Asegúrate de usar doble barra invertida (\\) para las rutas de Windows en PHP.

// --- ADVERTENCIA DE SEGURIDAD IMPORTANTE ---
// --- Asegúrate de que el usuario de PHP tenga PERMISOS DE LECTURA sobre este archivo.

$ip = isset($_SERVER['HTTP_CLIENT_IP'])
    ? $_SERVER['HTTP_CLIENT_IP']
    : (isset($_SERVER['HTTP_X_FORWARDED_FOR'])
        ? $_SERVER['HTTP_X_FORWARDED_FOR']
        : $_SERVER['REMOTE_ADDR']);

$searchFilter = isset($_GET['grep']) ? $_GET['grep'] : $ip; // Valor del filtro
$n = isset($_GET['n']) ? (int)$_GET['n'] : 300; // Número de líneas a mostrar

$salida = ''; // Inicializar la salida del log
$errorMessage = ''; // Para mensajes de error al cargar el log

try {
    if (!file_exists($logFilePath)) {
        throw new Exception("El archivo de log no existe en la ruta: " . htmlspecialchars($logFilePath) . ". Por favor, verifica la variable \$logFilePath en error_log_viewer.php.");
    }
    if (!is_readable($logFilePath)) {
        throw new Exception("PHP no tiene permisos de lectura para el archivo de log: " . htmlspecialchars($logFilePath) . ". Asegúrate de que el usuario de tu servidor web tiene acceso de lectura.");
    }

    // Leer todo el contenido del archivo de log
    $fileContent = file_get_contents($logFilePath);
    if ($fileContent === false) {
        throw new Exception("No se pudo leer el contenido del archivo de log.");
    }

    $allLogs = explode("\n", $fileContent);

    $filteredLogs = [];
    foreach ($allLogs as $logLine) {
        // Aplicar el filtrado (similar a 'grep') directamente en PHP
        // strpos() es más eficiente para búsquedas simples de subcadenas.
        // stripos() para búsqueda insensible a mayúsculas/minúsculas.
        if (empty($searchFilter) || stripos($logLine, $searchFilter) !== false) {
            $filteredLogs[] = $logLine;
        }
    }

    // Obtener las últimas N líneas (similar a 'tail -n')
    // array_slice con un índice negativo obtiene los últimos N elementos.
    $linesToShow = array_slice($filteredLogs, -$n);
    $salida = implode("\n", $linesToShow);

} catch (Exception $e) {
    $errorMessage = $e->getMessage();
}

$autoRefreshActive = isset($_GET['autoRefresh']) ? $_GET['autoRefresh'] : 'false';
$refreshInterval = isset($_GET['interval']) ? (int)$_GET['interval'] : 5;

// La descripción del comando ahora es informativa, no el comando shell real
$commandDisplay = "Mostrando las últimas " . $n . " líneas del log: " . htmlspecialchars($logFilePath);
if (!empty($searchFilter)) {
    $commandDisplay .= " | Filtrando por: '" . htmlspecialchars($searchFilter) . "'";
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ERRORLOG</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* CSS proporcionado por el usuario */
        :root {
            --primary-color: #4E73DF;
            --primary-hover: #2C4FA4;
            --error-color: #D9534F;
            --warning-color: #F0AD4E;
            --fatal-color: #C9302C;
            --light-bg: #f9f9f9;
            --dark-bg: #121212;
            --light-text: #333;
            --dark-text: #f9f9f9;
            --shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 20px;
            transition: all 0.3s ease;
            background-color: var(--light-bg);
            color: var(--light-text);
        }

        .main-title {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 1.5rem;
            font-size: 2rem;
        }

        .command-info {
            max-width: 80%;
            margin: 0 auto 2rem;
            padding: 1rem;
            background-color: rgba(78, 115, 223, 0.1);
            border-radius: 8px;
        }

        .command-info h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .command-info h4 {
            font-size: 0.9rem;
            color: #666;
            margin: 0;
        }

        .log-container {
            background-color: rgba(128, 128, 128, 0.1);
            border-radius: 12px;
            padding: 1.5rem;
            max-width: 90%;
            margin: 20px auto;
            box-shadow: var(--shadow);
        }

        .controls {
            display: flex;
            flex-wrap: wrap; /* Added for responsiveness */
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .btn {
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            white-space: nowrap; /* Prevent text wrapping */
        }

        .theme-toggle-btn {
            background-color: var(--primary-color);
            color: white;
        }

        .copy-all-btn {
            background-color: #28a745;
            color: white;
        }

        .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow);
        }

        .log-output {
            background-color: white;
            padding: 1.5rem;
            border-radius: 8px;
            border: 1px solid #ddd;
            box-shadow: var(--shadow);
            overflow-x: auto; /* Added for horizontal scrolling */
        }

        .log-entry {
            position: relative;
            padding: 0.75rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            background-color: rgba(0, 0, 0, 0.02);
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            transition: all 0.2s ease;
        }

        .log-entry:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }

        .log-content {
            margin: 0;
            flex-grow: 1;
            font-family: 'Consolas', monospace;
            font-size: 0.9rem;
            white-space: pre-wrap;
            word-wrap: break-word;
        }

        .copy-btn {
            background: transparent;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            opacity: 0.6;
            transition: all 0.2s ease;
            flex-shrink: 0; /* Prevent button from shrinking */
        }

        .copy-btn:hover {
            opacity: 1;
            transform: scale(1.1);
        }

        .copied {
            color: #28a745;
        }

        .error {
            color: var(--error-color);
            font-weight: bold;
            background-color: #F9E6E6;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .warning {
            color: var(--warning-color);
            font-weight: bold;
            background-color: #FFF3CD;
            padding: 3px 6px;
            border-radius: 3px;
        }

        .fatal {
            color: var(--fatal-color);
            font-weight: bold;
            background-color: #F2D7D5;
            padding: 3px 6px;
            border-radius: 3px;
        }

        /* Tema oscuro */
        .dark-theme {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        .dark-theme .log-container {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .dark-theme-output {
            background-color: #1a1a1a;
            color: var(--dark-text);
            border-color: #333;
        }

        .dark-theme .log-entry {
            background-color: rgba(255, 255, 255, 0.03);
        }

        .dark-theme .log-entry:hover {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .dark-theme .command-info {
            background-color: rgba(78, 115, 223, 0.2);
        }

        .refresh-btn {
            background-color: #17a2b8;
            color: white;
        }

        .refresh-btn:hover {
            background-color: #138496;
        }

        .auto-refresh-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .auto-refresh-btn {
            background-color: #6f42c1;
            color: white;
        }

        .auto-refresh-btn:hover {
            background-color: #5a32a3;
        }

        .auto-refresh-btn.active {
            background-color: #dc3545;
        }

        .auto-refresh-btn.active:hover {
            background-color: #c82333;
        }

        .clear-btn {
            background-color: #FF0033;
            color: white;
        }

        .clear-btn:hover {
            background-color: #E6002E; /* Slightly darker hover */
        }

        .timer-controls {
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .timer-input {
            width: 60px;
            padding: 0.5rem;
            border-radius: 4px;
            border: 1px solid #ccc;
            text-align: center;
        }

        .btn-danger {
            background-color: #dc3545; /* Red color for 'Errores' button */
            color: white;
        }

        .btn-danger:hover {
            background-color: #c82333;
        }

        @media (max-width: 768px) {
            .log-container {
                max-width: 95%;
                padding: 1rem;
            }
            
            .controls {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
            .auto-refresh-container {
                flex-direction: column;
                align-items: stretch;
                gap: 0.5rem;
            }
            .timer-controls {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<h1 class='main-title'>ERROR LOG - <?php echo htmlspecialchars($ip); ?></h1>
<div class='command-info'>
    <h3>Comando: <code><?php echo htmlspecialchars($commandDisplay); ?></code></h3>
    <h4>Para filtrar por algo distinto, agrega el parámetro **?grep=CONTENIDO**</h4>
</div>

<div class='log-container'>
    <div class='controls'>
        <button onclick='toggleTheme()' class='btn theme-toggle-btn'><i class='fas fa-adjust'></i> Cambiar Tema</button>
        <button onclick='copyAllLogs()' class='btn copy-all-btn'><i class='fas fa-copy'></i> Copiar Todo</button>
        <button onclick='refreshPage()' class='btn refresh-btn'><i class='fas fa-sync-alt'></i> Refrescar</button>
        <div class='auto-refresh-container'>
            <button onclick='toggleAutoRefresh()' class='btn auto-refresh-btn'><i class='fas fa-clock'></i> Auto Refrescar</button>
            <div class='timer-controls'>
                <input type='number' id='refreshInterval' min='1' value='<?php echo htmlspecialchars($refreshInterval); ?>' class='timer-input' title='Intervalo en segundos'>
                <span class='timer-text'>seg</span>
            </div>
        </div>
        <a href="error_log_clear.php" style="text-decoration: none;">
            <button onclick="return confirm('¿Estás seguro de que quieres limpiar el log? ¡Esta acción es irreversible!');" class="btn clear-btn"><i class="fas fa-trash"></i> Limpiar</button>
        </a>
        <a href="error_log_viewer.php?n=10000&grep=error" style="text-decoration: none;">
            <button class="btn btn-danger"><i class="fas fa-exclamation-triangle"></i> Errores (10k líneas)</button>
        </a>
    </div>
    <div id='log-output' class='log-output'>

    <?php
    if (!empty($errorMessage)) {
        echo "<div class='text-center py-8 text-red-500'>" . htmlspecialchars($errorMessage) . "</div>";
    } elseif (trim($salida) === '') {
        echo "<div class='text-center py-8 text-gray-500'>No hay entradas de log que coincidan con el filtro.</div>";
    } else {
        $logs = explode("\n", $salida);
        foreach ($logs as $index => $log) {
            if (trim($log) !== '') {
                // Aplicar estilos de resaltado
                $logDisplay = htmlspecialchars($log); // Sanitizar el contenido del log
                $logDisplay = str_ireplace('Error', '<span class="error">Error</span>', $logDisplay); // str_ireplace para ser insensible a mayúsculas/minúsculas
                $logDisplay = str_ireplace('Warning', '<span class="warning">Warning</span>', $logDisplay);
                $logDisplay = str_ireplace('Fatal', '<span class="fatal">Fatal</span>', $logDisplay);
                
                echo "<div class='log-entry' id='log-$index'>";
                echo "<pre class='log-content'>".str_replace("\\n", "<br>", $logDisplay)."</pre>";
                echo "<button onclick='copyLog(".$index.")' class='copy-btn' title='Copiar log'>";
                echo "<i class='fas fa-copy'></i>";
                echo "</button>";
                echo "</div>";
            }
        }
    }
    ?>

    </div>
</div>

<script>
    let autoRefreshInterval = null;
    let remainingTime = 0;
    let totalTime = 0;

    // Función para obtener parámetros de la URL
    function getUrlParameter(name) {
        const params = new URLSearchParams(window.location.search);
        return params.get(name);
    }

    // Función para actualizar la URL con los parámetros de auto-refresh
    function updateUrlParameters(autoRefresh, interval) {
        const url = new URL(window.location.href);
        url.searchParams.set('autoRefresh', autoRefresh);
        url.searchParams.set('interval', interval);
        window.history.replaceState({}, '', url);
    }

    // Inicializar el estado del auto-refresh basado en la URL
    function initAutoRefresh() {
        const autoRefresh = getUrlParameter('autoRefresh');
        const interval = getUrlParameter('interval');
        
        if (autoRefresh === 'true' && interval) {
            const btn = document.querySelector('.auto-refresh-btn');
            const input = document.getElementById('refreshInterval');
            input.value = interval;
            input.disabled = true;
            btn.classList.add('active');
            startAutoRefresh(parseInt(interval));
            btn.innerHTML = `<i class="fas fa-stop"></i> Detener (<span class="countdown">${interval}</span>s)`;
        }
    }

    function toggleAutoRefresh() {
        const btn = document.querySelector('.auto-refresh-btn');
        const input = document.getElementById('refreshInterval');
        
        if (autoRefreshInterval) {
            // Detener el auto-refresh
            stopAutoRefresh();
            btn.innerHTML = '<i class="fas fa-clock"></i> Auto Refrescar';
            btn.classList.remove('active');
            input.disabled = false;
            updateUrlParameters('false', input.value);
        } else {
            // Iniciar el auto-refresh
            const seconds = parseInt(input.value) || 5;
            if (seconds < 1) {
                alert('Por favor ingresa un número mayor a 0');
                return;
            }
            
            startAutoRefresh(seconds);
            btn.innerHTML = `<i class="fas fa-stop"></i> Detener (<span class="countdown">${seconds}</span>s)`;
            btn.classList.add('active');
            input.disabled = true;
            updateUrlParameters('true', seconds);
        }
    }

    function startAutoRefresh(seconds) {
        totalTime = seconds;
        remainingTime = seconds;
        
        autoRefreshInterval = setInterval(() => {
            remainingTime--;
            updateCountdown();
            
            if (remainingTime <= 0) {
                refreshPage(false); // No pass event to avoid issues
            }
        }, 1000);
    }

    function stopAutoRefresh() {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
        remainingTime = 0;
    }

    function updateCountdown() {
        const countdown = document.querySelector('.countdown');
        if (countdown) {
            countdown.textContent = remainingTime;
        }
    }

    function refreshPage() {
        const btn = document.querySelector('.refresh-btn');
        const icon = btn.querySelector('i');
        
        // Añadir clase de animación al icono
        icon.classList.add('fa-spin');
        
        // Pequeño delay para mostrar la animación
        setTimeout(() => {
            window.location.reload();
        }, 300);
    }

    function copyLog(index) {
        const logContent = document.querySelector(`#log-${index} .log-content`).textContent;
        const success = fallbackCopyToClipboard(logContent);
        if (success) {
            showCopyFeedback(index);
        }
    }

    function copyAllLogs() {
        const allLogs = Array.from(document.querySelectorAll('.log-content'))
            .map(log => log.textContent)
            .join('\n');
        const success = fallbackCopyToClipboard(allLogs);
        if (success) {
            showGlobalCopyFeedback();
        }
    }

    function fallbackCopyToClipboard(text) {
        try {
            // Crear un elemento temporal
            const tempTextArea = document.createElement('textarea');
            tempTextArea.value = text;
            
            // Asegurar que el textarea está fuera de la vista
            tempTextArea.style.position = 'fixed';
            tempTextArea.style.left = '-9999px';
            tempTextArea.style.top = '0';
            
            document.body.appendChild(tempTextArea);
            
            // Seleccionar y copiar el texto
            tempTextArea.select();
            document.execCommand('copy');
            
            // Limpiar
            document.body.removeChild(tempTextArea);
            
            return true;
        } catch (err) {
            console.error('Error al copiar:', err);
            alert('No se pudo copiar al portapapeles');
            return false;
        }
    }

    function showCopyFeedback(index) {
        const btn = document.querySelector(`#log-${index} .copy-btn`);
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.add('copied');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('copied');
        }, 2000);
    }

    function showGlobalCopyFeedback() {
        const btn = document.querySelector('.copy-all-btn');
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i> Copiado';
        btn.classList.add('copied');
        
        setTimeout(() => {
            btn.innerHTML = originalHTML;
            btn.classList.remove('copied');
        }, 2000);
    }

    function toggleTheme() {
        const body = document.body;
        const logOutput = document.getElementById('log-output');
        
        body.classList.toggle('dark-theme');
        body.classList.toggle('light-theme');
        logOutput.classList.toggle('dark-theme-output');
        logOutput.classList.toggle('light-theme-output');
    }

    // Inicializar el auto-refresh cuando la página carga
    document.addEventListener('DOMContentLoaded', initAutoRefresh);

</script>
</body>
</html>