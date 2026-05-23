<?php
namespace App\Modules\Subscription\Models;

use App\Shared\Traits\HasUuid;
use App\Shared\Traits\BelongsToBusiness;
use Illuminate\Database\Eloquent\Model;

class SubscriptionLog extends Model
{
    use HasUuid, BelongsToBusiness;
    protected $fillable = ['business_id','subscription_id','action','from_plan_id','to_plan_id','note','performed_by'];
    public function subscription() { return $this->belongsTo(Subscription::class); }
    public function performer() { return $this->belongsTo(\App\Modules\Auth\Models\User::class, 'performed_by'); }
}
