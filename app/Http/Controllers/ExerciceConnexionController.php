<?php

namespace App\Http\Controllers;

use App\Models\ExerciceCompte;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ExerciceConnexionController extends Controller
{
    public function formulaire(): View
    {
        return view('exercice.connexion');
    }

    public function connecter(Request $request)
    {

        $donne = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $compte = ExerciceCompte::where('email', $donne['email'])->first();

        // On verifie si le compte n'existe
        if (! $compte) {
            return redirect()->route('exercice.connexion')->with('erreur', 'Email invalide');
        }
        if (Hash::check($donne['password'], $compte->password)) {
            $request->session()->put('user_id', $compte->id);
            return redirect()->route('exercice.accueil');
        } else {
            return redirect()->route('exercice.connexion')->with('erreur', 'Email invalide');
        }

    }
    public function rechercher(Request $request)
    {
        $donne = $request->validate([
            'email'=>['required','email'],
        ]);
        $compte = ExerciceCompte::where('email',$donne['email'])->first();
        if ($compte){
            return $compte;
        }
        else{
            return with('error', 'Compte introuvable ou inexistant');
        }
    }

    public function accueil(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('user_id')) {
            return redirect()->route('exercice.connexion')
                ->with('erreur', 'Utilisateur non connecté');
        }
        $userId = $request->session()->get('user_id');
        $user = ExerciceCompte::find($userId);
        if (! $user) {
            $request->session()->forget('user_id');

            return redirect()->route('exercice.connexion')
                ->with('erreur', 'Utilisateur introuvable');
        }

        return view('exercice.accueil', ['user' => $user]);
    }
    public function deconnecter (Request $request): RedirectResponse
    {
        $request->session()->forget('user_id');

        return redirect()->route('exercice.connexion')
            ->with('message', 'Déconnexion réussie');
    }
}
