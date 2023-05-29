<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('/index');
});

#Route::get('/dashboard', function () {
#    return view('dashboard');
#})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/test', function () {
    return view('testpage');
});

Route::get('/dashboard', function () {
    return redirect('/');
})->middleware(['auth', 'verified'])->name('index');

Route::get('/dashboard_2', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard_2');


#Route::get('/run_playbook/{command}', 'App\Http\Controllers\PlaybookController@run');
#Route::post('/run_playbook/{command}', 'App\Http\Controllers\PlaybookController@run');

Route::get('/ping', 'App\Http\Controllers\PingController@run');
Route::post('/ping', 'App\Http\Controllers\PingController@run');

Route::get('/store_device', 'App\Http\Controllers\StoreDeviceController@run');
Route::post('/store_device', 'App\Http\Controllers\StoreDeviceController@run');

Route::get('/delete_device/{id}', 'App\Http\Controllers\StoreDeviceController@delete_device');
Route::post('/delete_device/{id}', 'App\Http\Controllers\RemoveDeviceController@delete_device');

Route::get('/run_playbook', 'App\Http\Controllers\PlaybookController@run');
Route::post('/run_playbook', 'App\Http\Controllers\PlaybookController@run');



Route::get('/inventory', function () {
    return view('inventory');
})->middleware(['auth', 'verified'])->name('inventory');

Route::get('/logs', function () {
    return view('logs');
})->middleware(['auth', 'verified'])->name('logs');

Route::get('/playbooks', function () {
    return view('use_playbooks');
})->middleware(['auth', 'verified'])->name('playbooks');

Route::post('/playbooks', function () {
    return view('use_playbooks');
})->middleware(['auth', 'verified'])->name('playbooks');

Route::get('/playbook_output', function () {
    return view('playbook_output');
})->middleware(['auth', 'verified'])->name('playbook_output');

Route::get('/add_device', function () {
    return view('add_device');
})->middleware(['auth', 'verified'])->name('add_device');

Route::post('/add_device', function () {
    return view('add_device');
})->middleware(['auth', 'verified'])->name('add_device');

Route::get('/update_topology', function () {
    return view('update_topology');
})->middleware(['auth', 'verified'])->name('update_topology');

Route::post('/update_topology', function () {
    return view('update_topology');
})->middleware(['auth', 'verified'])->name('update_topology');

Route::get('/update_topology_map', 'App\Http\Controllers\TopologyController@update_topology');
Route::post('/update_topology_map', 'App\Http\Controllers\TopologyController@update_topology');

Route::get('/delete_router/{router_id}', 'App\Http\Controllers\TopologyController@delete_router');


Route::get('/topology', function () {
    return view('topology');
})->middleware(['auth', 'verified'])->name('topology');

Route::get('/get_routers', 'App\Http\Controllers\TopologyController@get_routers');

Route::get('/store_router', 'App\Http\Controllers\TopologyController@store_router');
Route::post('/store_router', 'App\Http\Controllers\TopologyController@store_router');

Route::get('/prometheus', function () {
    return redirect('http://anmt:9090');
})->middleware(['auth', 'verified'])->name('prometheus');

Route::get('/grafana', function () {
    return redirect('http://anmt:3000');
})->middleware(['auth', 'verified'])->name('grafana');



Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
