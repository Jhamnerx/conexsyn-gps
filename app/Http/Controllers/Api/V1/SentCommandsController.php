<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Transformers\ApiV1\SentCommandTransformer;
use Tobuli\Entities\SentCommand;

class SentCommandsController extends Controller
{
    public function index()
    {
        $this->checkException('send_command', 'view');

        $sort = $this->data['sorting'] ?? [];
        $sortCol = $sort['sort_by'] ?? 'created_at';
        $sortDir = $sort['sort'] ?? 'DESC';

        $query = SentCommand::userAccessible($this->user)
            ->search($this->data['search'] ?? null)
            ->filter($this->data);

        $items = $query->toPaginator(15, $sortCol, $sortDir);

        return response()->json(
            \FractalTransformer::paginate($items, SentCommandTransformer::class)->toArray()
        );
    }
}