/**
 * Cloudflare Worker: connectivity-check
 *
 * Riceve richieste dal binary unit3d-announce per verificare se un peer
 * e' raggiungibile (porta TCP aperta). Il check viene eseguito dagli edge
 * node di Cloudflare, non dal server Hetzner, cosi' VPN provider che bloccano
 * connessioni in ingresso da datacenter AS24940 vengono bypassed.
 *
 * Endpoint: GET /?ip=<ip>&port=<port>&key=<secret>
 * Risposta: { "connectable": true|false }
 *
 * Segreto configurato tramite: wrangler secret put CONNECTIVITY_CHECK_KEY
 */

import { connect } from 'cloudflare:sockets';

/**
 * Controlla se un indirizzo IPv4 e' privato/riservato.
 * Blocca tentativi di usare il worker per portscanning di reti interne.
 */
function isPrivateIPv4(ip) {
    const parts = ip.split('.').map(Number);
    if (parts.length !== 4 || parts.some(p => isNaN(p) || p < 0 || p > 255)) {
        return true;
    }
    const [a, b] = parts;
    return (
        a === 10 ||
        (a === 172 && b >= 16 && b <= 31) ||
        (a === 192 && b === 168) ||
        a === 127 ||
        (a === 169 && b === 254) ||
        a === 0 ||
        a >= 224
    );
}

/**
 * Controlla se un indirizzo IPv6 e' privato/riservato.
 */
function isPrivateIPv6(ip) {
    const lower = ip.toLowerCase();
    return (
        lower === '::1' ||
        lower.startsWith('fc') ||
        lower.startsWith('fd') ||
        lower.startsWith('fe80:') ||
        lower.startsWith('::ffff:')
    );
}

function isIPv4(ip) {
    return /^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/.test(ip);
}

function isPrivateIP(ip) {
    return isIPv4(ip) ? isPrivateIPv4(ip) : isPrivateIPv6(ip);
}

export default {
    async fetch(request, env) {
        const url = new URL(request.url);
        const ip = url.searchParams.get('ip');
        const portStr = url.searchParams.get('port');
        const key = url.searchParams.get('key');

        // Autenticazione
        if (!env.CONNECTIVITY_CHECK_KEY || !key || key !== env.CONNECTIVITY_CHECK_KEY) {
            return new Response('Unauthorized', { status: 401 });
        }

        // Validazione parametri
        if (!ip || !portStr) {
            return new Response('Bad Request: missing ip or port', { status: 400 });
        }

        const port = parseInt(portStr, 10);
        if (isNaN(port) || port < 1024 || port > 65535) {
            return new Response('Bad Request: invalid port (must be 1024-65535)', { status: 400 });
        }

        // Blocca IP privati per prevenire SSRF
        if (isPrivateIP(ip)) {
            return Response.json({ connectable: false });
        }

        let connectable = false;
        let socket;
        try {
            socket = connect({ hostname: ip, port });
            await Promise.race([
                socket.opened,
                new Promise((_, reject) =>
                    setTimeout(() => reject(new Error('timeout')), 2000)
                ),
            ]);
            connectable = true;
        } catch (_) {
            connectable = false;
        } finally {
            // Non attendere socket.close(): se il TCP non ha ancora risposto
            // (SYN_SENT), await close() si blocca indefinitamente.
            // Il Worker runtime abbandona l'I/O pendente quando la Response viene
            // restituita, quindi e' sicuro non attendere.
            if (socket) {
                socket.close().catch(() => {});
            }
        }

        return Response.json({ connectable });
    },
};
