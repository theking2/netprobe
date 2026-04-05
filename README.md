# NetProbe — Network Diagnostics Web App

A self-hosted PHP network diagnostics tool with three modules:
1. **Speed Test** — DNS resolution, TCP connect time, TTFB, total load time, throughput, ICMP ping
2. **Port Scanner** — Scan common or custom ports on any host/IP
3. **Email Checker** — MX/SPF/DMARC DNS records + live SMTP/IMAP/POP3 port tests with TLS/STARTTLS detection

## Requirements

- PHP 7.4+ (PHP 8.x recommended)
- Extensions: `curl`, `openssl`, `sockets` (all standard)
- The `exec()` function available for ICMP ping (optional; gracefully disabled if blocked)
- Outbound TCP on all tested ports (may be restricted on shared hosting)

## Files

```
netprobe/
├── index.php   ← Frontend (single-page UI)
└── api.php     ← Backend API (all tests run here)
```

## Installation

1. Upload both files to any PHP-capable web directory
2. Visit `index.php` in your browser

No database, no config files, no dependencies.

## Notes

- **Port scanning** uses `fsockopen()` with a 1.5s timeout. On shared hosts, outbound connections to
  non-HTTP ports may be blocked by the provider's firewall.
- **ICMP ping** uses `exec("ping ...")` which requires the `exec()` function to be enabled and the
  binary to be accessible. The app shows a notice if ping is unavailable.
- **Email TLS checks** attempt real SMTP/IMAP/POP3 handshakes including STARTTLS negotiation and
  SSL certificate inspection.
- The app does **not** log or store any user input or results.

## Security

- Input is sanitized to hostname/IP characters only
- Port scan is limited to 50 ports maximum per request
- All connections use short timeouts to prevent resource exhaustion
- Consider adding HTTP Basic Auth if deploying publicly (e.g. `.htpasswd`)
