<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Pagination View
    |--------------------------------------------------------------------------
    |
    | This option controls the default pagination view that will be used
    | by the pagination library. In addition, you may set this to a
    | view of your own to customize pagination display.
    |
    */

    'default' => env('PAGINATION_VIEW', 'bootstrap-5'),

    /*
    |--------------------------------------------------------------------------
    | Pagination View Path
    |--------------------------------------------------------------------------
    |
    | This option allows you to customize the pagination view path. You
    | may set this to a custom path where your pagination views are
    | located. This path should be relative to the "resources/views"
    | directory of your application.
    |
    */

    'path' => env('PAGINATION_PATH', 'vendor.pagination'),

]; 