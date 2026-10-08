<?php

namespace Tobuli\Entities;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TrackerPort extends AbstractEntity
{
    protected $table = 'tracker_ports';

    protected $fillable = ['active', 'port', 'name', 'parent', 'extra'];

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function parentPort(): HasOne
    {
        return $this->hasOne(TrackerPort::class, 'name', 'parent');
    }

    public function childPorts(): HasMany
    {
        return $this->hasMany(TrackerPort::class, 'parent', 'name');
    }

    public function getDisplayAttribute(): string
    {
        $user = getActingUser();

        $canViewProtocol = $user && $user->perm('device.protocol', 'view');

        return $this->port . ($canViewProtocol ? " / $this->name" : "");
    }

    public function getChildNameAttribute(): string
    {
        if (!$this->parent) {
            return '';
        }

        $prefixLength = strlen($this->parent . '-');

        return substr($this->name, $prefixLength);
    }
}
