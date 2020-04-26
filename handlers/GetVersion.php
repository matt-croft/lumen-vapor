<?php

namespace LumenVapor\Handlers;

use Illuminate\Http\Request;

class GetVersion
{
    public function handle(Request $request)
    {
        return app()->version();
    }
}
