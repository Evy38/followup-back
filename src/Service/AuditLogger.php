<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Enregistre les actions sensibles dans le canal de log "audit".
 *
 * Chaque entrée contient : action, email de l'utilisateur concerné, IP source, horodatage.
 * En dev → var/log/audit.log
 * En prod → stderr (JSON, visible dans les logs Render)
 */
class AuditLogger
{
    public function __construct(
        private readonly LoggerInterface $auditLogger,
        private readonly RequestStack $requestStack
    ) {
    }

    public function log(string $action, string $email, array $context = []): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $this->auditLogger->info($action, array_merge([
            'email' => $email,
            'ip'    => $request?->getClientIp() ?? 'cli',
        ], $context));
    }
}
