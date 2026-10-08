<?php


namespace App\Http\Controllers\Api\ClientLite;

use App\Transformers\ClientLite\RoutesTransformer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Tobuli\Entities\Route;
use Tobuli\Services\FractalTransformerService;

class RoutesController extends Controller
{
    protected $transformerService;

    public function __construct(FractalTransformerService $transformerService)
    {
        parent::__construct();

        $this->transformerService = $transformerService;
    }

    protected function afterAuth($user)
    {
        $this->checkException('routes', 'view');
    }

    public function map(Request $request)
    {
        $routes = Route::userOwned($this->user)
            ->visible()
            ->clearOrdersBy()
            ->cursorPaginate(100);

        return response()->json(
            $this->transformerService->cursorPaginate($routes, RoutesTransformer::class)->toArray()
        );
    }
}