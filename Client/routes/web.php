<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of the routes that are handled
| by your application. Just tell Laravel the URIs it should respond
| to using a Closure or controller method. Build something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/login', function () {
    $query = http_build_query([
        'client_id' => '3',
        'redirect_uri' => 'http://192.168.1.65:1024/auth/callback',
        'response_type' => 'code',
        'intented_uri' => session("url.intended","http://192.168.1.65:1024/home"),
        'scope' => '',
    ]);
    // dd($query);
    return redirect('http://192.168.1.65:1025/oauth/authorize?'.$query);
})->name('login');

Route::get('/test', function() {
    dd(session("access_token"));
})->middleware("auth");

Route::get('/auth/callback', function (Illuminate\Http\Request $request) {
    $http = new GuzzleHttp\Client;
    // dd($request->input("code"));
    $response = $http->post('http://192.168.1.65:1025/oauth/token', [
        'form_params' => [
            'grant_type' => 'authorization_code',
            'client_id' => '3',
            'client_secret' => 'ijWLwtxSyPkmN2GdzCo2H4GCozsHB1DgicsBa9g1',
            'redirect_uri' => 'http://192.168.1.65:1024/auth/callback',
            'code' => $request->input("code"),
        ],
    ]);

    $accessToken = json_decode((string) $response->getBody(), true)["access_token"];

    $response = $http->request('GET', 'http://192.168.1.65:1025/api/user', [
        'headers' => [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$accessToken,
        ],
    ]);
    // dd($response);
    $userId = json_decode((string) $response->getBody(), true)["id"];
    $user = Auth::guard()->getProvider()->retrieveById($userId);
    if ($user){
        Auth::guard()->login($user);
        session(array("access_token"=>$accessToken));
        return redirect()->intended("/home");
    } else
        throw new RuntimeException('User '.json_decode((string) $response->getBody(), true)["name"]." is not permitted into this application");

    // return json_decode((string) $response->getBody(), true)["name"];
    // return "hello callback";
});
