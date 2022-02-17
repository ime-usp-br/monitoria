<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\Hash;
use Uspdev\Replicado\Pessoa;
use Auth;
use Illuminate\Support\Facades\Gate;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(!Gate::allows('editar usuario')){
            abort(403);
        }

        $usuarios = User::orderBy('name')->get();

        return view('usuarios.index', compact('usuarios'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if(!Gate::allows('editar usuario')){
            abort(403);
        }

        $usuario = User::find($id);
        $roles = Role::all();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(UserRequest $request, $id)
    {
        if(!Gate::allows('editar usuario')){
            abort(403);
        }

        $validated = $request->validated();
        $usuario = User::find($id);
        $usuario->roles()->detach();
        $usuario->assignRole($validated['roles']);
        $usuario->update($validated);
        return redirect('/usuarios');
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
