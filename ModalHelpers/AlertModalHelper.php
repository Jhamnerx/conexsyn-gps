<?php namespace ModalHelpers;

use CustomFacades\Field;
use CustomFacades\ModalHelpers\CustomEventModalHelper;
use CustomFacades\Repositories\AlertRepo;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\EventCustomRepo;
use CustomFacades\Repositories\PoiRepo;
use CustomFacades\Repositories\UserRepo;
use CustomFacades\Validators\AlertFormValidator;
use Formatter;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tobuli\Entities\Alert;
use Tobuli\Entities\Device;
use Tobuli\Entities\Event;
use Tobuli\Entities\Geofence;
use Tobuli\Entities\TaskStatus;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Helpers\Alerts\NotificationProvider;
use Tobuli\InputFields\AttributesCollection;
use Tobuli\Protocols\Commands;
use Tobuli\Services\Commands\CommandService;
use Tobuli\Services\EntityLoader\EnityLoader;
use Tobuli\Services\EntityLoader\UserDevicesGroupLoader;
use Tobuli\Services\EntityLoader\UserDriversLoader;
use Tobuli\Services\EntityLoader\UserGeofencesGroupLoader;
use Tobuli\Services\EntityLoader\UserPoisGroupLoader;
use Tobuli\Services\EntityLoader\UsersLoader;
use Tobuli\Services\ScheduleService;

class AlertModalHelper extends ModalHelper
{
    private UserDevicesGroupLoader $userDevicesLoader;
    private UsersLoader $usersLoader;
    private UserGeofencesGroupLoader $userZonesLoader;
    private UserGeofencesGroupLoader $userGeofencesLoader;
    private UserPoisGroupLoader $userPoisLoader;
    private UserDriversLoader $userDriversLoader;

    /**
     * @var ScheduleService
     */
    protected $schedulesService;

    public function __construct()
    {
        parent::__construct();

        $this->userDevicesLoader = new UserDevicesGroupLoader($this->user);
        $this->userDevicesLoader->setRequestKey('devices');
        $this->schedulesService = new ScheduleService();

        $this->usersLoader = new UsersLoader($this->user);
        $this->usersLoader->setRequestKey('users');

        $this->userZonesLoader = new UserGeofencesGroupLoader($this->user);
        $this->userZonesLoader->setRequestKey('zones');

        $this->userGeofencesLoader = new UserGeofencesGroupLoader($this->user);
        $this->userGeofencesLoader->setRequestKey('geofences');

        $this->userPoisLoader = new UserPoisGroupLoader($this->user);
        $this->userPoisLoader->setRequestKey('pois');

        $this->userDriversLoader = new UserDriversLoader($this->user);
        $this->userDriversLoader->setRequestKey('drivers');
    }

    public function get()
    {
        try {
            $this->checkException('alerts', 'view');
        } catch (\Exception $e) {
            return ['alerts' => []];
        }

        if ($this->api) {
            $alerts = Alert::enabled()
                ->userOwned($this->user)
                ->with(['devices', 'drivers', 'geofences', 'events_custom'])
                ->get();
            $types = $this->getTypesWithAttributes();

            foreach ($alerts as $alert) {
                $type = Arr::first($types, fn ($type) => $type['type'] === $alert->type);

                if ($type && !empty($type['attributes'])) {
                    $attributes = array_filter(
                        $type['attributes']->toArray(),
                        fn ($attr) => $alert->hasProperty($attr['name']) || $alert->hasGetMutator($attr['name'])
                    );

                    $alert->append(array_map(fn ($attr) => $attr['name'], $attributes));
                }
            }

            $alerts = $alerts->toArray();

            foreach ($alerts as $key => $alert) {
                $drivers = [];
                foreach ($alert['drivers'] as $driver)
                    array_push($drivers, $driver['id']);

                $alerts[$key]['drivers'] = $drivers;

                $devices = [];
                foreach ($alert['devices'] as $device)
                    array_push($devices, $device['id']);

                $alerts[$key]['devices'] = $devices;

                $geofences = [];
                foreach ($alert['geofences'] as $geofence)
                    array_push($geofences, $geofence['id']);

                $alerts[$key]['geofences'] = $geofences;

                $events_custom = [];
                foreach ($alert['events_custom'] as $event)
                    array_push($events_custom, $event['id']);

                $alerts[$key]['events_custom'] = $events_custom;
            }
        } else {
            $alerts = Alert::enabled()->userOwned($this->user)->get();
        }

        return compact('alerts');
    }

