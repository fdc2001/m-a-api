<?php

namespace App\Http\Controllers;

use App\Models\Configs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use OpenApi\Annotations as OA;

class ConfigsController extends Controller {
    public static function show(Request $request, $field) {
        $validator = Validator::make(['field'=>$field], [
            'field' => ['required', Rule::in('orbit_user','orbit_password','orbit_endpoint')],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        return Configs::where('field', $field)->first();
    }

    public static function update(Request $request, $field) {
        $validator = Validator::make(array_merge($request->all(), ['field' => $field]), [
            'value' => 'required',
            'field' => ['required', Rule::in('orbit_user','orbit_password','orbit_endpoint')],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $config = Configs::where('field', $field)->updateOrCreate(['field' => $field], ['value' => $request->value, 'field' => $field]);
        $config->update($request->all());

        return response()->json($config, 200);
    }

    public static function destroy($field) {
        $config = Configs::where('field', $field)->first();
        $config->delete();
        return response()->json(null, 204);
    }

    public function orbit_test() {

        $orbit_api = Configs::where('field', 'orbit_endpoint')->first();
        if($orbit_api) {
            $orbit_api = $orbit_api->value;
        }
        $user = Configs::where('field', 'orbit_user')->first();
        if($user) {
            $user = $user->value;
        }
        $password = Configs::where('field', 'orbit_password')->first();
        if($password) {
            $password = $password->value;
        }

        try {
            $response = Http::get($orbit_api . 'Login', [
                'Email' => $user,
                'Password' => $password,
            ]);
        } catch (\Exception $e) {
            return response()->json(['message'=>'error'], 400);
        }


        if (!$response->ok()) {
            return response()->json($response->json(), 400);
        }
        if($response['token']===""){
            return response()->json($response->json(), 400);
        }

        return response()->json(['message'=>'success'], 200);
    }

    public static function requestOrbit($method, $url, $data = []) {

        $orbit_api = Configs::where('field', 'orbit_endpoint')->first();
        if($orbit_api) {
            $orbit_api = $orbit_api->value;
        }
        $user = Configs::where('field', 'orbit_user')->first();
        if($user) {
            $user = $user->value;
        }
        $password = Configs::where('field', 'orbit_password')->first();
        if($password) {
            $password = $password->value;
        }

        $loginResponse = Http::get($orbit_api . 'Login', [
            'Email' => $user,
            'Password' => $password,
        ]);

        if (!$loginResponse->ok()) {
            return response()->json($loginResponse->json(), 400);
        }

        $token = $loginResponse['token'];

        $url.= '?token=' . $token.'&user='.$user;
        if($data===[]) {
            $response = Http::$method($orbit_api . $url);
        } else {
            $response = Http::$method($orbit_api . $url, $data);
        }
        if (!$response->ok()) {
            return response()->json($response->json(), 400);
        }

        return $response->json();
    }
}
