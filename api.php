<?php
// Buffer ALL output from the very first byte — prevents any warning/notice/whitespace
// from corrupting the JSON response before headers are sent.
ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// JSON output helper: clears the buffer, sends clean JSON, exits.
function sendJson(array $data): void {
    ob_end_clean();
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('X-Content-Type-Options: nosniff');
    }
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Catch any uncaught exception at the top level.
set_exception_handler(function (Throwable $e) {
    sendJson(['error' => 'Server error: ' . $e->getMessage()]);
});

try {
    $action = $_GET['action'] ?? '';
    $target = trim($_GET['target'] ?? '');

    if ($target === '') {
        sendJson(['error' => 'No target specified']);
    }

    // Sanitize: allow domain names and IPs only
    if (!preg_match('/^[a-zA-Z0-9.\-_:\[\]]+$/', $target)) {
        sendJson(['error' => 'Invalid target — only hostnames and IP addresses are allowed']);
    }

    // Strip protocol if accidentally included
    $target = preg_replace('#^https?://#', '', $target);
    $target = rtrim($target, '/');

    // Extract host (strip path for DNS purposes)
    $host = parse_url('http://' . $target, PHP_URL_HOST) ?: $target;

    switch ($action) {
        case 'speed':
            doSpeedTest($host);
            break;
        case 'ports':
            $ports = $_GET['ports'] ?? 'common';
            doPortScan($host, $ports);
            break;
        case 'email':
            doEmailCheck($host);
            break;
        default:
            sendJson(['error' => 'Unknown action: ' . $action]);
    }
} catch (Throwable $e) {
    sendJson(['error' => 'Unexpected error: ' . $e->getMessage()]);
}

// ─── Speed Test ───────────────────────────────────────────────────────────────
function doSpeedTest($host) {
    $results = [];

    // DNS resolution time
    $t0 = microtime(true);
    $ip = gethostbyname($host);
    $dnsTime = round((microtime(true) - $t0) * 1000, 2);

    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        sendJson(['error' => "Could not resolve host: $host"]);
    }

    $results['dns_ms'] = $dnsTime;
    $results['resolved_ip'] = $ip;

    // TCP connect time (port 80)
    $tcpTime = null;
    $t0 = microtime(true);
    $sock = @fsockopen($host, 80, $errno, $errstr, 5);
    if ($sock) {
        $tcpTime = round((microtime(true) - $t0) * 1000, 2);
        fclose($sock);
    }
    $results['tcp_connect_ms'] = $tcpTime;

    // TTFB via HTTP (curl)
    $ttfb = null;
    $totalTime = null;
    $downloadSize = null;
    $httpStatus = null;

    $url = "http://$host";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 3,
        CURLOPT_USERAGENT      => 'NetProbe/1.0',
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $body = curl_exec($ch);
    if (!curl_errno($ch)) {
        $info = curl_getinfo($ch);
        $ttfb         = round($info['starttransfer_time'] * 1000, 2);
        $totalTime    = round($info['total_time'] * 1000, 2);
        $downloadSize = strlen($body);
        $httpStatus   = $info['http_code'];
        $results['ttfb_ms']         = $ttfb;
        $results['total_time_ms']   = $totalTime;
        $results['download_bytes']  = $downloadSize;
        $results['http_status']     = $httpStatus;
        $results['redirect_url']    = $info['redirect_url'] ?: null;
        // Rough throughput
        if ($totalTime > 0 && $downloadSize > 0) {
            $results['throughput_kbps'] = round(($downloadSize / 1024) / ($totalTime / 1000), 2);
        }
    }
    curl_close($ch);

    // Ping-style ICMP via exec (may be blocked on shared hosts)
    $pingMs = null;
    $pingOutput = [];
    @exec("ping -c 3 -W 2 " . escapeshellarg($host) . " 2>&1", $pingOutput, $ret);
    if ($ret === 0) {
        foreach ($pingOutput as $line) {
            if (preg_match('/avg\s*=\s*[\d.]+\/([\d.]+)/', $line, $m)) {
                $pingMs = (float)$m[1];
            } elseif (preg_match('/time=([\d.]+)\s*ms/', $line, $m)) {
                $pingMs = (float)$m[1];
            }
        }
    }
    $results['ping_ms'] = $pingMs;
    $results['ping_available'] = ($pingMs !== null);

    sendJson(['ok' => true, 'target' => $host, 'results' => $results]);
}

