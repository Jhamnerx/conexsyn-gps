<?php

namespace Tobuli\Entities;




class SendQueue extends AbstractEntity
{
    public const SENDER_SYSTEM = 'system';

    protected $table = 'send_queue';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'sender',
        'data',
        'data_type',
        'channel',
        'channel_data'
    ];

    protected $casts = [
        'data'     => 'object',
        'channel_data' => 'array'
    ];

    protected $appends = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function target()
    {
        return $this->morphTo();
    }

    public function setDataAttribute($value)
    {
        if ($class = get_class($value))
            $this->data_type = $class;

        if ($value instanceof \Eloquent)
            $value = $value->toArray();

        unset($value['geofence']);

        $this->attributes['data'] = json_encode($value);
    }

    public function getDataAttribute($value)
    {
        $data = json_decode($value, true);

        if ( ! $data)
            return null;

        if (!$this->data_type) {
            return $data;
        }

        $item = new $this->data_type();

        foreach ($data as $key => $value) {
            $item->$key = $value;
        }

        return $item;
    }

    public function toArrayMassInsert()
    {
        return array_intersect_key(
            $this->getAttributes(),
            array_flip($this->getFillable())
        );
    }
}
