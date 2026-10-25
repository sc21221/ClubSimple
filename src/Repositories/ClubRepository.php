<?php

declare(strict_types=1);

namespace Clubmanager\Repositories;

use Clubmanager\Models\Club;
use Clubmanager\Services\AuditService;

/**
 * Club Master Data Repository
 *
 * @package Clubmanager\Repositories
 * @author  Reinhard Schiessl
 */
class ClubRepository
{
    /**
     * Get the primary club record or instantiate default if none exists.
     *
     * @return Club
     */
    public function getPrimary(): Club
    {
        $club = Club::find(1);
        if (!$club) {
            $clubs = Club::all();
            if (!empty($clubs)) {
                return $clubs[0];
            }

            // Create default instance for Musikverein Denkendorf
            $club = new Club([
                'name' => 'Musikverein Denkendorf e.V.',
                'name2' => 'Blasmusik & Jugendarbeit',
                'boss' => 'Reinhard Schiessl',
                'street' => 'Hauptstraße 1',
                'zip' => '73770',
                'city' => 'Denkendorf',
                'country' => 'Deutschland',
                'email' => 'vorstand@musikverein-denkendorf.de',
                'phone' => '+49 711 345678',
                'bank_name' => 'Kreissparkasse Esslingen-Nürtingen',
                'bank_creditor_id' => 'DE98ZZZ09999999999',
                'bank_bic' => 'ESSLDE66XXX',
                'bank_iban' => 'DE02611500200000123456',
            ]);
            $club->save();
        }
        return $club;
    }

    /**
     * Update club master data with audit logging.
     *
     * @param Club $club
     * @param array<string, mixed> $data
     * @param int|null $userId
     * @return bool
     */
    public function update(Club $club, array $data, ?int $userId = null): bool
    {
        $old = $club->toArray();
        $club->fill($data);
        $saved = $club->save();

        if ($saved) {
            AuditService::log(
                'CLUB_UPDATED',
                'clubs',
                (int) $club->Id,
                $old,
                $club->toArray(),
                $userId
            );
        }

        return $saved;
    }
}