// ─── Port Scan ────────────────────────────────────────────────────────────────
function doPortScan($host, $portsParam) {
    $commonPorts = [
        21 => 'FTP',
        22 => 'SSH',
        23 => 'Telnet',
        25 => 'SMTP',
        53 => 'DNS',
        80 => 'HTTP',
		81 => 'HTTP Alt.',
        110 => 'POP3',
        143 => 'IMAP',
        443 => 'HTTPS',
        465 => 'SMTPS',
        587 => 'SMTP Submission',
        993 => 'IMAPS',
        995 => 'POP3S',
        3306 => 'MySQL',
        3389 => 'RDP',
        5432 => 'PostgreSQL',
        6379 => 'Redis',
        8080 => 'HTTP Alt.',
		8123 => 'Home Assistant',
        8443 => 'HTTPS Alt.',
        9443 => 'HTTPS Alt.',
        27017 => 'MongoDB',
    ];

    if ($portsParam === 'common') {
        $ports = array_keys($commonPorts);
    } else {
        // Custom ports: comma-separated
        $ports = array_filter(array_map('intval', explode(',', $portsParam)));
        $ports = array_filter($ports, fn($p) => $p > 0 && $p <= 65535);
        $ports = array_slice(array_values($ports), 0, 50); // max 50
    }

    // Resolve IP first
    $ip = gethostbyname($host);
    if ($ip === $host && !filter_var($host, FILTER_VALIDATE_IP)) {
        sendJson(['error' => "Could not resolve host: $host"]);
    }

    $results = [];
    $timeout = 1.5;

    foreach ($ports as $port) {
        $t0 = microtime(true);
        $sock = @fsockopen($ip, $port, $errno, $errstr, $timeout);
        $elapsed = round((microtime(true) - $t0) * 1000, 2);

        $open = ($sock !== false);
        if ($sock) fclose($sock);

        $results[] = [
            'port'    => (int)$port,
            'service' => $commonPorts[$port] ?? 'Unknown',
            'open'    => $open,
            'ms'      => $open ? $elapsed : null,
        ];
    }

    sendJson([
        'ok'          => true,
        'target'      => $host,
        'resolved_ip' => $ip,
        'scanned'     => count($results),
        'open_count'  => count(array_filter($results, fn($r) => $r['open'])),
        'ports'       => $results,
    ]);
}

// ─── Email Check ──────────────────────────────────────────────────────────────
function doEmailCheck($host) {
    $checks = [];

    // MX Records
    $mxRecords = [];
    if (getmxrr($host, $mxHosts, $mxWeights)) {
        array_multisort($mxWeights, SORT_ASC, $mxHosts);
        foreach ($mxHosts as $i => $mx) {
            $mxRecords[] = ['host' => $mx, 'priority' => $mxWeights[$i]];
        }
    }
    $checks['mx_records'] = $mxRecords;
    $checks['mx_ok'] = !empty($mxRecords);

    // SPF Record
    $spf = null;
    $txtRecords = dns_get_record($host, DNS_TXT);
    foreach ($txtRecords ?: [] as $rec) {
        if (isset($rec['txt']) && str_starts_with($rec['txt'], 'v=spf1')) {
            $spf = $rec['txt'];
            break;
        }
    }
    $checks['spf_record'] = $spf;
    $checks['spf_ok'] = $spf !== null;

    // DMARC Record
    $dmarc = null;
    $dmarcRecords = dns_get_record('_dmarc.' . $host, DNS_TXT);
    foreach ($dmarcRecords ?: [] as $rec) {
        if (isset($rec['txt']) && str_starts_with($rec['txt'], 'v=DMARC1')) {
            $dmarc = $rec['txt'];
            break;
        }
    }
    $checks['dmarc_record'] = $dmarc;
    $checks['dmarc_ok'] = $dmarc !== null;

	// DKIM Record
    $dkim = null;
    $dkimRecords = dns_get_record('default._domainkey.' . $host, DNS_TXT);
    foreach ($dkimRecords ?: [] as $rec) {
        if (isset($rec['txt']) && str_starts_with($rec['txt'], 'v=DKIM1')) {
            $dkim = $rec['txt'];
            break;
        }
    }
    $checks['dkim_record'] = $dkim;
    $checks['dkim_ok'] = $dkim !== null;

    // Test primary MX host for email ports
    $testHost = !empty($mxRecords) ? $mxRecords[0]['host'] : $host;
    $checks['tested_mail_host'] = $testHost;

    $emailPorts = [
        ['port' => 25,  'label' => 'SMTP',             'tls' => 'none',     'proto' => 'smtp'],
        ['port' => 465, 'label' => 'SMTPS (SSL/TLS)',   'tls' => 'implicit', 'proto' => 'smtp'],
        ['port' => 587, 'label' => 'SMTP Submission',   'tls' => 'starttls', 'proto' => 'smtp'],
        ['port' => 143, 'label' => 'IMAP',              'tls' => 'starttls', 'proto' => 'imap'],
        ['port' => 993, 'label' => 'IMAPS (SSL/TLS)',   'tls' => 'implicit', 'proto' => 'imap'],
        ['port' => 110, 'label' => 'POP3',              'tls' => 'starttls', 'proto' => 'pop3'],
        ['port' => 995, 'label' => 'POP3S (SSL/TLS)',   'tls' => 'implicit', 'proto' => 'pop3'],
    ];

    $portResults = [];
    foreach ($emailPorts as $ep) {
        $result = checkEmailPort($testHost, $ep['port'], $ep['tls'], $ep['proto']);
        $result['label'] = $ep['label'];
        $result['recommended'] = in_array($ep['port'], [465, 587, 993, 995]);
        $portResults[] = $result;
    }
    $checks['ports'] = $portResults;

    sendJson(['ok' => true, 'target' => $host, 'checks' => $checks]);
}

