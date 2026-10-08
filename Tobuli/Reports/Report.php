<?php

namespace Tobuli\Reports;

use Barryvdh\Snappy\Facades\SnappyPdf as PDF;
use Formatter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Tobuli\Entities\Poi;
use Tobuli\Entities\User;
use Tobuli\Entities\UserReportTypePivot;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\GeoLocation\GeoLocation;
use Tobuli\InputFields\AbstractField;

abstract class Report implements Arrayable
{
    protected $formats = ['html', 'json', 'xls', 'xlsx', 'pdf', 'pdf_land'];
    protected $disabledFormats = [];
    protected $enableFields = [
        'period',
        'date_from',
        'from_time',
        'date_to',
        'to_time',
        'devices',
        'geofences',
        'metas',
        'speed_limit',
        'stops',
        'show_addresses',
        'zones_instead',
    ];
    protected $disableFields = [];
    protected $validation = [];
    protected $deviceless = false;
    protected bool $allDevices = false;

    /** @var User */
    protected $user;

    protected $format;

    protected $date_from;

    protected $date_to;

    protected $items = [];
    protected $totals = [];

    protected $metas;
    protected $devices;
    protected $geofences;
    protected $pois;
    protected $parameters;
    protected $stop_seconds = 60;
    protected $speed_limit = null;
    protected $skip_blank_results = false;
    protected $period_time_from = null;
    protected $period_time_to = null;
    protected $period_weekdays = null;

    public $zones_instead = false;
    public $show_addresses = false;

    abstract protected function generate();
    abstract public function typeID();
    abstract public function title();

    public function __construct()
    {
        $this->geoLocation = new GeoLocation();
        $this->setMetaList($this->defaultMetas());
    }

    private function _generate()
    {
        $this->beforeGenerate();
        $this->generate();
        $this->afterGenerate();
    }

    public static function checkUsable(User $user): bool
    {
        return static::isAvailable() && static::isReasonable() && static::isUserEnabled($user);
    }

    public static function isReasonable(): bool
    {
        return true;
    }

    public static function isAvailable(): bool
    {
        return true;
    }

    public static function isEnabled(): bool
    {
        return settings()->get('reports.' . static::TYPE_ID . '.status') ?? true;
    }

    public static function isUserEnabled(User $user): bool
    {
        $default = self::isEnabled();

        $userReports = Cache::store('array')->sear(
            'user.' . $user->id . '.report_types',
            fn () => UserReportTypePivot::where('user_id', $user->id)->pluck('report_type_id', 'report_type_id')
        );

        return ($userReports->isEmpty() && $default) || $userReports->has(static::TYPE_ID);
    }

    public function isDeviceless()
    {
        return $this->deviceless;
    }

    public function hasAllDevices(): bool
    {
        return $this->allDevices;
    }

    protected function beforeGenerate()
    {
        if ($this->zones_instead && (empty($this->geofences) || $this->geofences->isEmpty()))
            $this->geofences = $this->user->geofences;
    }

    protected function afterGenerate() {}

    public function setUser($user)
    {
        $this->user = $user;
    }

    public function setFormat($format)
    {
        $this->format = strtolower($format);
    }

    public function setGeofences($geofences)
    {
        $this->geofences = $geofences;
    }

    public function getGeofences()
    {
        return $this->geofences;
    }

    public function getGeofenceNames()
    {
        return $this->getGeofences()->pluck('name')->all();
    }

    /**
     * @param Poi[]
     */
    public function setPois($pois)
    {
        $this->pois = $pois;
    }

    public function getPois()
    {
        return $this->pois;
    }

    public function setParameters($parameters)
    {
        $this->parameters = $parameters;
    }

    public function setStopMinutes($minutes)
    {
        $this->stop_seconds = $minutes * 60;
    }

    public function setStopSeconds($seconds)
    {
        $this->stop_seconds = $seconds;
    }

