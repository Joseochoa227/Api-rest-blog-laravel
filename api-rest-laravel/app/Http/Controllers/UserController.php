<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;
class UserController extends Controller
{
    public function pruebas(Request $request){
    	return 'Accion de pruebas de user controller';
    }

    public function register(Request $request){
    	

    	//Recoger los datos del usuario con post
    	$json = $request->input('json',null);
    	//Decodificar el json (Separar todas las variables que trae mi json)
    	$params = json_decode($json); //Obtengo un objeto (Transformo mi json en un objeto)
    	$params_array = json_decode($json, true); //Obtengo un Array Transformo mi json en un array)


    	if(!empty($params_array && !empty($params))){

	    	//Limpiar datos
	    	$params_array = array_map('trim', $params_array);
	    	//Validar datos
	    	$validate = \Validator::make($params_array, [
	    		'name' 		=> 'required|alpha',
	    		'surname' 	=> 'required|alpha',
	    		'email' 	=> 'required|email|unique:users',//Comprobar si el usuario existe ya (Duplicado)
	    		'password'	=> 'required'
	    	]);

	    	if($validate->fails()){
	    		//La validacion ha fallado
	    		$data = array(
		    		'status'  => 'error',
		    		'code'	  => '404',
		    		'message' => 'El usuario no se ha creado',
		    		'errors'  => $validate->errors()
	    		);
	    	}else{

	    		//Validacion pasada correctamente
			    //Cifrar contraseña
	    		$pwd = hash('sha256', $params->password);

		    	
		    	//Crear el usuario

	    		$user = new User();
	    		$user->name= $params_array['name'];
	    		$user->surname= $params_array['surname'];
	    		$user->email= $params_array['email'];
	    		$user->password= $pwd;
	    		$user->role = 'ROLE_USER';

	    		//Guardar el usuario

	    		$user->save(); 
	    		$data = array(
		    		'status'  => 'success',
		    		'code'	  => '200',
		    		'message' => 'El usuario se ha creado correctamente',
		    		'user'	  => $user
	    		);
	    	}
    	}else{
    		$data = array(
		    		'status'  => 'error',
		    		'code'	  => '404',
		    		'message' => 'Los datos enviados no son correctos'
	    		);
    	}

    	return response()->json($data, $data['code']);
    }
    public function login(Request $request ){

    	$jwtAuth = new \JwtAuth();

    	//Recibir datos por POST

    	$json = $request->input('json', null);
    	$params = json_decode($json);
    	$params_array = json_decode($json, true);
    	//Validar esos datos
    	$validate = \Validator::make($params_array, [
	    		
	    		'email' 	=> 'required|email',
	    		'password'	=> 'required'
	    	]);

	    	if($validate->fails()){
	    		//La validacion ha fallado
	    		$signup = array(
		    		'status'  => 'error',
		    		'code'	  => '404',
		    		'message' => 'El usuario no se ha podido identificar',
		    		'errors'  => $validate->errors()
	    		);
	    	}else{
		    	//Cifrar contraseña

		    	$pwd = hash('sha256', $params->password);
		    	//Devolver token o datos
		    	$signup = $jwtAuth->signup($params->email, $pwd);
		    	if(isset($params->getToken)){
		    		$signup = $jwtAuth->signup($params->email, $pwd, true);
		    	}
	    	}
    	return response()->json($signup,200);
    }

    public function update(Request $request){
    	//Comprobar si el usuario esta identificado
    	$token=$request->header('Authorization');
    	$jwtAuth = new \JwtAuth();
    	$checkToken = $jwtAuth->checkToken($token);
		
    	//Actualizar el usuario!!!
		//Recoger los datos por post
		$json = $request->input('json', null);
		$params_array = json_decode($json, true);
		
		if($checkToken && !empty($params_array)){


    		//Sacar usuario identificado
    		$user = $jwtAuth->checkToken($token, true);
    		//Validar los datos

    		$validate = \Validator::make($params_array, [
    			'name' 		=> 'required|alpha',
	    		'surname' 	=> 'required|alpha',
	    		'email' 	=> 'required|email|unique:users,'.$user->sub,//Comprobar si el usuario existe ya (Duplicado)
    		]);
    		//Quitar los campos que no quiero actualizar

    		unset($params_array['id']);
    		unset($params_array['role']);
    		unset($params_array['password']);
    		unset($params_array['created_at']);
    		unset($params_array['remenber_token']);

    		//Actualizar el usuario
    		$user_update = User::where('id', $user->sub)->update($params_array);

    		//Devolver array con el resultado
    		$data = array(
		    		'status'  => 'success',
		    		'code'	  => '200',
		    		'user' 	  => $user,
		    		'change'  => $params_array
	    	);

    	}else{
    		$data = array(
		    		'status'  => 'error',
		    		'code'	  => '404',
		    		'message' => 'El usuario no se ha podido identificar'
	    	);
    	}
    	
    	return response()->json($data, $data['code']);

    }

    public function upload(Request $request){


    	//Recoger los datos de la peticion
    	$image = $request->file('file0');

    	//Validacion de la imagen


    	$validate = \Validator::make($request->all(),[
    		'file0'		=> 'required|image|mimes:jpg,jpeg,png,gif'
    	]);

    	//Guardar la imagen
    	if(!$image || $validate->fails()){
    		$data = array(
			    		'status'  => 'error',
			    		'code'	  => '404',
			    		'message' => 'Error al subir imagen'
		    	);
    	}else{
    		$image_name = time().$image->getClientOriginalName();
    		\Storage::disk('users')->put($image_name, \File::get($image));

    		$data = array(
    				'status'=> 'success',
    				'code'  => '200',
    				'image' => $image_name
    		);
    	}


    	return response()->json($data, $data['code']);
    }

    public function getImage($filename){
    	$isset = \Storage::disk('users')->exists($filename);

    	if($isset){

	    	$file = \Storage::disk('users')->get($filename);
	    	return new Response($file, 200);
    	}else{
    		$data = array(
    				'status'=> 'Error',
    				'code'  => '404',
    				'message' => 'La imagen no existe'
    		);
    	}

    	return response()->json($data, $data['code']);
    }

    public function detail($id){
    	$user = User::find($id);

    	if(is_object($user)){
    		$data= array(
    			'code' 		=> 200,
    			'status'	=> 'success',
    			'user' 		=> $user
    		);
    	}else{
    		$data= array(
    			'code' 		=> 404,
    			'status'	=> 'error',
    			'message' 	=> 'El usuario no existe'
    		);
    	}
    	return response()->json($data, $data['code']);
    }

    
}
