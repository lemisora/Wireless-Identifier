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
        $card = Card::where('uid', $validated['uid'])->with('user')->first();
        
        if ($card && $card->user) {
                // Si la tarjeta y el usuario existen ---
                return response()->json([
                    'status' => 'success',
                    'message' => 'Acceso Permitido',
                    'user_name' => $card->user->name,
                ]); // Por defecto, esto envía un código 200 OK, lo cual es correcto.
        
            } else {
                // Si la tarjeta no fue encontrada o sin usuario ---
                $errorMessage = $card ? 'no tiene usuario asociado' : 'no está registrada';
                
                // DEVOLVEMOS UN CÓDIGO 404 NOT FOUND
                return response()->json([
                    'status' => 'error',
                    'message' => 'La tarjeta ' . $errorMessage,
                ], 404);
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
    
            $previousUser = User::where('card_id', $card->id)
                                    ->where('id', '!=', $validated['user_id'])
                                    ->first();
            
            $reassignedMessage = "";
            if ($previousUser) {
                // Si se encontró un usuario anterior, se le quita la tarjeta.
                $previousUser->card_id = null;
                $previousUser->save();
                $reassignedMessage = " La tarjeta ha sido reasignada desde el usuario '{$previousUser->name}'.";
            }
            
            // Asigna la tarjeta al nuevo usuario.
            $newUser = User::find($validated['user_id']);
            $newUser->card_id = $card->id;
            $newUser->save();
            
            return response()->json([
                'status' => 'success',
                'message' => "Tarjeta {$card->uid} asignada a {$newUser->name}." . $reassignedMessage,
            ]);
        }
}