    public function createData()
    {
        $this->checkException('alerts', 'create');

        if (empty($this->user->devices()->count()))
            throw new ValidationException(['id' => trans('front.must_have_one_device')]);

        $types = $this->getTypesWithAttributes();
        $schedules = $this->schedulesService->getFormSchedules($this->user);
        $notifications = $this->getNotifications();
        $devices = [];
        $geofences = [];

        $alert_zones = [
            '1' => trans('front.zone_in'),
            '2' => trans('front.zone_out')
        ];

        if ($this->api) {
            $devices = apiArray($this->user->devices->pluck('name', 'id')->all());
            $geofences = apiArray(Geofence::userAccessible($this->user)->orderBy('name')->pluck('name', 'id')->all());
            $alert_zones = apiArray($alert_zones);
        }

        return compact(
            'devices',
            'geofences',

            'types',
            'schedules',
            'notifications',
            'alert_zones'
        );
    }

    public function create()
    {
        $this->checkException('alerts', 'store');

        $this->validate('create');

        $this->data['for_all_user_devices'] = $this->getForAllUserDevicesValue(null);

        beginTransaction();
        try {
            $alert = $this->user->alerts()->create($this->data);

            $this->saveRelations($alert);
        }
        catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1, 'item' => $alert];
    }

    public function editData()
    {
        $id = array_key_exists('alert_id', $this->data) ? $this->data['alert_id'] : request()->route('alerts');
        $id = $id ?: request()->route('id');

        /** @var Alert $item */
        $item = $this->user->alerts()->find($id);

        $this->checkException('alerts', 'edit', $item);

        if (empty($this->user->devices()->count()))
            throw new ValidationException(['id' => trans('front.must_have_one_device')]);

        $types = $this->getTypesWithAttributes($item);
        $schedules = $this->schedulesService->setSchedules($item->schedules ?: [])->getFormSchedules($this->user);
        $notifications = $this->getNotifications($item);
        $commands = [];
        $geofences = [];
        $devices = [];

        $alert_zones = [
            '1' => trans('front.zone_in'),
            '2' => trans('front.zone_out')
        ];

        if ($this->api) {
            $item->load('devices');
            $devices     = apiArray($this->user->devices->pluck('name', 'id')->all());
            $geofences   = apiArray(Geofence::userAccessible($this->user)->orderBy('name')->pluck('name', 'id')->all());
            $alert_zones = apiArray($alert_zones);
            $commands = (new CommandService($this->user))->getGprsCommands([]);

            $type = Arr::first($types, fn ($type) => $type['type'] === $item->type);

            if ($type) {
                $attributes = array_filter(
                    $type['attributes']->toArray(),
                    fn ($attr) => $item->hasProperty($attr['name'])
                );

                $item->append(array_map(fn ($attr) => $attr['name'], $attributes));
            }
        }

        return compact(
            'item',
            'devices',
            'geofences',

            'types',
            'schedules',
            'notifications',
            'alert_zones',
            'commands'
        );
    }

    public function edit()
    {
        $alert = $this->user->alerts()->find($this->data['id']);

        $this->checkException('alerts', 'update', $alert);

        $this->validate('update');

        $this->data['for_all_user_devices'] = $this->getForAllUserDevicesValue($alert);

        beginTransaction();
        try {
            $alert->update($this->data);

            $this->saveRelations($alert);
        }
        catch (\Exception $e) {
            rollbackTransaction();
            throw $e;
        }

        commitTransaction();

        return ['status' => 1];
    }

    private function saveRelations(Alert $alert): void
    {
        if ($this->api) {
            $alert->devices()->sync(Arr::get($this->data, 'devices', []));
            $alert->zones()->sync(Arr::get($this->data, 'zones', []));
            $alert->geofences()->sync(Arr::get($this->data, 'geofences', []));
            $alert->drivers()->sync(Arr::get($this->data, 'drivers', []));
            $alert->pois()->sync(Arr::get($this->data, 'pois', []));
        } else {
            $alert->devices()->syncLoader($this->userDevicesLoader);
            $alert->zones()->syncLoader($this->userZonesLoader);
            $alert->geofences()->syncLoader($this->userGeofencesLoader);
            $alert->drivers()->syncLoader($this->userDriversLoader);
            $alert->pois()->syncLoader($this->userPoisLoader);
        }

        $events_custom = Arr::get($this->data, 'events_custom', []);

        if ($events_custom) {
            $protocols = $alert->devices()->groupProtocols()->get()->pluck('protocol')->all();
            $events = EventCustomRepo::whereProtocols($events_custom, $protocols);
            $events_custom = $events->pluck('id')->all();
        }

        $alert->events_custom()->sync($events_custom);

        $this->setUsers($alert);
    }

    private function getForAllUserDevicesValue(?Alert $alert): bool
    {
        if (!$this->userDevicesLoader->hasSelect()) {
            return $alert->for_all_user_devices ?? false;
        }

        return $this->userDevicesLoader->hasSelectAll();
    }

    private function validate($type)
    {
        $alert_id = Arr::get($this->data, 'id');

        AlertFormValidator::validate($type, $this->data, $alert_id);

        $this->api
            ? $this->validateRelationsArray()
            : $this->validateRelationsLoader();

        if ($schedules = $this->data['schedules'] ?? []) {
            $this->schedulesService->validate($schedules);
            $this->data['schedules'] = $this->schedulesService->setFormSchedules($schedules);
        }

        $notificationProvider = (new NotificationProvider($this->user))->clearFilters();

        foreach (Arr::get($this->data, 'notifications', []) as $name => $notificationData)
        {
            try {
                $notification = $notificationProvider->find($name);
            } catch (\InvalidArgumentException $e) {
                throw new ValidationException(["notifications.$name" => 'Notification type not supported.']);
            }

            if (!$notification->isEnabled($this->user)) {
                throw new ValidationException(["notifications.$name.active" => trans('front.not_available')]);
            }

            $validator = $notification->validate($notificationData);

            if ($validator->fails()) {
                throw new ValidationException(["notifications.$name.input" => $validator->errors()->first()]);
            }

            $this->data['notifications'][$name] = Arr::only($this->data['notifications'][$name], ['active', 'input']);
        }

        if (Arr::get($this->data, 'command.active')) {
            if ($this->api) {
                $devices = DeviceRepo::getWhereIn($this->data['devices']);
            } else {
                $alert = AlertRepo::find($this->data['id'] ?? null);

                $devices = $this->getDevicesSelected($alert)->get();
            }

            $commands = (new CommandService($this->user))->getGprsCommands($devices);

            $rules = Commands::validationRules(Arr::get($this->data, 'command.type'), $commands);
            $validator = Validator::make($this->data, $rules);
            if ($validator->fails()) {
                throw new ValidationException($validator->messages());
            }

            if ($rules) {
                $this->data['command'] = array_merge(
                    Arr::only($this->data, array_keys($rules)),
                    $this->data['command']
                );
            }
        }

        if (!empty($this->data['acceptable_time_from'])) {
            $this->data['acceptable_time_from'] = Formatter::time()->reverse($this->data['acceptable_time_from']);
            $this->data['acceptable_time_from'] = Carbon::parse($this->data['acceptable_time_from'])->format('H:i');
        }
    }

    private function validateRelationsArray(): void
    {
        Validator::validate($this->data, [
            'drivers'       => 'required_if:type,driver|array',
            'events_custom' => 'required_if:type,custom|array',
            'geofences'     => 'required_if:type,geofence_in,geofence_out,geofence_inout|array',
            'pois'          => 'required_if:type,poi_stop_duration,poi_idle_duration|array',
            'zones'         => 'required_if:zone,1,2|array',
        ]);
    }

    private function validateRelationsLoader(): void
    {
        $alert = Alert::find(Arr::get($this->data, 'id'));
        $zone = Arr::get($this->data, 'zone');
        $type = Arr::get($this->data, 'type');

        $failures = array_filter([
            'selected_devices' => !$this->hasRelationSelected($this->userDevicesLoader, $alert?->devices()),

            'selected_zones' => in_array($zone, [1, 2])
                && !$this->hasRelationSelected($this->userZonesLoader, $alert?->zones()),

            'selected_geofences' => in_array($type, ['geofence_in', 'geofence_out', 'geofence_inout'])
                && !$this->hasRelationSelected($this->userGeofencesLoader, $alert?->geofences()),

            'selected_drivers' => $type === 'driver' && !$this->hasRelationSelected($this->userDriversLoader, $alert?->drivers()),

            'selected_pois' => in_array($type, ['poi_stop_duration', 'poi_idle_duration'])
                && !$this->hasRelationSelected($this->userPoisLoader, $alert?->pois()),
        ]);

        if (empty($failures)) {
            return;
        }

        $errors = [];

        foreach ($failures as $field => $ignored) {
            $transKey = substr($field, 9); // cut off 'selected_'

            $errors[$field] = [trans('validation.required', ['attribute' => trans("validation.attributes.$transKey")])];
        }

        throw new ValidationException($errors);
    }


    public function doDestroy($id) {
        $item = AlertRepo::find($id);

        $this->checkException('alerts', 'remove', $item);

        return compact('item');
    }

    public function destroy()
    {
        $id = array_key_exists('alert_id', $this->data) ? $this->data['alert_id'] : Arr::get($this->data, 'id');

        $this->checkException('alerts', 'clean');

        $ids = is_array($id) ? $id : [$id];

        $this->user->alerts()->whereIn('id', $ids)->delete();

        return ['status' => 1];
    }

    public function getTypesWithAttributes($alert = null)
    {
        if ($this->api) {
            $drivers = UserRepo::getDrivers($this->user->id)->pluck('name', 'id')->all();

            $geofences = Geofence::userAccessible($this->user)->orderBy('name')->pluck('name', 'id')->all();

            $pois = PoiRepo::whereUserId($this->user->id);
            $pois->map(function ($item) {
                $item['title'] = $item['name'];
                return $item;
            })->only('id', 'title')->all();
        }

        $events_custom = $alert ? CustomEventModalHelper::getGroupedEvents($alert->devices()) : [];
        $events_custom = Arr::pluck($events_custom, 'items');

        $types = self::getTypes();

        foreach ($types as & $type)
        {
            switch ($type['type']) {
                case 'overspeed':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'overspeed',
                            trans('validation.attributes.overspeed') . ' (' . Formatter::speed()->getUnit() . ')',
                            $alert ? $alert->overspeed : ''
                        ),
                    ]);
                    break;
                case 'time_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'time_duration',
                            trans('validation.attributes.time_duration') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->time_duration : ''
                        ),
                    ]);
                    break;
                case 'move_start':
                case 'stop_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'stop_duration',
                            trans('validation.attributes.stop_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->stop_duration : ''
                        ),
                    ]);
                    break;
                case 'move_start_filtered':
                    $timeOptions = getSelectTimeRange();

                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'stop_duration',
                            trans('validation.attributes.stop_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->stop_duration : ''
                        ),
                        Field::number(
                            'distance',
                            trans('validation.attributes.distance_limit') . ' (' . Formatter::distance()->getUnit() . ')',
                            $alert ? $alert->distance : 0
                        ),
                        \Field::select(
                            'acceptable_time_from',
                            trans('validation.attributes.acceptable_time_from'),
                            empty($alert->acceptable_time_from)
                                ? Arr::first($timeOptions)
                                : Carbon::parse(Formatter::time()->convert($alert->acceptable_time_from))->format('H:i'),
                        )->setOptions($timeOptions),
                    ]);
                    break;
                case 'offline_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'offline_duration',
                            trans('validation.attributes.offline_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->offline_duration : ''
                        ),
                    ]);
                    break;
                case 'move_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'move_duration',
                            trans('validation.attributes.move_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->move_duration : ''
                        ),
                        Field::number(
                            'min_parking_duration',
                            trans('validation.attributes.min_parking_duration') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->min_parking_duration : ''
                        ),
                    ]);
                    break;
                case 'idle_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'idle_duration',
                            trans('validation.attributes.idle_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->idle_duration : ''
                        ),
                    ]);
                    break;
                case 'ignition_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'ignition_duration',
                            trans('validation.attributes.ignition_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->ignition_duration : ''
                        ),
                    ]);

                    if ($this->user->perm('checklist', 'view')) {

                        $type['attributes']->push(
                            Field::select(
                                'pre_start_checklist_only',
                                trans('global.pre_start_checklist'),
                                $alert ? $alert->pre_start_checklist_only : 0
                            )->setOptions([
                                0 => trans('global.no'),
                                1 => trans('global.yes'),
                            ])->setDescription(trans('global.pre_start_checklist_alert_description'))
                        );
                    }
                    break;
                case 'ignition':
                    $type['attributes'] = AttributesCollection::make([
                        Field::select(
                            'state',
                            trans('validation.attributes.state'),
                            $alert ? $alert->state : null
                        )->setOptions([
                            0 => trans('global.all'),
                            1 => trans('front.on'),
                            2 => trans('front.off'),
                        ]),
                    ]);
                    break;
                case 'fuel_change':
                    $type['attributes'] = AttributesCollection::make([
                        Field::select(
                            'state',
                            trans('validation.attributes.state'),
                            $alert ? $alert->state : null
                        )->setOptions([
                            0 => trans('front.fill_theft'),
                            1 => trans('front.fill'),
                            2 => trans('front.theft'),
                        ]),
                    ]);
                    break;
                case 'driver':
                    $type['attributes'] = AttributesCollection::make([
                        $this->api
                            ? Field::multiSelect(
                                'drivers',
                                trans('front.drivers'),
                                $alert ? $alert->drivers->pluck('id')->all() : []
                            )->setOptions($drivers)
                            : Field::multiSelectLoader(
                                'drivers',
                                trans('front.drivers'),
                                route('alerts.drivers', $alert?->id)
                            ),
                    ]);
                    break;
                case 'driver_unauthorized':
                    $type['attributes'] = AttributesCollection::make([
                        Field::select(
                            'authorized',
                            trans('validation.attributes.authorized'),
                            $alert ? $alert->authorized : '0'
                        )->setOptions([
                            0 => trans('global.no'),
                            1 => trans('global.yes'),
                        ]),
                    ]);
                    break;
                case 'geofence_in':
                case 'geofence_out':
                case 'geofence_inout':
                    $type['attributes'] = AttributesCollection::make([
                        $this->api
                            ? Field::multiSelect(
                                'geofences',
                                trans('validation.attributes.geofences'),
                                $alert ? $alert->geofences->pluck('id')->all() : []
                            )->setOptions($geofences)
                            : Field::multiSelectLoader(
                                'geofences',
                                trans('validation.attributes.geofences'),
                                route('alerts.geofences', $alert?->id)
                            ),
                    ]);
                    break;
                case 'custom':
                    $type['attributes'] = AttributesCollection::make([
                        Field::multiSelect(
                            'events_custom',
                            trans('validation.attributes.event'),
                            $alert ? $alert->events_custom->pluck('id')->all() : []
                        )
                            ->setOptions($events_custom)
                            ->setDescription(trans('front.alert_events_tip')),
                        Field::number(
                            'continuous_duration',
                            trans('validation.attributes.continuous_duration') . '(' . trans('front.second_short') . ')',
                            $alert ? $alert->continuous_duration : 0
                        ),
                    ]);
                    break;
                case 'distance':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'distance',
                            trans('validation.attributes.distance_limit') . '(' . Formatter::distance()->getUnit() . ')',
                            $alert ? $alert->distance : 0
                        ),
                        Field::number(
                            'period',
                            trans('validation.attributes.period') . '(' . trans('global.days') . ')',
                            $alert ? $alert->period : 0
                        ),
                    ]);
                    break;
                case 'poi_stop_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'stop_duration',
                            trans('validation.attributes.stop_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->stop_duration : ''
                        ),
                        Field::number(
                            'distance_tolerance',
                            trans('validation.attributes.distance_tolerance') . ' (' . trans('front.mt') . ')',
                            $alert ? $alert->distance_tolerance : ''
                        ),
                        $this->api
                            ? Field::multiSelect(
                                'pois',
                                trans('validation.attributes.pois'),
                                $alert ? $alert->pois->pluck('id')->all() : []
                            )->setOptions($pois->pluck('title', 'id')->all())
                            : Field::multiSelectLoader(
                                'pois',
                                trans('validation.attributes.pois'),
                                route('alerts.pois', $alert?->id)
                            ),
                    ]);
                    break;
                case 'poi_idle_duration':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'idle_duration',
                            trans('validation.attributes.idle_duration_longer_than') . ' (' . trans('front.minutes') . ')',
                            $alert ? $alert->idle_duration : ''
                        ),
                        Field::number(
                            'distance_tolerance',
                            trans('validation.attributes.distance_tolerance') . ' (' . trans('front.mt') . ')',
                            $alert ? $alert->distance_tolerance : ''
                        ),
                        $this->api
                            ? Field::multiSelect(
                                'pois',
                                trans('validation.attributes.pois'),
                                $alert ? $alert->pois->pluck('id')->all() : []
                            )->setOptions($pois->pluck('title', 'id')->all())
                            : Field::multiSelectLoader(
                                'pois',
                                trans('validation.attributes.pois'),
                                route('alerts.pois', $alert?->id)
                            ),
                    ]);
                    break;
                case 'task_status':
                    $type['attributes'] = AttributesCollection::make([
                        Field::multiSelect(
                            'statuses',
                            trans('validation.attributes.statuses'),
                            $alert ? $alert->statuses : []
                        )->setOptions(TaskStatus::getList())
                    ]);
                    break;
                case 'unplugged':
                    $type['attributes'] = AttributesCollection::make([
                        Field::number(
                            'continuous_duration',
                            trans('validation.attributes.continuous_duration') . '(' . trans('front.second_short') . ')',
                            $alert ? $alert->continuous_duration : 0
                        ),
                    ]);
                    break;
                case 'device_expiration':
                    $availableCases = Alert::getAvailableCases();

                    $type['attributes'] = AttributesCollection::make([
                        Field::select(
                            'case',
                            trans('validation.attributes.case'),
                            $alert->case ?? ''
                        )->setOptions(array_filter([
                            Alert::CASE_EXPIRED => trans('front.expired'),
                            Alert::CASE_EXPIRING => trans('front.expiring'),
                            Alert::CASE_EXPIRED_SIM => trans('front.sim_expired'),
                            Alert::CASE_EXPIRING_SIM => trans('front.sim_expiring'),
                        ], fn ($key) => in_array($key, $availableCases), ARRAY_FILTER_USE_KEY)),
                        Field::number('days', trans('global.days'), $alert->days ?? 0)
                    ]);
                    break;
                default:
                    break;
            }
        }

        return $types;
    }

    public static function getTypes()
    {
        $types = [
            [
                'type'  => 'overspeed',
                'title' => trans('front.overspeed'),
            ],
            [
                'type'  => 'stop_duration',
                'title' => trans('front.stop_duration'),
            ],
            [
                'type'  => 'time_duration',
                'title' => trans('front.time_duration'),
            ],
            [
                'type'  => 'offline_duration',
                'title' => trans('front.offline_duration'),
            ],
            [
                'type'  => 'move_duration',
                'title' => trans('front.move_duration'),
            ],
            [
                'type' => 'ignition_duration',
                'title' => trans('front.ignition_duration'),
            ],
            [
                'type'  => 'idle_duration',
                'title' => trans('front.idle_duration'),
            ],
            [
                'type'  => 'ignition',
                'title' => trans('front.ignition_on_off'),
            ],
            [
                'type'  => 'move_start',
                'title' => trans('front.start_of_movement'),
            ],
            [
                'type'  => 'move_start_filtered',
                'title' => trans('front.start_of_movement') . ' (' . trans('validation.attributes.filter') . ')',
            ],
            [
                'type'  => 'driver',
                'title' => trans('front.driver_change'),
            ],
            [
                'type'  => 'driver_unauthorized',
                'title' => trans('front.driver_change_authorization'),
            ],
            [
                'type'  => 'geofence_in',
                'title' => trans('front.geofence') . ' ' . trans('global.in'),
            ],
            [
                'type'  => 'geofence_out',
                'title' => trans('front.geofence') . ' ' . trans('global.out'),
            ],
            [
                'type'  => 'geofence_inout',
                'title' => trans('front.geofence') . ' ' . trans('global.in') . '/' . trans('global.out'),
            ],
            [
                'type'  => 'custom',
                'title' => trans('front.custom_events'),
            ],
            [
                'type'  => 'sos',
                'title' => 'SOS',
            ],
            [
                'type'  => 'fuel_change',
                'title' => trans('front.fuel') . ' (' . trans('front.fill_theft') . ')',
            ],
            [
                'type' => 'distance',
                'title' => trans('global.distance'),
            ],
            [
                'type'  => 'poi_stop_duration',
                'title' => trans('front.poi_stop_duration'),
            ],
            [
                'type'  => 'poi_idle_duration',
                'title' => trans('front.poi_idle_duration'),
            ],
            [
                'type' => 'unplugged',
                'title' => trans('front.unplugged'),
            ],
            [
                'type'  => 'task_status',
                'title' => trans('front.task_status'),
            ],
            [
                'type'  => 'device_expiration',
                'title' => trans('front.device_expiration'),
            ],
        ];

        $expect = [];

        if (!config('addon.alert_time_duration'))
            $expect[] = 'time_duration';

        if (!config('addon.sensor_type_plugged'))
            $expect[] = 'unplugged';

        if (!config('addon.alert_move_start_filtered'))
            $expect[] = 'move_start_filtered';

        if (!auth()->user()->perm('tasks', 'view'))
            $expect[] = 'task_status';

        if (!empty($expect))
            $types = Arr::where($types, function ($type) use ($expect) {
                return !in_array($type['type'], $expect);
            });

        //reindex
        return array_values($types);
    }

    public function getNotifications(?Alert $alert = null): array
    {
        $notifications = (new NotificationProvider($this->user))->getInputMeta($alert ?: new Alert());

        // indexes reset with array_values
        return array_values($notifications);
    }

    public function getCommands()
    {
        if ($this->api) {
            $devicesKey = 'devices';

            $ids = $this->data[$devicesKey] ?? [];

            $devices = $this->user->devices()->whereIn('id', $ids)->get();
        } else {
            $devicesKey = 'selected_devices';

            $query = $this->getDevicesSelected($this->user->alerts()->find($this->data['alert_id'] ?? null));

            $devices = $query ? $query->get() : collect();
        }

        Validator::validate([$devicesKey => $devices->pluck('id')->all()], [$devicesKey => 'required|array']);

        $commands = (new CommandService($this->user))->getGprsCommands($devices);

        return $commands;
    }

    public function syncDevices()
    {
        $alert = AlertRepo::find($this->data['alert_id']);

        $this->checkException('alerts', 'update', $alert);

        AlertFormValidator::validate('devices', $this->data);

        $alert->devices()->sync(Arr::get($this->data, 'devices', []));

        return ['status' => 1];
    }

    public function customEvents()
    {
        $alert = AlertRepo::find($this->data['alert_id'] ?? null);

        $devices = $this->getDevicesSelected($alert);

        if (empty($devices))
            return [];

        $events = CustomEventModalHelper::getGroupedEvents($devices);

        array_walk($events, function(&$v){ $v['items'] = apiArray($v['items']); });

        return $events;
    }

    public function summary($from = null, $to = null)
    {
        $this->checkException('events', 'view');

        $query = $this->user->alerts()
            ->select(DB::raw('count(*) as count, alerts.type'))
            ->join('events', 'alerts.id', '=', 'events.alert_id')
            ->groupBy('alerts.type');

        if ($from)
            $query->where('events.created_at', '>=', $from);

        if ($to)
            $query->where('events.created_at', '<=', $to);

        $alerts = $query->get()->pluck('count', 'type');

        $types = collect(AlertModalHelper::getTypes())
            ->map(function($type) use ($alerts) {
                $type['count'] = $alerts[$type['type']] ?? 0;

                return $type;
            });

        return $types;
    }

    protected function setUsers($alert)
    {
        if (!isAdmin() || !$this->user->can('view', new \Tobuli\Entities\User()))
            return;

        if (!$this->usersLoader->hasSelect())
            return;

        $this->usersLoader->setQueryStored($alert->users());
        $alert->users()->syncLoader($this->usersLoader);
    }

    protected function getDevicesSelected($alert)
    {
        if (!$this->userDevicesLoader->hasSelect() && !$alert)
            return null;

        if ($alert) {
            $this->userDevicesLoader->setQueryStored($alert->devices());
        }

        return $this->userDevicesLoader->getQuerySelected();
    }

    protected function hasRelationSelected(EnityLoader $loader, ?Relation $queryStored): bool
    {
        if (!$loader->hasSelect() && !$queryStored) {
            return false;
        }

        if ($queryStored) {
            $loader->setQueryStored($queryStored);
        }

        return $loader->getQuerySelected()->count() > 0;
    }
}