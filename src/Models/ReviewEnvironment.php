<?php

declare(strict_types=1);

namespace Ddr\ForgeTestBranches\Models;

use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $branch
 * @property string $slug
 * @property string $domain
 * @property int $server_id
 * @property int $site_id
 * @property int $database_id
 * @property int $database_user_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ReviewEnvironment extends Model
{
    protected $fillable = [
        'branch',
        'slug',
        'domain',
        'server_id',
        'site_id',
        'database_id',
        'database_user_id',
    ];

    protected function casts(): array
    {
        return [
            'server_id' => 'integer',
            'site_id' => 'integer',
            'database_id' => 'integer',
            'database_user_id' => 'integer',
        ];
    }
}
