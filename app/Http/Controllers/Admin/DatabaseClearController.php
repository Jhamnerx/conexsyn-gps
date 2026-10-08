<?php namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;
use Tobuli\Exceptions\ValidationException;
use Tobuli\Services\DatabaseConnectionService;
use Tobuli\Services\DatabaseService;
use Tobuli\Validation\AdminDatabaseClearFormValidator;

class DatabaseClearController extends BaseController {

    /**
     * @var AdminDatabaseClearFormValidator
     */
    private $adminBackupsFormValidator;

    function __construct(AdminDatabaseClearFormValidator $adminDatabaseClearFormValidator) {
        parent::__construct();
        $this->adminDatabaseClearFormValidator = $adminDatabaseClearFormValidator;
    }

    public function panel() {
        $settings = settings('db_clear');

        return View::make('admin::DatabaseClear.panel')->with(compact('settings'));
    }

    public function save() {
        $input = Request::all();

        try
        {
            $this->adminDatabaseClearFormValidator->validate('update', $input);

            $data = [
                'status' => ! empty($input['status']),
                'days'   => $input['days'],
                'from'   => $input['from']
            ];

            settings('db_clear', $data);

            return Redirect::route('admin.tools.index')->withSuccess(trans('front.successfully_saved'));
        }
        catch (ValidationException $e)
        {
            return Redirect::route('admin.tools.index')->withInput()->withErrors($e->getErrors());
        }
    }

    public function getDbSize()
    {
        $service = new DatabaseService();
        $total = $service->getTotalSize();
        $reserved = $service->getReservedSize();

        return response()->json([
            'total' => [
                'size' => formatBytes( $total ),
                'percentage' => 100,
            ],
            'used' => [
                'size' => formatBytes( $total - $reserved ),
                'percentage' => $total ? ($total - $reserved) * 100 / $total : 0,
            ],
            'reserved' => [
                'size' => formatBytes( $reserved ),
                'percentage' => $total ? $reserved * 100 / $total : 0,
            ]
        ]);
    }
}
