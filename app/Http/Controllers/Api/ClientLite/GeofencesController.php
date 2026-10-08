<?php


namespace App\Http\Controllers\Api\ClientLite;


use App\Transformers\ClientLite\GeofenceTransformer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tobuli\Entities\Geofence;
use Tobuli\Services\FractalTransformerService;

class GeofencesController extends Controller
{
    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    protected function afterAuth($user)
    {
        $this->checkException('geofences', 'view');
    }

    public function map(Request $request)
    {
        $geofences = Geofence::userOwned($this->user)
            ->visible()
            ->clearOrdersBy()
            ->cursorPaginate(100);

        return response()->json(
            $this->transformerService->cursorPaginate($geofences, GeofenceTransformer::class)->toArray()
        );
    }
}