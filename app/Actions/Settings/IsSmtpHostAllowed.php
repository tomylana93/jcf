<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Decides whether mail delivery may connect to an SMTP host.
 *
 * In production the host must not point at the server itself, link-local addresses or cloud metadata endpoints,
 * so the mail settings cannot be used to reach internal-only services. Private networks stay allowed for SMTP relays.
 */
final readonly class IsSmtpHostAllowed
{
    /** @var list<string> */
    private const array BLOCKED_RANGES = ['0.0.0.0/8', '127.0.0.0/8', '169.254.0.0/16', '::/128', '::1/128', 'fe80::/10', 'fd00:ec2::254/128'];

    public function handle(string $host): bool
    {
        if (! app()->isProduction()) {
            return true;
        }

        $host = mb_strtolower(mb_trim($host, " \t\n\r\0\x0B[]"));

        if ($host === 'localhost' || str_ends_with($host, '.localhost')) {
            return false;
        }

        $addresses = filter_var($host, FILTER_VALIDATE_IP) !== false ? [$host] : $this->resolve($host);

        return array_all($addresses, fn (string $address): bool => ! IpUtils::checkIp($address, self::BLOCKED_RANGES));
    }

    /** @return list<string> */
    private function resolve(string $host): array
    {
        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        return array_values(array_filter(array_map(
            fn (array $record): ?string => $record['ip'] ?? $record['ipv6'] ?? null,
            $records,
        )));
    }
}
