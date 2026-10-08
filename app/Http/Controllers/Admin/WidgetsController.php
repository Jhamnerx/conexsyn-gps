<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Validation\Rule;

class WidgetsController extends BaseController
{
    public function index()
    {
        return view('admin::Widgets.index')->with([
            'widgets' => settings('widgets'),
            'widgets_list' => config('lists.widgets'),
        ]);
    }

    public function store()
    {
        $input = $this->validate(request(), [
            'default' => 'required|boolean',
            'status' => 'boolean',
            'list' => 'array',
            'list.*' => Rule::in(array_keys(config('lists.widgets'))),
        ]);

        $settings = array_merge(settings('widgets'), $input);

        settings('widgets', $settings);

        return redirect()->back()->withSuccess(trans('front.successfully_saved'));
    }
}
