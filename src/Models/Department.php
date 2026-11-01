<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * @property int $id
 * @property string $name
 */
class Department extends Model
{
    protected static string $table = 'departments';
    protected static string $primaryKey = 'id';
}