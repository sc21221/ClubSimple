<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * Club Master Data Model
 *
 * Represents the "clubs" table containing general club details,
 * official board representative, and SEPA creditor configuration.
 *
 * @package Clubmanager\Models
 * @author  Reinhard Schiessl
 *
 * @property int $Id
 * @property string $name
 * @property string|null $name2
 * @property string|null $boss
 * @property string|null $street
 * @property string|null $zip
 * @property string|null $city
 * @property string|null $country
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $bank_name
 * @property string $bank_creditor_id
 * @property string $bank_bic
 * @property string $bank_iban
 * @property string $created_at
 * @property string|null $updated_by
 * @property string $updated_at
 */
class Club extends Model
{
    protected static string $table = 'clubs';
    protected static string $primaryKey = 'Id';

    /**
     * Provide seamless alias for lower-case id.
     *
     * @param string $key
     * @return mixed
     */
    public function __get(string $key): mixed
    {
        if ($key === 'id') {
            return $this->attributes['Id'] ?? null;
        }
        return parent::__get($key);
    }
}
