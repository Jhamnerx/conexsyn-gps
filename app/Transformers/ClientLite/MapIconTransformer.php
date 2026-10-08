<?php


namespace App\Transformers\ClientLite;


use Tobuli\Entities\MapIcon;

class MapIconTransformer extends BaseTransformer
{
    /**
     * @param MapIcon $entity
     * @return array|null
     */
    public function transform($entity)
    {
        if (!$entity) {
            return null;
        }

        return [
            'id' => (int)$entity->id,
            'width' => (int)$entity->width,
            'height' => (int)$entity->height,
            'url' => (string)$entity->url,
        ];
    }
}
