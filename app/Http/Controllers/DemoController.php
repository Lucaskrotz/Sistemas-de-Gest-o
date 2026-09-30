<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class DemoController extends Controller
{
    /** Loga o usuário demo do módulo ({modulo}@demo.test) e abre o módulo. */
    public function __invoke(Request $request, string $modulo)
    {
        abort_unless(config("modulos.{$modulo}"), 404);

        Auth::login(User::where('email', "{$modulo}@demo.test")->firstOrFail());
        $request->session()->regenerate();

        return Route::has("{$modulo}.index")
            ? redirect()->route("{$modulo}.index")
            : redirect()->route('home')->with('error', 'Este módulo ainda está em construção.');
    }
}
