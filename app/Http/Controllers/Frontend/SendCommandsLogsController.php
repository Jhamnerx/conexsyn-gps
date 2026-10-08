<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Support\Arr;
use Tobuli\Entities\SentCommand;
use Tobuli\Services\Commands\SendCommandService;

class SendCommandsLogsController extends Controller
{
    public function index()
    {
        $connections = [
            SendCommandService::CONNECTION_GPRS => trans('front.gprs'),
            SendCommandService::CONNECTION_SMS => trans('front.sms'),
        ];

        return $this->getList('index')->with('connections', $connections);
    }

    public function table()
    {
        return $this->getList('table');
    }

    private function getList(string $view)
    {
        $this->checkException('send_command', 'view');

        $sort = $this->data['sorting'] ?? [];
        $sortCol = $sort['sort_by'] ?? 'created_at';
        $sortDir = $sort['sort'] ?? 'DESC';

        $showUser = $this->user->isAdmin() || $this->user->isManager();

        $query = SentCommand::userAccessible($this->user)
            ->with('device')
            ->search($this->data['search_phrase'] ?? null)
            ->filter($this->data);

        if ($showUser) {
            $query->with('user');
        }

        $items = $query->toPaginator(15, $sortCol, $sortDir);

        return view("Frontend.SendCommand.$view")->with(compact('items', 'showUser'));
    }
}
