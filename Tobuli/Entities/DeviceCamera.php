<?php namespace Tobuli\Entities;

use App\Jobs\DeviceCameraCreate;
use App\Jobs\DeviceCameraDelete;
use Auth;
use Tobuli\Traits\DisplayTrait;

class DeviceCamera extends AbstractEntity implements DisplayInterface
{
    use DisplayTrait;

    public static string $displayField = 'name';

    protected $table = 'device_cameras';

    protected $fillable = [
        'device_id',
        'name',
        'show_widget',
        'ftp_username',
        'ftp_password',
    ];

    public $timestamps = true;

    protected static function boot() {
        parent::boot();

        static::created(function ($camera) {
            dispatch(new DeviceCameraCreate($camera, Auth::user()));
        });

        static::updated(function ($camera) {
            //@TODO: dispatch update job
        });

        static::deleted(function (DeviceCamera $camera) {
            dispatch(new DeviceCameraDelete($camera->ftp_username));
        });
      }

    public function device() {
        return $this->belongsTo('Tobuli\Entities\Device', 'device_id', 'id');
    }

    public function scopeShowWidget($query)
    {
        return $query->where('show_widget', 1);
    }
}
