<?php


namespace App\Http\Controllers\Api\ClientLite;


use App\Transformers\ClientLite\EventTransformer;
use Formatter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tobuli\Services\FractalTransformerService;

class EventsController extends Controller
{
    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    protected function afterAuth($user)
    {
        $this->checkException('events', 'view');
    }

    public function index(Request $request)
    {
        $query = $this->user->events()
            ->filter($request->all())
            ->search($request->get('search'))
            ->orderBy('events.id', 'desc');

        if ( ! empty($data['date_from']))
            $query->where('events.time', '>=', Formatter::time()->reverse($data['date_from']));

        if ( ! empty($data['date_to']))
            $query->where('events.time', '<=', Formatter::time()->reverse($data['date_to']));

        if ( ! empty($data['created_from']))
            $query->where('events.created_at', '>=', Formatter::time()->reverse($data['created_from']));

        if ( ! empty($data['created_to']))
            $query->where('events.created_at', '<=', Formatter::time()->reverse($data['created_to']));

        $events = $query->paginate(20);

        return response()->json(
            $this->transformerService->paginate($events, EventTransformer::class)->toArray()
        );
    }


}