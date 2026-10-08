<?php

namespace Tobuli\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tobuli\Entities\Tag;
use Tobuli\Entities\User;

class TagService
{
    public const OPTIONS_COLORS = [
        '#6b7280' => 'Gray',
        '#f43f5e' => 'Red',
        '#f97316' => 'Orange',
        '#6366f1' => 'Purple',
        '#10b981' => 'Green',
        '#0ea5e9' => 'Blue',
        '#ec4899' => 'Pink',
        '#84cc16' => 'Lime',
    ];

    public function __construct(
        private ?User $user = null,
    ) {}

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(array $data): Tag
    {
        $data['is_common'] = $this->user->isAdmin();

        Validator::validate($data, [
            'name'      => ['required', Rule::unique('tags', 'name')],
            'is_common' => ['required', 'boolean'],
            'color'     => ['required', Rule::in(array_keys(self::OPTIONS_COLORS))],
        ]);

        $tag = Tag::make($data);
        $tag->user_id = $this->user->id;
        $tag->save();

        return $tag;
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function update(Tag $tag, array $data): void
    {
        Validator::validate($data, [
            'name'      => [Rule::unique('tags', 'name')->ignore($tag)],
            'is_common' => ['boolean'],
            'color'     => [Rule::in(array_keys(self::OPTIONS_COLORS))],
        ]);

        $tag->update($data);
    }

    public function delete(Tag $tag): void
    {
        $tag->devices()->detach();
        $tag->delete();
    }

    public function setToModel(Model $model, array $ids): void
    {
        $ids = Tag::userAccessible($this->user)->whereIn('id', $ids)->pluck('id');

        $model->tags()->sync($ids);
    }

    public function attachToModel(Model $model, array $ids): void
    {
        $ids = Tag::userAccessible($this->user)->whereIn('id', $ids)->pluck('id');

        $model->tags()->syncWithoutDetaching($ids);
    }

    public function detachFromModel(Model $model, array $ids): void
    {
        $ids = Tag::userAccessible($this->user)->whereIn('id', $ids)->pluck('id');

        $model->tags()->detach($ids);
    }
}