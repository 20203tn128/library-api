<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //
    public function register(Request $request)
    {
      $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required|confirmed'
    ]);
        if (!$validator->fails()) {
            DB::beginTransaction();
            try {
                //Set data
                $user = new User();
                $user->name = $request->name;
                $user->email = $request->email;
                $user->password = Hash::make($request->password); //encrypt password
                $user->save();
                DB::commit();
                return $this->getResponse201('user account', 'created', $user);
            } catch (Exception $e) {
                DB::rollBack();
                return $this->getResponse500([$e->getMessage()]);
            }
        } else {
            return $this->getResponse500([$validator->errors()]);
        }
    }

    public function login(Request $request){
      $validator =Validator::make($request->all(),[
        'email'=>'required|email',
        'password'=>'required'
      ]);
      if(!$validator->fails()){
        $user = User::where('email','=',$request->email)->first();
        if(isset($user->id)){
            if(Hash::check($request->password,$user->password)){
                $token =$user->createToken('auth-token')->plainTextToken;
                return response()->json([
                    'message'=>"Successful authentication",
                    'access_token'=>$token,
                ],200);
            }else{
                return $this->getResponse401();
            }
        }else{
            return $this->getResponse401();
        }
      }else{
        return $this->getResponse500([$validator->errors()]);
      }
    }

    public function userProfile(){
    return $this->getResponse200(auth()->user());
    }

    public function logout(Request $request){
     $request->user()->currentAccessTokens()->delete();
     return response()->json([
        'message'=>"Logout successful"
     ],200);
    }

    public function changePassword(Request $request ){
      $validator =Validator::make($request->all(),[
        'password'=>'required|confirmed',
      ]);
      if (!$validator->fails()) {
        DB::beginTransaction();
        $user= $request->user();
        try {
            $user->password = Hash::make($request->password); //encrypt password
            $user->tokens()->delete();
            $user->save();
            DB::commit();
            return response()->json([
                'message'=>"Your password has been successfully updated!"
            ],201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    } else {
        return $this->getResponse500([$validator->errors()]);
    }
      
    }
}
