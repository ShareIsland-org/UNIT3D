<?php

declare(strict_types=1);

/**
 * NOTICE OF LICENSE.
 *
 * UNIT3D Community Edition is open-sourced software licensed under the GNU Affero General Public License v3.0
 * The details is bundled with this project in the file LICENSE.txt.
 *
 * @project    UNIT3D Community Edition
 *
 * @author     HDVinnie <hdinnovations@protonmail.com>
 * @license    https://www.gnu.org/licenses/agpl-3.0.en.html/ GNU Affero General Public License v3.0
 */

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Symfony\Component\HttpFoundation\Request as RequestAlias;

class TrustProxies extends Middleware
{
    /**
     * The trusted proxies for this application.
     *
     * NOTA: questo middleware e' deliberatamente disabilitato in Kernel.php.
     *
     * Il setup attuale e' Cloudflare -> nginx -> PHP-FPM (FastCGI).
     * Nginx risolve l'IP reale del client tramite il modulo ngx_http_realip_module
     * (real_ip_header CF-Connecting-IP, set_real_ip_from [IP Cloudflare]) e passa
     * il valore corretto a PHP come REMOTE_ADDR via fastcgi_param.
     * Laravel riceve quindi il vero IP del client in REMOTE_ADDR senza bisogno
     * di leggere X-Forwarded-For.
     *
     * Abilitare TrustProxies con $proxies = '*' sarebbe un rischio di sicurezza:
     * qualunque client potrebbe falsificare il proprio IP tramite X-Forwarded-For.
     * Se in futuro si volesse abilitare, usare gli IP specifici di Cloudflare
     * anziche' il wildcard '*'.
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * The headers that should be used to detect proxies.
     *
     * @var int
     */
    protected $headers = RequestAlias::HEADER_X_FORWARDED_FOR | RequestAlias::HEADER_X_FORWARDED_HOST | RequestAlias::HEADER_X_FORWARDED_PORT | RequestAlias::HEADER_X_FORWARDED_PROTO | RequestAlias::HEADER_X_FORWARDED_AWS_ELB;
}
