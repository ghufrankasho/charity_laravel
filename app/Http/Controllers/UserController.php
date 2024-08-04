<?php

namespace App\Http\Controllers;

use App\Mail\Notification;
use App\Models\User;
use App\Models\Account;
use App\Models\Donation;
use App\Models\Work;
use App\Models\Project;
use App\Models\Employee; 
use DateTime;
// use Laravel\Cashier\Billable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

// use function PHPSTORM_META\type;

class UserController extends Controller
{
    // use Billable;
    public function index(){
    
        $users=User::latest()->get();
       
        foreach($users as $user){
            
            $work=$user->work()->first('name');
           if($work){ $user->work_id=$work->name;}
          
        }
        return response()->json(
            $users
            ,200);
    }
    public function requests(){
      try{
        $work=Work::where('name','متطوع')->first();
        $orphen=Work::where('name','يتيم')->first();
        $donations=Donation::sum('amount');
        
        $num_orphen=0;
        if($orphen){
            $orphens=User::where('work_id',$orphen->id)->latest()->get();
            $num_orphen=count($orphens);
        }
        if($work)
        {
            $users=User::where([['work_id',$work->id],['accept',false]])->latest()->get();
         
        }
        else{
            $users=User::latest()->get();
        }
         
        $employees=Employee::where('employed',false)->latest()->get();
         
        $request=array();
        foreach( $employees as $employee){
            $employee['is_user']=false;
            $date=  new DateTime($employee->created_at);
            $employee->date= $date->format('Y-m-d');
            array_push($request, $employee); 
        }
        foreach( $users as $user){
            $user['is_user']=true;
            $date=  new DateTime($user->created_at);
            $user->date= $date->format('Y-m-d');
            array_push($request, $user); 
        }
        $request[0]->orphen=$num_orphen;
        $request[0]->donations= $donations;
        return response()->json(
            $request
            ,200);
      }   catch (\Throwable $th) {
        return response()->json([
            'status' => false,
            'message' =>  $th->getMessage(),
            // "حدث خطأ أثناء أضافة البيانات"
        ], 500);
    }    
    }
    public function get(){
        
        $work=Work::where('name','متطوع')->first();
        if($work)
        {
            $users=User::where([['work_id',$work->id],['accept',false]])->latest()->get();
         if($users){
                foreach($users as $user){
                    $user->number=count($users); 
                    break; 
                }
                
                return response()->json(
                    $users
                    ,200);
            }
        }
        else{
            return response()->json(
                []
                ,200);
        }
        
        
    }
    public function get_accepted_volunter(){
        
        $work=Work::where('name','متطوع')->first();
        if($work)
        {
            $users=User::where([['work_id',$work->id],['accept',True],['project_id',null]])->latest()->get();
         if(count($users)!=0 and $users){
                
                
                return response()->json(
                    $users
                    ,200);
            }
            else{
                return response()->json(
                    ['status'=>false,
                    'message'=>"there no volunters",
                    'errors'=>'there no volunters']
                    ,422);
            }
        }
        else{
            return response()->json(
                ['status'=>false,
                'message'=>"there no volunters",
                'errors'=>'there no volunters']
                ,422);
        }
        
        
    }
    public function store(Request $request){
        
        try{
            
              
            $validateauser = Validator::make($request->all(), 
            [
               'last_name' => 'string|required',
               'first_name' => 'string|required',
                'address' => 'nullable|string',
                'mobile' => 'string|required',
                // 'email'=>'required|string|email',
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
                    'errors' => $validateauser->errors()->first()
                ], 422);
            }
            if($request->hasFile('image') and $request->file('image')->isValid()){
                $image= $this->store_image($request->file('image')); 
            }
            if($request->hasFile('cv') and $request->file('cv')->isValid()){
                $cv= $this->store_cv($request->file('cv')); 
            }
            
            if($request->account_id != null)
            {$user=User::where('account_id',$request->account_id)->first();
            
            if($user){
                
                return response()->json(  
                    ['status' => false,
                    'message' =>'!!لقد قمت تسجيل بياناتك من قبل',
                    'data'=>null], 422);
                
            }}
            $user = User::create(array_merge(
                $validateauser->validated()
                , 
                ));
             $user->image=$image;
            if($request->account_id != null)  {
                $account=Account::find($request->account_id);
                $user->account()->associate($account);
            }
            $work=work::find($request->work_id);
            $user->work()->associate($work);
            
            if($request->work_id==5)$user->cv=$cv;
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
               'errors' => $validate->errors()->first()
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
    public function update(Request $request){
        try{
           
           
            
            $validateuser = Validator::make($request->all(), [
                'id'=>'required|integer|exists:users,id',
                'last_name' => 'string',
                'first_name' => 'string',
                'address' => 'string|nullable',
                'mobile' => 'string|nullable',
                'salary' => 'nullable|integer|min:0|max:1000000',
                'account_id' => 'nullable|integer|exists:accounts,id',
                'work_id' => 'nullable|integer|exists:works,id',
                 
                'image' => 'file|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',
                ]);
            $validateuser->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
            
            if($validateuser->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateuser->errors()->first()
                ], 422);
            }
            $user=User::find($request->id);
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
                ['status'=>true,
                'message'=> 'تم تعديل بيانات المستخدم بنجاح',
                'data'=>$user]
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
               'errors' => $validate->errors()->first()
            ], 422);}
        //   branch relishen shipe
        
            $user=user::with('work','project','account')-> find($request->id);
           
         
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
    public function get_user_project(Request $request){
        try {  
            
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:users,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
        //   branch relishen shipe
        
            $user=user::find($request->id);
           
            $project=$user->project;
          if($project){ 
            return response()->json(
                $project
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
               'errors' => $validate->errors()->first()
            ], 422);}
        //   branch relishen shipe
        
            $user=user:: find($request->id);
            $user->accept=$request->accept;
            $user->save();
            if(! $request->accept){
                
               return $this->destroy($request);
            }
           
         
        
            return   $this->sendEmail($user->email);
          
                 

           
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
    public function search(Request $request){
        try {
                
           
           
            $validatesearch = Validator::make($request->all(), 
            [ 'search' => 'required|string|min:3' ,
              'is_user'=>  'required|boolean' 
            ]); 
           
            if($validatesearch->fails()  ){
                    return response()->json([
                        'status' => false,
                         'message' => 'خطأ في التحقق',
                        'errors' => $validatesearch->errors()->first()
                    ], 422);
            }
          
            if(($request->is_user ==null ) or  $request->is_user)
           { 
            $data = user::where('first_name','LIKE', '%' . $request->search .'%')
                ->orwhere('last_name','LIKE', '%' . $request->search .'%')
                ->orwhere('mobile','LIKE', '%' . $request->search .'%')
                ->orwhere('address','LIKE', '%' . $request->search .'%')->get();      
           }
            
           if( $request->is_user==false)
           { 
         
            $data = Employee::where('name','LIKE', '%'. $request->search .'%')
            ->orwhere('phone','LIKE', '%'. $request->search .'%')
            ->orwhere('email','LIKE', '%'. $request->search .'%')
            ->orwhere('address','LIKE', '%'. $request->search .'%')->get(); 
             
           }
           
            if(count($data)>0)
            {
                $result=array();
                
                
                   
                if($request->is_user ){
                        foreach($data as $user){
                        
                            if(! in_array($user,$result) and $user->accept !=1  ){
                                array_push($result , $user);
                                
                            }}
                        foreach($result as $user){
                            $project=$user->project()->first('name');
                        if($project)$user->project_id=$project->name;
                        
                            $work=$user->work()->first('name');
                            if( $work)$user->work_id=$work->name;
                            
                            }  
                    }
                    else{
                        foreach($data as $user){
                        
                            if(! in_array($user,$result) and $user->employed !=1  ){
                                array_push($result , $user);
                                
                            }} 
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
    public function attach_user_to_project(Request $request) {

        try {  
            
 
            $validate = Validator::make( $request->all(),
            ['user_id'=>'required|integer|exists:users,id',
            'project_id'=>'required|integer|exists:projects,id'
            ]);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
          
            $user=user::find($request->user_id);
            $project=Project::find($request->project_id);
         
           
          if($user and $project){ 
            
            $user->project()->associate($project);
           $user->save();
            
            return response()->json(
                ['message'=>' تم أصافة المشروع الى الجهة المتطوعة بنجاح']
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
  
    public function sendEmail($email){
        // $email = 'ghufrankasho2@gmail.com';
        $data = [
            'data' => 'تم قبول للعمل معنا ك متطوع :)',
        ];

       $result= Mail::to($email)->send(new Notification($data,'emails.user'));
        if($result)
        return response()->json(['status' => true,
            'message' =>'تم إرسال معلوماتك  بالبريد الالكتروني بنجاح']);
     else{
        return response()->json(
            ['status' => false,
           'message' =>  " حدث خطأ أثناء عملية إرسال معلوماتك  بالبريد الالكتروني  ",
           'data'=>null]
            , 422);
     }
  
    }
}