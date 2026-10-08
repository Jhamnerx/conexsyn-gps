<?php

namespace App\Http\Controllers\Api\Frontend;

use App\Transformers\Tag\TagListTransformer;
use Tobuli\Entities\Tag;

class TagsController extends BaseController
{
    public function index()
    {
        $this->checkException('tags', 'view');

        $items = Tag::userAccessible($this->user)
            ->orderBy($this->data['sort_col'] ?? 'name', $this->data['sort_dir'] ?? 'ASC')
            ->paginate($this->data['limit'] ?? null);

        return response()->json(array_merge(
            ['status' => 1],
            \FractalTransformer::paginate($items, TagListTransformer::class)->toArray()
        ));
    }
}
