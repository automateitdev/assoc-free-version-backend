<?php

use App\Http\Controllers\Api\ApiController;
use App\Models\User;
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
    return view('welcome');
});

Route::get('/pdf-send', function () {
    return view('mail.subscription_form.service-agriment');
});


// Route::get('/log', function () {
//     // \Log::channel('custom_log')->debug("This is a debug message.");
//     // \Log::channel('custom_log')->info("This is an info level message.");
//     // \Log::channel('custom_log')->notice("This is a notice level message.");
//     // \Log::channel('custom_log')->warning("This is a warning level message.");
//     // \Log::channel('custom_log')->error("This is an error level message.");
//     // \Log::channel('custom_log')->critical("This is a critical level message.");
//     // \Log::channel('custom_log')->alert("This is an alert level message.");
//     // \Log::channel('custom_log')->emergency("This is an emergency level message.");
//     // \Log::channel('stack')->info('hello test');
//     $uesr = User::get();
//     create_log('emergency', 'hello'.$uesr,'custom_log');
//     \Log::info('hello');
// });
Route::post('/spgAdmissionConfirm', [ApiController::class, 'spgAdmissionConfirm']);



// Route::get('/liveFix', [ApiController::class, 'liveFixation']);
// Route::get('/fix-assign-roll', [ApiController::class, 'fixAssignRoll']);

Route::get('/admission-url', [ApiController::class, 'admissionUrl']);


