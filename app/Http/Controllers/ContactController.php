<?php

namespace App\Http\Controllers;
use App\Models\Contact; 
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;


class ContactController extends Controller
{
    public function index(){
    
        $contacts=contact::latest()->get();
        
        
        return response()->json(
            $contacts
            ,200);
    }
    public function views(){
    
        $contacts=contact::latest()->take(10)->get();
        
        
        return response()->json(
            $contacts
            ,200);
    }
    public function store(Request $request){
        
        try{
            
              
            $validateacontact = Validator::make($request->all(), 
            [
                'last_name' => 'string|required',
                'first_name' => 'string|required',
                'message' => 'nullable|required|string',
                'mobile' => 'string|required',
                'email'=>'required|string|email|unique:contacts',
                
            ]);
            

            if($validateacontact->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateacontact->errors()->first()
                ], 422);
            }

            $contact = contact::create(array_merge(
                $validateacontact->validated()
                
                ));

            
           if ($contact){
               
            return response()->json(
                ['status' => true,
                'message' =>    'تم أضافة البيانات  بنجاح',
                'data'=>$contact]
             , 201);
            }
       else{
            return response()->json(
                ['status' => false,
                'message' =>'حدث خطأ أثناء أضافة البيانات',
                'data'=>null],
                422);
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
    public function destroy(Request $request){
        try {  
             
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:contacts,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
          
            $contact=contact::find($request->id);
         
           
          if($contact){ 
                 
            $result= $contact->delete();
            if($result){ 
                return response()->json(
                ['status'=>true,
                'message'=>    ' تم حذف البيانات  بنجاح',
                    
                ]
                , 200);
            }
            }

            return response()->json(null, 422);
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the contact.'], 500);
        }
    }
}