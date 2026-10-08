<?php

namespace Tobuli\Services;

use Illuminate\Database\Eloquent\Relations\HasMany;

class GroupModelService
{
    private HasMany $query;

    public function __construct(HasMany $query)
    {
        $this->query = $query;
    }

    /**
     * @param false|int|null|array $id
     * @param false|int|null|array $groupId
     * @param int|string|bool $active
     * @return int
     */
    public function changeActive($id, $groupId, $active = 0): int
    {
        return (clone $this->query)
            ->when($groupId !== false, function ($query) use ($groupId) {
                if ($groupId) {
                    $groupId = is_array($groupId) ? $groupId : [$groupId];
                    $query->whereIn('group_id', $groupId);
                } else {
                    $query->whereNull('group_id');
                }
            })
            ->when($id !== false, function ($query) use ($id) {
                if ($id) {
                    $id = is_array($id) ? $id : [$id];
                    $query->whereIn('id', $id);
                }
            })
            ->update([
                'active' => filter_var($active, FILTER_VALIDATE_BOOLEAN)
            ]);
    }
}