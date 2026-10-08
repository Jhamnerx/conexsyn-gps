<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Tobuli\Entities\Tag;
use Tobuli\Services\TagService;

class TagsController extends Controller
{
    private TagService $tagService;

    protected function afterAuth($user)
    {
        $this->tagService = new TagService($user);
    }

    public function index()
    {
        return $this->getList('index');
    }

    public function table()
    {
        return $this->getList('table');
    }

    private function getList(string $view)
    {
        $this->checkException('tags', 'view');

        $items = Tag::userAccessible($this->user)
            ->with('user')
            ->search(request('search_phrase'))
            ->toPaginator(
                request('limit', 25),
                request('sorting.sort_by', 'name'),
                request('sorting.sort', 'desc')
            );

        $colorOptions = TagService::OPTIONS_COLORS;

        return View::make("Admin.Tags.$view")->with(compact('items', 'colorOptions'));
    }

    public function create()
    {
        $this->checkException('tags', 'create');

        return $this->getForm(new Tag(), 'Admin.Tags.create');
    }

    public function edit(int $id = null)
    {
        $item = Tag::find($id);

        $this->checkException('tags', 'edit', $item);

        return $this->getForm($item, 'Admin.Tags.edit');
    }

    public function store()
    {
        $this->checkException('tags', 'create');

        $this->tagService->create($this->data);

        return ['status' => 1];
    }

    public function update(int $id = null)
    {
        $item = Tag::find($this->data['id']);

        $this->checkException('tags', 'edit', $item);

        $this->tagService->update($item, $this->data);

        return ['status' => 1];
    }

    public function destroy(Request $request)
    {
        $tags = Tag::whereIn('id', $request->get('id') ?: [])->get();

        foreach ($tags as $tag) {
            if ($this->user->can('destroy', $tag)) {
                $this->tagService->delete($tag);
            }
        }

        return ['status' => 1];
    }

    private function getForm(Tag $item, string $view)
    {
        $colorOptions = TagService::OPTIONS_COLORS;

        return View::make($view)->with(compact(
            'item',
            'colorOptions',
        ));
    }

}