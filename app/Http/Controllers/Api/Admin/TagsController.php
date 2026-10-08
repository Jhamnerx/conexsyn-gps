<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Frontend\BaseController;
use App\Transformers\Tag\TagFullTransformer;
use App\Transformers\Tag\TagListTransformer;
use Tobuli\Entities\Tag;
use Tobuli\Services\TagService;

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

    public function show(int $id)
    {
        $item = Tag::find($id);

        $this->checkException('tags', 'show', $item);

        return response()->json(
            \FractalTransformer::item($item, TagFullTransformer::class)->toArray()
        );
    }

    public function formData()
    {
        $this->checkException('tags', 'store');

        return response()->json([
            'color' => TagService::OPTIONS_COLORS,
        ]);
    }

    public function store()
    {
        $this->checkException('tags', 'create');

        $item = (new TagService($this->user))->create($this->data);

        return response()->json(array_merge(
            ['status' => 1],
            \FractalTransformer::item($item, TagFullTransformer::class)->toArray()
        ));
    }

    public function update(int $id)
    {
        $item = Tag::find($id);

        $this->checkException('tags', 'update', $item);

        (new TagService($this->user))->update($item, $this->data);

        return response()->json(array_merge(
            ['status' => 1],
            \FractalTransformer::item($item, TagFullTransformer::class)->toArray()
        ));
    }

    public function destroy(int $id)
    {
        $item = Tag::find($id);

        $this->checkException('tags', 'remove', $item);

        (new TagService($this->user))->delete($item);

        return response()->json(['status' => 1]);
    }
}
