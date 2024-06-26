<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Account;
use App\Models\Work;
use App\Models\Project;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(){
        $users=User::latest()->get();
         
        foreach($users as $user){
            $project=$user->project()->first('name');
            
            $work=$user->work()->first('name');
           if($work){ $user->work_id=$work->name;}
           if($project)
            {$user->project_id=$project->name;}
        }
        return response()->json(
            $users
            ,200);
    }
    public function get(){
        
        $work=Work::where('name','متطوع')->first();
        if($work)
        {
            $users=User::where([['work_id',$work->id],['accept',false]])->latest()->get();
         }
        else{$users=User::latest()->get();}
        $users[0]->number=count($users);
        return response()->json(
            $users
            ,200);
    }
    public function store(Request $request){
        
        try{
            
              
            $validateauser = Validator::make($request->all(), 
            [
               'last_name' => 'string|required',
               'first_name' => 'string|required',
                'address' => 'nullable|string',
                'mobile' => 'string|required',
                'account_id' => 'integer|exists:accounts,id',
                'work_id' => 'nullable|integer|exists:works,id',
                'project_id' => 'nullable|integer|exists:projects,id',
                'cv' => "nullable|file|mimetypes:application/pdf,application/txt|max:10000",
                'image' => 'file|required|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',

            ]);
            $validateauser->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });

            if($validateauser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateauser->errors()
                ], 422);
            }

           
             
                
            if($request->hasFile('image') and $request->file('image')->isValid()){
                $image= $this->store_image($request->file('image')); 
            }
            if($request->hasFile('cv') and $request->file('cv')->isValid()){
                $cv= $this->store_cv($request->file('cv')); 
            }
            
            $user=User::where('account_id',$request->account_id)->first();
            if($user){
                
                return response()->json(  
                    ['status' => false,
                    'message' =>'حدث خطأ أثناء أضافة البيانات',
                    'data'=>null], 422);
                
            }
            $user = User::create(array_merge(
                $validateauser->validated()
                
                ));
            $user->image=$image;
            $user->cv=$cv;
            $account=Account::find($request->account_id);
            $user->account()->associate($account);
            $work=work::find($request->work_id);
            $user->work()->associate($work);
          
            $result=$user->save();
           if ($result){
               
            return response()->json(
                ['status' => true,
                'message' =>    'تم أضافة بيانات البروفايل بنجاح',
                'data'=>$user]
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
                ['id'=>'required|integer|exists:users,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $user=User::find($request->id);
         
           
          if($user){ 
                if($user->image!=null){
                    $this->deleteImage($user->image);
                } 
                if($user->cv!=null){
                    $this->deleteCV($user->cv);
                } 
                //dissociate user from work
                $work=$user->work()->first();
                if($work){
                    $user->work()->dissociate($work);
                    }
                 //dissociate user from project    
                $project=$user->project()->first();
                if($project){
                        $user->project()->dissociate($project);
                        }
                 //dissociate user from account    
                 $account=$user->account()->first();
                 
                 if($account){
                        $user->account()->dissociate( $account);
                        $account->delete();
                        }    
           
                
                 
                $result= $user->delete();
            if($result){ 
                return response()->json(
                ['status'=>true,
                'message'=>    ' تم حذف بيانات المستخدم بنجاح',
                    
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
            return response()->json(['message' => 'An error occurred while deleting the user.'], 500);
        }
    }
    public function update(Request $request, $id){
        try{
            $input = [ 'id' =>$id ];
            $validate = Validator::make( $input,
            ['id'=>'required|integer|exists:users,id']);
            if($validate->fails()){
                    return response()->json([
                        'status' => false,
                        'message' => 'خطأ في التحقق',
                        'errors' => $validate->errors()
                    ], 422);
                }
                
            $user=User::find($id);
            
            $validateuser = Validator::make($request->all(), [
                'last_name' => 'string',
                'first_name' => 'string',
                'address' => 'string|nullable',
                'mobile' => 'string|nullable',
                'account_id' => 'nullable|integer|exists:accounts,id',
                'work_id' => 'nullable|integer|exists:works,id',
                // 'project_id' => 'nullable|integer|exists:projects,id',
                'image' => 'file|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',
                ]);
            $validateuser->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
            
            if($validateuser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateuser->errors()
                ], 422);
            }
            if($user){  
                $user->update($validateuser->validated());
                if($request->hasFile('image') and $request->file('image')->isValid()){
                    if($user->image !=null){
                        $this->deleteImage($user->image);
                    }
                    $user->image = $this->store_image($request->file('image')); 
                }
                if($request->work_id != null){

                    $work=Work::find($request->work_id);
                    $user->work()->associate($work);
                }
                // if($request->project_id != null){

                //     $project=Project::find($request->project_id);
                //     $user->project()->associate($project);
                // }
               
                
                $user->save();
                
                return response()->json(
                    'تم تعديل بيانات المستخدم بنجاح'
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
        $file->move(public_path('users'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('users/' . $imageName);
                
         
       
       return $imagePath;

    }
    public function store_cv( $file){
        $userxtension = $file->getClientOriginalExtension();
           
        $imageName = uniqid() . '.' .$userxtension;
        $file->move(public_path('user_cv'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('user_cv/' . $imageName);
                
         
       
       return $imagePath;

    }
    public function deleteCV( $url){
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
    public function show(Request $request){
        try {  
            
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:users,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
            $user=user:: find($request->id);
           
         
          if($user){ 
            return response()->json(
                $user
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
    public function accept(Request $request){
        try {  
            
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:users,id',
                'accept'=>'required|bool']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
            $user=user:: find($request->id);
            $user->accept=$request->accept;
            $user->save();
            if(! $request->accept)$this->destroy($request);
           
         
          if($user){ 
            return response()->json(
                ['status' => true,
                'message' =>    'تم العملية  بنجاح',
                'data'=>$user]
                 , 200);
            } 
                 

            return response()->json(
                 ['status' => false,
                'message' =>  " حدث خطأ أثناء عملية جلب البيانات ",
                'data'=>null]
                 , 422);
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
    public function set_account_type(Request $request){
        try {  
             
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:accounts,id',
                'type'=>'required|in:0,2,3,4']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $account=Account::find($request->id);
         
           
          if($account){ 
            
            $account->type=  $request->type; 
            $result=$account->save();
             
            if($result){ 
                return response()->json(
                    ['status' => true,
                    'message' => ' تمت العملية بنجاح',
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
            return response()->json(['message' => 'An error occurred while deleting the account.'], 500);
        }
    }
    public function search_account(Request $request){
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
          
            $data = Account::where(
                // 'name','LIKE', '%' . $request->search .'%')
                // ->orwhere('email','LIKE', '%' . $request->search .'%')
                 'email','LIKE', '%' . $request->search .'%')
                -> latest()->get();      
              
            
            if(count($data)>0)
            {
                $result=array();
                 
                foreach($data as $Account){
                    
                    if(! in_array($Account,$result)  ){
                        $date=  new DateTime($Account->created_at);
                        $Account->date=$date->format('y-m-d');
                        array_push($result , $Account);
                        
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
    public function search($search){
        try {
                
            $input = [ 'search' =>$search ];
               
            $validatesearch = Validator::make($input, 
            [ 'search' => 'required|string|min:3' ]); 
                
            if($validatesearch->fails()  ){
                    return response()->json([
                        'status' => false,
                         'message' => 'خطأ في التحقق',
                        'errors' => $validatesearch->errors()
                    ], 422);
                    }
          
            $data = user::where('first_name','LIKE', '%' . $search .'%')
                ->orwhere('last_name','LIKE', '%' . $search .'%')
                ->orwhere('mobile','LIKE', '%' . $search .'%')
                ->orwhere('address','LIKE', '%' . $search .'%')->get();      
              
            
            if(count($data)>0)
            {
                $result=array();
                
                foreach($data as $user){
                    
                    if(! in_array($user,$result)  ){
                        array_push($result , $user);
                        
                    }
                    foreach($result as $user){
                        $project=$user->project()->first('name');
                        $work=$user->work()->first('name');
                        $user->work_id=$work->name;
                        $user->project_id=$project->name;
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
            return response()->json(['message' => $e,
            'An error  occurred while requesting this Product.'], 500);
        }

    }
}