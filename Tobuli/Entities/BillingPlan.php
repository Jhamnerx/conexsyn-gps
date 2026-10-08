<?php namespace Tobuli\Entities;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Tobuli\Helpers\Templates\Builders\BillingPlanTemplate;
use Tobuli\Traits\Orderable;

class BillingPlan extends AbstractEntity
{
    use Orderable;

    protected static BillingPlanTemplate $templateBuilder;

    protected $table = 'billing_plans';

    protected $fillable = [
        'title',
        'price',
        'objects',
        'duration_type',
        'duration_value',
        'visible',
        'template',
    ];

    public $timestamps = false;
    private $permissions = null;

    public function perm($name, $mode)
    {
        $mode = trim($mode);
        $modes = Config::get('permissions.modes');

        if ( ! array_key_exists($mode, $modes)) {
            die('Bad permission');
        }

        if (is_null($this->permissions)) {
            $this->permissions = [];
            $perms = DB::table('billing_plan_permissions')
                ->select('name', 'view', 'edit', 'remove')
                ->where('plan_id', '=', $this->id)
                ->get()
                ->all();

            if ( ! empty($perms)) {
                foreach ($perms as $perm) {
                    $this->permissions[$perm->name] = [
                        'view'   => $perm->view,
                        'edit'   => $perm->edit,
                        'remove' => $perm->remove,
                    ];
                }
            }
        }

        return array_key_exists($name, $this->permissions) && array_key_exists($mode,
            $this->permissions[$name]) ? boolval($this->permissions[$name][$mode]) : false;
    }

    public function getPermissions()
    {
        $permissions = [];

        $defaultPermissions = Config::get('permissions.list');

        foreach ($defaultPermissions as $name => $modes) {
            foreach($modes as $mode => $value) {
                $permissions[$name][$mode] = $this->perm($name, $mode);
            }
        }

        return $permissions;
    }

    public function isFree()
    {
        return $this->price <= 0;
    }

    public function buildTemplate(array $permissions, string $submitUrl): ?string
    {
        if (!config('addon.plan_templates')) {
            return null;
        }

        $template = (object)['title' => '', 'note' => $this->template];

        $data = $this->toArray();
        $data['permissions'] = $permissions;
        $data['submit_url'] = $submitUrl;

        return self::getTemplateBuilder()->buildTemplate($template, $data)['body'];
    }

    protected static function getTemplateBuilder(): BillingPlanTemplate
    {
        return self::$templateBuilder ?? (self::$templateBuilder = new BillingPlanTemplate());
    }
}
