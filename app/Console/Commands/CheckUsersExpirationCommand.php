<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Carbon;
use Tobuli\Entities\Event;
use Tobuli\Entities\SendQueue;
use Tobuli\Entities\User;

class CheckUsersExpirationCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'users_expiration:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates users expiration events.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->createExpiringEvents();
        $this->createExpiredEvents();

        $this->line('DONE');
    }

    private function createExpiringEvents(): void
    {
        if (!settings('main_settings.expire_notification.active_before')) {
            return;
        }

        $daysBefore = settings('main_settings.expire_notification.days_before');
        $repeatIn = settings('main_settings.expire_notification.repeat_expiring_each_days');

        $users = User::isExpiringAfter($daysBefore)
            ->select('users.*')
            ->selectSub(fn (Builder $query) => $query
                ->selectRaw('MAX(events_log.time)')
                ->from('events_log')
                ->whereColumn('events_log.object_id', 'users.id')
                ->where('events_log.object_type', array_search(User::class, Relation::morphMap()))
                ->where('events_log.type', Event::TYPE_EXPIRING_USER)
                ->whereColumn('events_log.time', '<=', 'users.subscription_expiration')
                ->whereRaw('events_log.time >= DATE_SUB(users.subscription_expiration, INTERVAL '. $daysBefore .' DAY)'),
                'last_event_time'
            )->cursor();

        $this->checkUsersSend($users, Event::TYPE_EXPIRING_USER, $repeatIn);
    }

    private function createExpiredEvents(): void
    {
        if (!settings('main_settings.expire_notification.active_after')) {
            return;
        }

        $daysAfter = settings('main_settings.expire_notification.days_after');
        $repeatIn = settings('main_settings.expire_notification.repeat_expired_each_days');

        $users = User::isExpiredBefore($daysAfter)
            ->select('users.*')
            ->selectSub(fn (Builder $query) => $query
                ->selectRaw('MAX(events_log.time)')
                ->from('events_log')
                ->whereColumn('events_log.object_id', 'users.id')
                ->where('events_log.object_type', array_search(User::class, Relation::morphMap()))
                ->where('events_log.type', Event::TYPE_EXPIRED_USER)
                ->whereColumn('events_log.time', '>=', 'users.subscription_expiration'),
                'last_event_time'
            )->cursor();

        $this->checkUsersSend($users, Event::TYPE_EXPIRED_USER, $repeatIn);
    }

    private function checkUsersSend($users, string $type, int $repeatIn): void
    {
        $now = Carbon::now();

        foreach ($users as $user) {
            if (!$user->last_event_time) {
                $this->createEvent($type, $user);
                continue;
            }

            if ($repeatIn && $now->diffInDays($user->last_event_time) >= $repeatIn) {
                $this->createEvent($type, $user);
            }
        }
    }

    private function createEvent($type, User $user): void
    {
        $data = [
            'user_id'   => $user->id,
            'type'      => $type,
            'sender'    => SendQueue::SENDER_SYSTEM,
            'data'      => $user,
            'channel'   => 'push',
        ];

        SendQueue::create(array_merge($data, [
            'channel'   => 'push'
        ]));

        SendQueue::create(array_merge($data, [
            'channel'      => 'email',
            'channel_data' => empty($user->manager->email)
                ? [$user->email]
                : [$user->email, $user->manager->email],
        ]));

        if ($user->phone_number) {
            SendQueue::create(array_merge($data, [
                'channel'      => 'sms',
                'channel_data' => $user->phone_number,
            ]));
        }

        $user->logEvent($type);
    }
}