function checkEmailPort($host, $port, $tlsMode, $proto) {
    $timeout = 5;
    $result = ['port' => $port, 'open' => false, 'tls' => $tlsMode, 'details' => []];

    if ($tlsMode === 'implicit') {
        // SSL/TLS direct
        $ctx = stream_context_create(['ssl' => [
            'verify_peer'       => false,
            'verify_peer_name'  => false,
            'capture_peer_cert' => true,
        ]]);
        $sock = @stream_socket_client(
            "ssl://$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx
        );
    } else {
        $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    if (!$sock) {
        $result['error'] = $errstr ?: 'Connection refused';
        return $result;
    }

    $result['open'] = true;
    stream_set_timeout($sock, 4);

    // Read banner
    $banner = fgets($sock, 512);
    $result['details']['banner'] = trim($banner ?: '');

    if ($tlsMode === 'starttls') {
        // Send EHLO, then STARTTLS
        if ($proto === 'smtp') {
            fwrite($sock, "EHLO netprobe.local\r\n");
            $ehlo = '';
            while ($line = fgets($sock, 512)) {
                $ehlo .= $line;
                if (preg_match('/^\d{3} /', $line)) break;
            }
            $result['details']['ehlo_caps'] = trim($ehlo);
            $starttlsSupported = stripos($ehlo, 'STARTTLS') !== false;
            $result['details']['starttls_advertised'] = $starttlsSupported;

            if ($starttlsSupported) {
                fwrite($sock, "STARTTLS\r\n");
                $stls = fgets($sock, 256);
                $result['details']['starttls_response'] = trim($stls);
                if (str_starts_with(trim($stls), '220')) {
                    $result['details']['starttls_ok'] = true;
                    // Upgrade to TLS
                    stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    $result['details']['tls_upgrade'] = 'success';
                }
            }
        } elseif ($proto === 'imap') {
            // Check for STARTTLS capability
            fwrite($sock, "A001 CAPABILITY\r\n");
            $cap = '';
            while ($line = fgets($sock, 512)) {
                $cap .= $line;
                if (str_contains($line, 'A001 OK') || str_contains($line, 'A001 BAD')) break;
            }
            $result['details']['capabilities'] = trim($cap);
            $result['details']['starttls_advertised'] = stripos($cap, 'STARTTLS') !== false;
        } elseif ($proto === 'pop3') {
            fwrite($sock, "CAPA\r\n");
            $capa = '';
            while ($line = fgets($sock, 256)) {
                $capa .= $line;
                if (trim($line) === '.') break;
            }
            $result['details']['capabilities'] = trim($capa);
            $result['details']['starttls_advertised'] = stripos($capa, 'STLS') !== false;
        }
    } elseif ($tlsMode === 'implicit') {
        // Check cert
        $params = stream_context_get_params($sock);
        if (!empty($params['options']['ssl']['peer_certificate'])) {
            $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
            if ($cert) {
                $result['details']['cert_subject'] = $cert['subject']['CN'] ?? 'unknown';
                $result['details']['cert_issuer']  = $cert['issuer']['O'] ?? 'unknown';
                $result['details']['cert_valid_to'] = date('Y-m-d', $cert['validTo_time_t']);
                $result['details']['cert_expired']  = $cert['validTo_time_t'] < time();
            }
        }
    } elseif ($tlsMode === 'none' && $proto === 'smtp') {
        fwrite($sock, "EHLO netprobe.local\r\n");
        $ehlo = '';
        while ($line = fgets($sock, 512)) {
            $ehlo .= $line;
            if (preg_match('/^\d{3} /', $line)) break;
        }
        $result['details']['ehlo_caps'] = trim($ehlo);
    }

    // Quit gracefully
    if ($proto === 'smtp')      fwrite($sock, "QUIT\r\n");
    elseif ($proto === 'imap')  fwrite($sock, "A002 LOGOUT\r\n");
    elseif ($proto === 'pop3')  fwrite($sock, "QUIT\r\n");

    fclose($sock);
    return $result;
}
