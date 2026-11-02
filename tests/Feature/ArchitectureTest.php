<?php

arch('globals')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();

arch('controllers')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

arch('models')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('controllers delegate pdf rendering to the pdf documents')
    ->expect('App\Http\Controllers')
    ->not->toUse('Elibyy\TCPDF\Facades\TCPDF');

arch('models and services do not depend on the http layer')
    ->expect(['App\Models', 'App\Services'])
    ->not->toUse(['Illuminate\Http\Request', 'request']);

arch('application code does not use faker')
    ->expect('App')
    ->not->toUse(['Faker\Factory', 'Faker\Generator']);
