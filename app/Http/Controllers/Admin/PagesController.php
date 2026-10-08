<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;
use Illuminate\Validation\Rule;
use Tobuli\Entities\Page;

class PagesController extends Controller
{
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
        $items = Page::search(request('search_phrase'))->toPaginator(
            request('limit', 25),
            request('sorting.sort_by', 'title'),
            request('sorting.sort', 'ASC')
        );

        return View::make('Admin.Pages.' . $view)->with(compact('items'));
    }

    public function create()
    {
        return View::make('Admin.Pages.create')->with('item', new Page());
    }

    public function edit(int $id = null)
    {
        return View::make('Admin.Pages.edit')->with('item', Page::findOrFail($id));
    }

    public function store()
    {
        $data = $this->validateForm();

        Page::create($data);

        return ['status' => 1];
    }

    public function update(int $id)
    {
        $page = Page::findOrFail($id);

        $data = $this->validateForm($page);

        $page->update($data);

        return ['status' => 1];
    }

    public function destroy()
    {
        Page::whereIn('id', request('id') ?? [])->delete();

        return ['status' => 1];
    }

    private function validateForm(Page $item = null): array
    {
        $ruleUq = Rule::unique(Page::class);

        if ($item) {
            $ruleUq->ignore($item);
        }

        return request()->validate([
            'title' => ['required', $ruleUq],
            'slug' => ['required', 'alpha_dash', $ruleUq],
            'content' => 'required',
        ]);
    }
}