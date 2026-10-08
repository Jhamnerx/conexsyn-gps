<?php


namespace App\Http\Controllers\Api\ClientLite;

use App\Transformers\ClientLite\PoiTransformer;
use App\Transformers\ClientLite\RoutesTransformer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tobuli\Entities\Poi;
use Tobuli\Entities\Route;
use Tobuli\Services\FractalTransformerService;

class PoisController extends Controller
{
    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    protected function afterAuth($user)
    {
        $this->checkException('poi', 'view');
    }

    public function map(Request $request)
    {
        $pois = Poi::userOwned($this->user)
            ->visible()
            ->clearOrdersBy()
            ->cursorPaginate(100);

        return response()->json(
            $this->transformerService->cursorPaginate($pois, PoiTransformer::class)->toArray()
        );
    }
}