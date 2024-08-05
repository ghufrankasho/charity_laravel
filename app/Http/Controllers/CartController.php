<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\cart;
use App\Models\Account;
use App\Models\Project;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class CartController extends Controller
{
    public function index(Request $request){
        $carts=cart::find($request->id);
         
      
        return response()->json(
            $carts
            ,200);
    }
    public function show(Request $request) {
       
        try {  
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:carts,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
        $cart=cart::find($request->id);
          if($cart){ 
            return response()->json(
                $cart->projects
                 , 200);
            } 
                 
              
               
               
            

            return response()->json(['message'=>" حدث خطأ أثناء عملية جلب البيانات "], 422);
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' =>$e
            //  'حدث خطأ أثناء عملية جلب البيانات'
            ], 
             500);
        }
    }
    public function store(Request $request){
        
        try{
            
              
            $validatecart = Validator::make($request->all(), 
            [
                'account_id' => 'integer|required|exists:accounts,id',
                'project_id' => 'integer|required|exists:projects,id',
            ]);
           
            if($validatecart->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validatecart->errors()
                ], 422);
            }
            $account=Account::find($request->account_id);
            if( $account){
               $cart= $account->cart;
          
            }
            else{  $cart = cart::create(array_merge(
                $validatecart->validated()
                
                ));
          
            $account=account::find($request->account_id);
            $cart->account()->associate($account);
            $result=$cart->save();
        }
        if($cart){
                
            $project=Project::find($request->project_id);
             
           $projects= $cart->projects;
          
          foreach($projects as $pro){
            if($project->id==$pro->id){
                return response()->json([
                    'status'=>false,
                    'message'=>' هذا المشروع موجد مسبقا في السلة !!',
                    'data'=>$cart
                     
                ], 422); 
            }
          }
          if($project){ 
           $cart->projects()->attach( $project);
            
            return response()->json(
                [ 'status'=>true, 
                    'message'=>' تم أضافة المشروع الى السلة بنجاح',
                    'data'=>$cart]
                , 200);
             
                   
                
            }
           }
           

        }
        catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'message' =>  $th->getMessage(),
                // "حدث خطأ أثناء أضافة البيانات"
            ], 500);
        }
       
        
    }
    public function dettach(Request $request){
        try {  
       
           
            $validate = Validator::make( $request->all(),
                ['cart_id'=>'required|integer|exists:carts,id',
                 'project_id'=>'required|integer|exists:projects,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $cart=cart::find($request->cart_id);
          
            $project=Project::find($request->project_id);
           
          if($cart && $project){ 
            
            $projects= $cart->projects;
           
            foreach($projects as $pro){
              if($request->project_id==$pro->id){
                
                $project= $cart->projects()->detach($project);
                 
                $result=$cart->save();
                 
                
                if($result){ 
                    return response()->json(
                    [   'status'=>true,
                        'message'=>' تم حذف بنجاح',
                        'data'=>$result]
                    , 200);
                }
              }
            }  
            }

            
             return response()->json(
                    ['status' => false,
                    'message' =>'حدث خطأ أثناء أضافة البيانات',
                    'data'=>null],
                    422);
              
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the D.'], 500);
        }
    }
    public function update(Request $request){
        try{
            
    
            
            $validatecart = Validator::make($request->all(), [
               'id'=>'required|integer|exists:carts,id',
               'name' => 'nullable|string|unique:carts'
            ]);
           
           
            
            if($validatecart->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validatecart->errors()
                ], 422);
            }
            $cart=cart::find($request->id);
            
            if($cart){  
                $cart->update($validatecart->validated());
                $cart->save();
                }
              
                
                return response()->json(
                    ['status' => true,
                    'message' =>    'تم تعديل بنجاح',
                    'data'=>null]
                 , 200);
                
      
            return response()->json([
                'status' => false,
                'message' =>  'فشلت عملية التعديل ',
                'data'=> null
                ], 422);
            

        }
        catch (\Throwable $th) {
            return response()->json([
            'status' => false,
            'message' => $th->getMessage()
            ], 500);
        }
      
        
    }
    // public function attach_cart_to_project($cart,$project) {

    //     try {  
            
 
            
          
           
    //         // return $Doner;
          

           
        
    //     catch (ValidationException $e) {
    //         return response()->json(['errors' => $e->errors()], 422);
    //     } 
    //     catch (\Exception $e) {
    //         return response()->json(['message' => 'An error occurred while deleting the D.'], 500);
    //     } 
       
        
        
    // }
}