    public function setSpeedLimit($speed)
    {
        $this->speed_limit = $speed;
    }

    public function getSpeedLimit()
    {
       return $this->speed_limit;
    }

    public function setZonesInstead($value)
    {
        $this->zones_instead = $value;
    }

    public function setShowAddresses($value)
    {
        $this->show_addresses = $value;
    }

    public function setSkipBlankResults($value)
    {
        $this->skip_blank_results = $value;
    }

    public function getSkipBlankResults()
    {
        return $this->skip_blank_results;
    }

    public function setPeriodWeekdays($value)
    {
        $this->period_weekdays = $value;
    }

    public function getPeriodWeekdays()
    {
        return $this->period_weekdays;
    }

    public function setPeriodTimeFrom($value)
    {
        $this->period_time_from = $value;
    }

    public function getPeriodTimeFrom()
    {
        return $this->period_time_from;
    }

    public function setPeriodTimeTo($value)
    {
        $this->period_time_from = $value;
    }

    public function getPeriodTimeTo()
    {
        return $this->period_time_to;
    }

    public function getFormat()
    {
        return $this->format;
    }

    public function getDateFrom()
    {
        return $this->date_from;
    }

    public function getDateTo()
    {
        return $this->date_to;
    }

    public function hasDateFrom(): bool
    {
        return !in_array('date_from', $this->disableFields) && in_array('date_from', $this->enableFields);
    }

    public function hasDateTo(): bool
    {
        return !in_array('date_to', $this->disableFields) && in_array('date_to', $this->enableFields);
    }

    public function getItems()
    {
        if (empty($this->items))
            return [];

        return $this->items;
    }

    public function globalTotals($key = null)
    {
        if (is_null($key))
            return $this->totals;

        if (empty($this->totals[$key]))
            return null;

        return $this->totals[$key];
    }

    public function getType()
    {
        return [
            'type'          => $this->typeID(),
            'name'          => $this->title(),
            'formats'       => array_diff($this->formats, $this->disabledFormats),
            'fields'        => array_diff($this->enableFields, $this->disableFields),
            'validation'    => $this->getValidation(),
            'parameters'    => collect($this->getInputParameters()),
        ];
    }

    public function getValidation(): array
    {
        return $this->getDefaultValidation() + $this->validation;
    }

    protected function getDefaultValidation(): array
    {
        $rules = [
            'type'              => 'required|' . Rule::in(array_keys(ReportManager::$types)),
            'metas'             => 'array',
            'metas.*'           => Rule::in(array_keys(ReportManager::getMetaList($this->user))),
            'send_to_email'     => ['array_max:' . config('tobuli.limits.report_emails')],
            'send_to_email.*'   => 'email',
        ];

        if ($this->hasDateFrom()) {
            $rules['date_from'] = 'required|date';
        }

        if ($this->hasDateTo()) {
            $rules['date_to'] = 'required|date';

            if ($this->hasDateFrom()) {
                $rules['date_to'] .= '|after_or_equal:date_from';
            }
        }

        if (config('addon.report_send_to_ftp')) {
            $rules['send_to_ftp'] = 'url|starts_with:ftp,sftp';
        }

        return $rules;
    }

