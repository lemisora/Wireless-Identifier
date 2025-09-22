<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

use App\Models\Card;
use App\Models\User;

class AccessController extends Controller
{
    // Valir que exista un UID en la petición
    public function logAccess(Request $request) : JsonResponse{
        $validated = $request->validate([
            'uid' => 'required|string|max:100',
        ]);
        
        $uid = $validated['uid'];
        
        // Acceder a la base de datos
        $card = Card::where('uid', $uid)->with('user')->first();
        
        if($card && $card->user){
            return response()->json([
                'status' => 'success',
                'message' => 'Acceso permitido',
            ]);
        }else{
            return response()->json([
                'status' => 'denied',
                'message' => 'Acceso denegado: sin usuario asociado',
            ]);
        }
    }
    
    /**
         * Crea un nuevo usuario en la base de datos.
         */
    public function storeUser(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:users,name',
            ]);
    
            $user = User::create([
                'name' => $validated['name'],
                // Genera una contraseña aleatoria de 10 caracteres y la hashea
                'password' => Hash::make(Str::random(10)), 
            ]);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Usuario creado exitosamente.',
                'user' => $user, // Devolvemos el usuario creado (incluyendo su ID)
            ], 201); // Código 201: Created
        }
    
        /**
         * Crea o encuentra una tarjeta por su UID y la asocia a un usuario.
         */
        public function assignCard(Request $request)
        {
            $validated = $request->validate([
                'uid' => 'required|string|max:25',
                'user_id' => 'required|integer|exists:users,id',
            ]);
    
            // Busca la tarjeta por UID, y si no existe, la crea.
            $card = Card::firstOrCreate(['uid' => $validated['uid']]);
    
            // Busca al usuario
            $user = User::find($validated['user_id']);
    
            // Asocia la tarjeta con el usuario y guarda
            $user->card_id = $card->id;
            $user->save();
    
            return response()->json([
                'status' => 'success',
                'message' => "Tarjeta {$card->uid} asignada a {$user->name} exitosamente.",
            ]);
        }
}
