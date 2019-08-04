<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

$params = [
    'index'=>'products',
    'type'=>'_doc',
    'body'=>[
        'query'=>[
            'bool'=>[
                'filter'=>[
                    ['term'=>['on_sale'=>true]]
                ],
                'must'=>[
                    [
                        'multi_match'=>[
                            'query'=>'内存条',
                            'fields'=>[
                                'title^3',
                                'long_title^2',
                                'category^3',
                                'description',
                                'skus.title',
                                'skus.description',
                                'properties.value'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'aggs'=>[
            'properties'=>[
                'nested'=>[
                    'path'=>'properties'
                ],
                'aggs'=>[
                    'properties'=>[
                        'terms'=>[
                            'field'=>'properties.name'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