    public function validateInput(array &$input)
    {
        $rules = $this->getValidation();
        $parameters = [];

        /** @var AbstractField $parameter */
        foreach ($this->getInputParameters() as $parameter) {
            if (empty($parameter->getAdditionalParameter('param_omit')))
                $parameters[] = $parameter->getName();

            if (empty($parameter->getValidation())) {
                continue;
            }

            //html attribute name to validation name
            $name = preg_replace(['/\[\]/', '/\[([^\[\]]+)\]/'], ['.*', '.$1'], $parameter->getName());

            $rules[$name] = $parameter->getValidation();
        }

        $validator = Validator::make($input, $rules)->after(function (\Illuminate\Validation\Validator $validator) use ($input) {
            $hasSchedule = !empty($input['daily']) || !empty($input['weekly']) || !empty($input['monthly']);

            if (!$hasSchedule) {
                return;
            }

            if (!config('addon.report_send_to_ftp')) {
                if (empty($input['send_to_email'])) {
                    $validator->errors()->add('send_to_email', trans('validation.required', [
                        'attribute' => trans('validation.attributes.send_to_email'),
                    ]));
                }

                return;
            }

            if (empty($input['send_to_email']) && empty($input['send_to_ftp'])) {
                $validator->errors()
                    ->add('send_to_email', trans('validation.required_without', [
                        'attribute' => trans('validation.attributes.send_to_email'),
                        'values' => trans('validation.attributes.send_to_ftp'),
                    ]))
                    ->add('send_to_ftp', trans('validation.required_without', [
                        'attribute' => trans('validation.attributes.send_to_ftp'),
                        'values' => trans('validation.attributes.send_to_email'),
                    ]));
            }
        });

        if ($validator->fails()) {
            throw new ValidationException($validator->messages());
        }

        $input['parameters'] = Arr::only($input, $parameters);
    }

    /**
     * @return AbstractField[]
     */
    public function getInputParameters(): array
    {
        return [];
    }

