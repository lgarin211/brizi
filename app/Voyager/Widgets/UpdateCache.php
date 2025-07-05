<?php

namespace App\Voyager\Widgets;

use Arrilot\Widgets\AbstractWidget;

class UpdateCache extends AbstractWidget
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    public function run()
    {
        return view('voyager::widgets.update_cache', [
            'config' => $this->config,
        ]);
    }
}
