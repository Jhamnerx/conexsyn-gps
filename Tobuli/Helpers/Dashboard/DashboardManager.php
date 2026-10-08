<?php namespace Tobuli\Helpers\Dashboard;

use Illuminate\Support\Str;
use Tobuli\Helpers\Dashboard\Blocks\Block;

class DashboardManager
{
    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getFrame($name)
    {
        return $this->newBlockClass($name)->buildFrame();
    }

    /**
     * @param $name
     * @return mixed
     * @throws \Exception
     */
    public function getContent($name)
    {
        return $this->newBlockClass($name)->buildContent();
    }

    /**
     * @throws \Exception
     */
    public function getConfig($name, $key = null)
    {
        return $this->newBlockClass($name)->getConfig($key);
    }

    /**
     * @param $name
     * @return Block
     * @throws \Exception
     */
    public function getBlock($name)
    {
        return $this->newBlockClass($name);
    }

    /**
     * @param $name
     * @return Block
     * @throws \Exception
     */
    private function newBlockClass($name)
    {
        $class = 'Tobuli\Helpers\Dashboard\Blocks\\' . ucfirst(Str::camel($name)) . 'Block';

        if ( ! class_exists($class, true))
            throw new \Exception('Dashboard class found ' . $class);

        return new $class;
    }
}