    public function setRange($date_from, $date_to)
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
    }

    public function getFilename()
    {
        $items = [Str::snake(class_basename(get_class($this)))];

        if ($this->hasDateFrom()) {
            $items[] = Formatter::time()->convert($this->date_from);
        }

        if ($this->hasDateTo()) {
            $items[] = Formatter::time()->convert($this->date_to);
        }

        $items[] = time();

        $filename = implode('_', $items);
        $filename = mb_convert_encoding($filename, 'ASCII');
        $filename = strtr($filename, [
            ' ' => '_',
            '-' => '_',
            ':' => '_',
            '/' => '_'
        ]);

        return $filename;
    }

    public function getView()
    {
        return 'front::Reports.partials.type_' . $this::TYPE_ID;
    }

    public function toHTML()
    {
        return view($this->getView())->with(['report' => $this]);
    }

    protected function getJsonData(): array
    {
        return [
            'meta' => $this->metas,
            'items' => $this->items,
            'totals' => $this->totals,
        ];
    }

    public function toJson()
    {
        return json_encode($this->getJsonData());
    }

    public function toPDF()
    {
        return PDF::loadView($this->getView(), ['report' => $this]);
    }

    public function toPDFLand()
    {
        return $this->toPDF()->setPaper('A4', 'landscape');
    }

    public function toCSV()
    {
        $filename = $this->getFilename() . '.csv';
        $filePath = storage_path('cache/' . $filename);

        $file = fopen($filePath, 'wb');

        // UTF-8 BOM
        fwrite($file,"\xEF\xBB\xBF");

        $this->toCSVData($file);

        fclose($file);

        return $filePath;
    }

    protected function toCSVData($file) {
        return;
    }

    public function save()
    {
        $this->_generate();

        $path = storage_path('cache');
        $filename = $this->getFilename();
        $filePath = $path . '/' . $filename;

        switch ($this->format) {
            case 'html':
                file_put_contents($filePath . '.html', $this->toHTML()->render());

                return $filePath . '.html';
            case 'json':
                file_put_contents($filePath . '.json', $this->toJson());

                return $filePath . '.json';
            case 'pdf':
                $this->toPDF()->save($filePath . '.pdf');

                return $filePath . '.pdf';

            case 'pdf_land':
                $this->toPDFLand()->save($filePath . '.pdf');

                return $filePath . '.pdf';

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);
                Excel::store($export, $filename.'.xls', 'storage_cache', \Maatwebsite\Excel\Excel::XLS);

                return $filePath . '.xls';

            case 'xlsx':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);
                Excel::store($export, $filename.'.xlsx', 'storage_cache', \Maatwebsite\Excel\Excel::XLSX);

                return $filePath . '.xlsx';

            case 'csv':
                return $this->toCSV();

            default:
                throw new \Exception("Wrong report format '{$this->format}'");
        }
    }

    public function download()
    {
        $this->_generate();

        switch ($this->format) {
            case 'html':
                header('Content-disposition: attachment; filename="' . utf8_encode($this->getFilename()) . '.html"');
                header('Content-type: text/html');

                echo $this->toHTML()->render();
                break;

            case 'json':
                header('Content-disposition: attachment; filename="' . utf8_encode($this->getFilename()) . '.json"');
                header('Content-type: application/json');

                echo $this->toJson();
                break;

            case 'pdf':
                return $this->toPDF()->download($this->getFilename() . '.pdf');

            case 'pdf_land':
                return $this->toPDFLand()->download($this->getFilename() . '.pdf');

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::download($export, $this->getFilename().'.xls', \Maatwebsite\Excel\Excel::XLS);

            case 'xlsx':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::download($export, $this->getFilename().'.xlsx', \Maatwebsite\Excel\Excel::XLSX);

            case 'csv':
                $headers = array();

                return response()->download($this->toCSV(), utf8_encode($this->getFilename()) . '.csv', $headers);
        }
    }

    public function view()
    {
        $this->_generate();

        switch ($this->format) {
            case 'html':
                return $this->toHTML();

            case 'json':
                return $this->toJson();

            case 'pdf':
                return $this->toPDF();

            case 'pdf_land':
                return $this->toPDFLand();

            case 'xls':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::raw($export, \Maatwebsite\Excel\Excel::XLS);

            case 'xlsx':
                $export = new ReportXlsViewExport($this->getView(), ['report' => $this]);

                return Excel::raw($export, \Maatwebsite\Excel\Excel::XLSX);
        }
    }

    protected function getLocation($position, $text = null)
    {
        if (empty($position->latitude) && empty($position->longitude))
            return $text;

        if (is_null($text))
            $text = $this->getAddress($position);

        return googleMapLink($position->latitude, $position->longitude, $text);
    }

    protected function getAddress($position)
    {
        $address = null;

        if (empty($position->latitude) && empty($position->longitude))
            return $address;

        if ($this->zones_instead)
            $address = $this->getGeofencesNames($position);

        if ($this->show_addresses && ! $address ) {
            $address = $this->geoLocation->resolveAddressHandled($position->latitude, $position->longitude);
        }

        return $address ? htmlentities(removeEmoji($address)) : null;
    }

    protected function getGeofencesIn($position)
    {
        if (empty($position))
            return [];

        if (empty($this->geofences))
            return [];

        return $this->geofences->filter(function($geofence) use ($position) {
            return $geofence->pointIn($position);
        });
    }

    protected function getGeofencesNames($position)
    {
        $geofences = $this->getGeofencesIn($position);

        if ( ! $geofences)
            return null;

        return $geofences->implode('name', ', ');
    }

    /**
     * @return array
     */
    abstract protected function defaultMetas();

    public function setMetas($data)
    {
        if (empty($data))
            return;

        $list = ReportManager::getMetaList($this->user);

        $list = array_filter($list, function($title, $key) use ($data){
            return in_array($key, $data);
        }, ARRAY_FILTER_USE_BOTH);

        $this->setMetaList($list);
    }

    private function setMetaList($list)
    {
        if (empty($list))
            return;

        foreach ($list as $key => $title) {
            list($model, $attribute) = explode('.', $key, 2);

            $this->metas[$model][$key] = [
                'title' => $title,
                'attribute' => $attribute,
            ];
        }
    }

    public function metas($object = null)
    {
        if (empty($this->metas))
            return [];

        if (is_null($object)) {
            return Arr::collapse($this->metas);
        }

        if (empty($this->metas[$object]))
            return [];

        return $this->metas[$object];
    }

    public function toArray(): array
    {
        $data = $this->getType();
        $data['fields'] = array_values($data['fields']);

        return $data;
    }
}