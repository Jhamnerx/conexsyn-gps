<?php namespace App\Http\Controllers\Frontend;

use App\Console\PositionsStack;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;

class PositionsController extends Controller {
    public function insert() {
        $input = Request::all();

        $error = null;
        $required = ['uniqueId' => '', 'fixTime' => '', 'latitude' => '', 'longitude' => '', 'speed' => '', 'altitude' => '', 'course' => '', 'protocol' => ''];

        foreach ($required as $field => $value) {
            if (!isset($input[$field]))
                $error .= $field.', ';
        }

        if (!is_null($error))
            return Response::make(json_encode(['status' => 0, 'message' => 'Missing params: '.substr($error, 0, -2)]), 400);

        $data = [
            'fixTime'    => strtotime($input['date']) * 1000,
            'valid'      => $input['valid'],
            'imei'       => $input['uniqueId'],
            'latitude'   => $input['latitude'],
            'longitude'  => $input['longitude'],
            'attributes' => empty($input['attributes']) ? [] : $input['attributes'],
            'speed'      => $input['speed'],
            'altitude'   => $input['altitude'],
            'course'     => $input['course'],
            'protocol'   => $input['protocol'],
        ];

        (new PositionsStack())->add($data);
    }

    /**
     * Recibe el push de la plataforma externa: {"code":200,"data":[{deviceId, latitude,
     * longitude, altitude, timestamp, accuracy, datePublished}, ...]} con hasta 200 items
     * y horas UTC (yyyy-MM-ddTHH:mm:ss). Encola igual que insert_position.
     */
    public function webhook() {
        $config = config('server.webhook_positions');
        $allowed = array_filter(array_map('trim', explode(',', (string)$config['allowed_ips'])));

        if ($allowed && !in_array(Request::ip(), $allowed))
            return Response::json(['code' => 403, 'message' => 'Forbidden'], 403);

        $input = Request::json()->all();
        $items = isset($input['data']) ? $input['data'] : $input;

        if (!is_array($items))
            return Response::json(['code' => 400, 'message' => 'Invalid body'], 400);

        $stack = new PositionsStack();
        $queued = 0;

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['deviceId']) || empty($item['timestamp'])
                || !isset($item['latitude'], $item['longitude']))
                continue;

            try {
                $fixTime = Carbon::parse($item['timestamp'], 'UTC')->getTimestamp() * 1000;
            } catch (\Exception $e) {
                continue;
            }

            $attributes = [];
            if (!empty($item['datePublished']))
                $attributes['datepublished'] = $item['datePublished'];

            $stack->add([
                'fixTime'    => $fixTime,
                'valid'      => 1,
                'imei'       => (string)$item['deviceId'],
                'latitude'   => (float)$item['latitude'],
                'longitude'  => (float)$item['longitude'],
                'altitude'   => isset($item['altitude']) ? (float)$item['altitude'] : 0,
                'accuracy'   => isset($item['accuracy']) && $item['accuracy'] !== '' ? (float)$item['accuracy'] : null,
                'attributes' => $attributes,
                'speed'      => 0,
                'course'     => null,
                'protocol'   => $config['protocol'],
            ]);

            $queued++;
        }

        return Response::json(['code' => 200, 'message' => 'success', 'queued' => $queued]);
    }
}
