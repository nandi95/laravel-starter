<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display the specified resource.
     *
     * @param Request $request
     *
     * @return UserResource
     */
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user());
    }
}
