<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * @property int $id
 * @property string $name
 */
class Event extends Model
{
    protected static string $table = 'events';
    protected static string $primaryKey = 'id';
}