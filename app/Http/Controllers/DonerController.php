<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use App\Models\Account;
use App\Models\Doner;
use App\Models\Project;
class DonerController extends Controller
{
   
    public function index(){
        $doners=Doner::with('projects')->latest()->get();
        return response()->json(
            $doners
            ,200);
    }
    public function store(Request $request){
        
        try{
            
              
            $validateDoner = Validator::make($request->all(), 
            [
               'name' => 'string|required',
               'phone' => 'string|required',
               'address' => 'string|required',
               'email'=>'required|string|email|unique:doners',
               'image' => 'file|required|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',
               'account_id' => 'integer|exists:accounts,id',
               
            ]);
            $validateDoner->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
           

            if($validateDoner->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateDoner->errors()->first()
                ], 422);
            }
            if($request->hasFile('image') and $request->file('image')->isValid()){
                $image= $this->store_image($request->file('image')); 
            }

            $Doner = Doner::create(array_merge(
                $validateDoner->validated()
                
                ));
            $account=Account::find($request->account_id);
            $Doner->account()->associate($account);
           
            $Doner->image=$image;
        
            $result=$Doner->save();
           if ($result){
               
                return response()->json(
                    ['status' => true,
                    'message' =>    'تم أضافة بيانات الجهة الداعمة بنجاح',
                    'data'=>$Doner]
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
    public function destroy($id){
        try {  
            
            $input = [ 'id' =>$id ];
            $validate = Validator::make( $input,
                ['id'=>'required|integer|exists:doners,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $Doner=Doner::find($id);
            // return $Doner;
           
          if($Doner){ 
               
                $projects= $Doner->projects()->get();
                
                foreach($projects as $project){
                    $Doner->projects()->detach($project->id);
                    $project->save();
                }
                
                $result= $Doner->delete();
                if($result){ 
                    return response()->json(
                    ['message'=>' تم حذف بيانات الجهة الداعمة بنجاح']
                    , 200);
                }
            }

            return response()->json(null, 422);
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
            
    
            
            $validateDoner = Validator::make($request->all(), [
               'id'=>'required|integer|exists:doners,id',
               'name' => 'nullable|string',
               'phone' => 'nullable|string',
               'address' => 'nullable|string',
               'email'=>'nullable|string|email',
               'image' => 'file|nullable|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',

            ]);
            $validateDoner->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
           
            
            if($validateDoner->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateDoner->errors()
                ], 422);
            }
            $doner=Doner::find($request->id);
            
            if($doner){  
                $doner->update($validateDoner->validated());
                if($request->hasFile('image') and $request->file('image')->isValid()){
                    if($doner->image !=null){
                        $this->deleteImage($doner->image);
                    }
                    $doner->image = $this->store_image($request->file('image')); 
                }
                $doner->save();
                
                return response()->json(
                    ['status' => true,
                    'message' =>    'تم تعديل بيانات الجهة الداعمة بنجاح',
                    'data'=>null]
                 , 200);
                }
      
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
    public function deleteImage( $url){
        // Get the full path to the image
       
        $fullPath =$url;
         
        $parts = explode('/',$fullPath,5);
        $fullPath = public_path($parts[3].'/'.$parts[4]);
        
        // Check if the image file exists and delete it
        if (file_exists($fullPath)) {
            unlink($fullPath);
            
            return true;
         }
         else return false;
    }
    public function store_image( $file){
        $extension = $file->getClientOriginalExtension();
           
        $imageName = uniqid() . '.' .$extension;
        $file->move(public_path('doner'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('doner/' . $imageName);
                
         
       
       return $imagePath;

    }
    public function attach_doner_to_project(Request $request) {

        try {  
            
 
            $validate = Validator::make( $request->all(),
            ['doner_id'=>'required|integer|exists:doners,id',
            'project_id'=>'required|integer|exists:projects,id'
            ]);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $Doner=Doner::find($request->doner_id);
            $project=Project::find($request->project_id);
            // return $Doner;
           $projects= $Doner->projects;
          foreach($projects as $pro){
            if($project->id==$pro->id){
                return response()->json([
                    'status'=>false,
                    'message'=>'هذه الجهة تدعم المشروع مسبقا !!',
                    'errors'=>''
                ], 422); 
            }
          }
          if($Doner and $project){ 
           $Doner->projects()->attach( $project);
            
            return response()->json(
                ['message'=>' تم أضافة المشروع الى الجهة الداعمة بنجاح']
                , 200);
             
                   
                
            }

            return response()->json(null, 422);
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' => 'An error occurred while deleting the D.'], 500);
        } 
       
        
        
    }
    public function search(Request $request){
        try {
                
            $input = [ 'search' =>$request->search ];
               
            $validatesearch = Validator::make($input, 
            [ 'search' => 'required|string|min:3' ]); 
                
            if($validatesearch->fails()  ){
                    return response()->json([
                        'status' => false,
                         'message' => 'خطأ في التحقق',
                        'errors' => $validatesearch->errors()
                    ], 422);
                    }
          
            $data = doner::with('projects')->where('name','LIKE', '%' . $request->search .'%')
                ->orwhere('phone','LIKE', '%' . $request->search .'%')
                ->orwhere('email','LIKE', '%' . $request->search .'%')
                ->orwhere('address','LIKE', '%' . $request->search .'%')->get();      
              
            
            if(count($data)>0)
            {
                $result=array();
                
                foreach($data as $doner){
                    
                    if(! in_array($doner,$result)  ){
                        array_push($result , $doner);
                        
                    }
                }
                
                if ($result)
                { return response()->json(
                            
                    $result
                    , 200);  
                }
            }
            else{
                return response()->json([],204); 
                }
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' => 'An error  occurred while requesting this Product.'], 500);
        }

    }
    public function show(Request $request) {
       
        try {  
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:doners,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
            $doner=doner::with('projects')->find($request->id);
            $account=Account::find($doner->account_id);
            $doner->cart=$account->cart;
          if($doner){ 
            return response()->json(
                $doner
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
}