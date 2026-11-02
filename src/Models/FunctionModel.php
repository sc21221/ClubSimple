<?php

declare(strict_types=1);

namespace Clubmanager\Models;

use Clubmanager\Core\Model;

/**
 * @property int $id
 * @property string $name
 */
class FunctionModel extends Model
{
    protected static string $table = 'club_functions';
    protected static string $primaryKey = 'id';
}