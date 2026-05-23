<?php
namespace App\Modules\AI\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class AIQuery extends Model
{
    use HasUuid, BelongsToBusiness;

    protected $table = 'ai_queries';

    protected $fillable = [
        'business_id', 'user_id', 'query_type', 'prompt',
        'response', 'tokens_used', 'cost', 'model', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(\App\Modules\Auth\Models\User::class);
    }
}
