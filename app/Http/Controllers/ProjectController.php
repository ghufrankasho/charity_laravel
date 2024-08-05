<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Department;
use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use App\Models\ProjectType;
use Illuminate\Http\Request;
use DateTime;
 
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
 

class ProjectController extends Controller
{
    
    public function index($num_pages=null){
        if($num_pages !=null)   $projects=Project::with('doners','user')->latest()->take($num_pages)->get();
        else $projects=Project::with('doners','user')->latest()->get();
        
        foreach($projects as $project){
            $employees=null;
            $depart=Department::find($project->department_id);
            if($depart){
                $employees=$depart->employees()->get();
            }
            $start = new DateTime($project->start_date);
            $end = new DateTime($project->end_date);
            
            $total_work_days = $start->diff($end);
            $days = $total_work_days->format('%a');
            
            $done_days = $start->diff(now());
            $done_days = $done_days->format('%a');
            
            $prograss= ($done_days/$days);
            $prograss=round($prograss, 2);
            $project->prograss=$prograss*100;
           
            $project->save();
            $project->employees=$employees;
        }
        return response()->json(
            $projects
            ,200);
    }
    public function get_pages(){
       
       
            $projects=Project::latest()->get();
            foreach($projects as $project){
                $date=  new DateTime($project->start_date);
               $project->date= $date->format('Y-m-d');
            }
            return response()->json(
                $projects
                ,200);
       
    }
    public function get_projects_with_fundris(){
        $projects=Project::where('fundrise','!=',0)->latest()->get();
       
        foreach($projects as $project){
            
            $amount=$project->donations()->sum('amount');
         
            $project->amount= $amount;
        }
        return response()->json(
            $projects
            ,200);
    }
    public function get_statistac(){
        $ourgoal=Project::where('fundrise','!=',0)->latest()->get()->sum('fundrise');
        $raised=Donation::get()->sum('amount');
        $volunters=User::where('work_id',5)->count();
        
        
        return response()->json(
            [
                'ourgoal'=>$ourgoal,
                'raised'=>$raised,
                'volunters'=>$volunters,
                ]
            ,200);
    }
    public function store(Request $request){
        
        try{
            
              
            $validateproject = Validator::make($request->all(), 
            [
               'name' => 'string|required',
               'description' => 'string|required',
               'start_date' => 'string|required',
               'end_date' => 'string|required',
               'fundrise' => 'nullable|integer|min:0|max:1000000',
               'project_type_id' => 'integer|required|exists:project_types,id',
               'department_id' => 'integer|required|exists:departments,id',
               'image' => 'file|required|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',

            ]);
            $validateproject->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });

            if($validateproject->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateproject->errors()->first()
                ], 422);
            }

           
             
                
            if($request->hasFile('image') and $request->file('image')->isValid()){
                $image= $this->store_image($request->file('image')); 
            }
           
            
            
            $project = project::create(array_merge(
                $validateproject->validated()
                
                ));
            $project->image=$image;
            $type=ProjectType::find($request->project_type_id);
            $project->projectType()->associate($type);
            
            $department=Department::find($request->department_id);
            $project->department()->associate($department);
            $result=$project->save();
           if ($result){
               
                return response()->json(
                    ['status' => true,
                    'message' =>'تم أضافة بيانات المشروع بنجاح',
                     ]
                 
                 , 201);
                }
           else{
            return response()->json([
                'status' => false,
                'message' =>  'حدث خطأ أثناء أضافة البيانات',
                
                ], 422);
                
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
             
            $input = [ 'id' =>$id];
          
            $validate = Validator::make( $input,
                ['id'=>'required|integer|exists:projects,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
          
            $project=project::find($id);
         
           
          if($project){ 
                if($project->image!=null){
                    $this->deleteImage($project->image);
                } 
               
                //  dissociate from projectType
                $type=$project->projectType()->first();
                if($type){
                $project->projectType()->dissociate($type);
 
                }
                 
                //delete users
                $users=$project->user()->get();
               if( count($users)!=0){
                
                foreach($users as $user){
                    $user->project()->dissociate();
                    $user->save();
                }
               }
               //dissociate doners
               $doners=$project->doners()->get();
               if(count($doners)!=0){
            
                foreach($doners as $doner){
                    
                    $doner->projects()->detach();
                   
                    $doner->save();
                }
               }
                
               $project->save();
                $result= $project->delete();
             
                if($result){ 
                    return response()->json(
                   ['status'=>true,
                     'message' =>' تم حذف بيانات المشروع بنجاح']
                    , 200);
                }
            }

            return response()->json(['message'=>"حدث خطأ أثناء عملية الحذف"], 422);
        }
        catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['message' => 'حدث خطأ أثناء عملية الحذف'], 500);
        }
    }
    public function update(Request $request){
        try{
            
            
            
            $validateproject = Validator::make($request->all(), [
                'id'=>'required|integer|exists:projects,id',
                'name' => 'string|nullable',
                'description' => 'string|nullable',
                'start_date' => 'date|nullable',
                'fundrise' => 'integer|nullable|min:0|max:1000000',
                'end_date' => 'date|nullable',
                'project_type_id' => 'integer|nullable|exists:project_types,id',
                'image' => 'file|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',
            ]);
            $validateproject->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
            
            if($validateproject->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateproject->errors()->first()
                ], 422);
            }
            $project=project::find($request->id);
            if($project){  
                $project->update($validateproject->validated());
                if($request->hasFile('image') and $request->file('image')->isValid()){
                    if($project->image !=null){
                        $this->deleteImage($project->image);
                    }
                    $project->image = $this->store_image($request->file('image')); 
                }
                if($request->project_type_id != null){

                    $type=projectType::find($request->project_type_id);
                    $project->projectType()->associate($type);
                }
                if($request->department_id != null){

                    $department=department::find($request->department_id);
                    $project->department()->associate($department);
                }
               
                
                $project->save();
                
                return response()->json(
                    ['status' => true,
                    'message' => 'تم تعديل بيانات المشروع بنجاح',
                     ]
                    , 200);
            }
            
            return response()->json([
                'status' => false,
                'message' =>  'فشلت عملية التعديل ',
                
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
        $file->move(public_path('projects'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('projects/' . $imageName);
                
         
       
       return $imagePath;

    }
    public function show(Request $request) {
       
        try {  
            
            $input = [ 'id' =>$request->id];
            
            $validate = Validator::make( $input,
                ['id'=>'required|integer|exists:projects,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
        //   branch relishen shipe
        
            $project=project::with('projectType','department','doners','user')->find($request->id);
            $depart=Department::get(['id','name']);
            $types=ProjectType::get(['id','name']);
            $project->project_type_id=$project['projectType']->name;
            $project['types']=$types;
            $project->department_id=$project['department']->name;
            $project['departments']=$depart;
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
    public function show_all_detailes(Request $request) {
       
        try {  
            
           
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:projects,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
        //   branch relishen shipe
        
            $project=project::with('projectType','department','doners','user')->find($request->id);
           
            $depart=Department::find($project->department_id);
            $employees=$depart->employees()->get();
            $branch=$depart->branch()->first();
            $project->project_type_id=$project['projectType']->name;
            
            $project->department_id=$project['department']->name;
            $project->employees=$employees;
            $project->branch=$branch;
            return $project;
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
          
            $data = Project::where('name','LIKE', '%' . $request->search .'%')
                ->orwhere('description','LIKE', '%' . $request->search .'%')->get();      
              
            
            if(count($data)>0)
            {
                $result=array();
                
                foreach($data as $Project){
                    
                    if(! in_array($Project,$result)  ){
                        array_push($result , $Project);
                        
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
    public function donat(Request $request) {
        try {  
             
           
            
            $validate = Validator::make( $request->all(),
                [
                    'project_id'=>'nullable|integer|exists:projects,id',
                    'account_id'=>'required|integer|exists:accounts,id',
                    'amount'=>'required|integer',
                    'detailes'=>'required|string']);
                  
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()->first()
            ], 422);}
            
            $donation = Donation::create(array_merge(
                $validate->validated()
                
                ));
             
            if($request->project_id!==null){
                
                $project=project::find($request->project_id);
                $donation->project()->associate($project);
            }
            if($request->account_id!==null){
                
                $account=Account::find($request->account_id);
                $donation->account()->associate($account);
            }
            
            $result= $donation->save();
       
     
                if($result){ 
                    return response()->json(
                   ['status'=>true,
                     'message' =>' شكرا لتبرعك  ',
                     'data'=>null]
                    , 200);
                }
            

            return response()->json(['status'=>false,'message'=>"حدث خطأ أثناء عملية التبرع",
        'errors'=>"حدث خطأ أثناء عملية شكرالتبرع"], 422);
        }
        catch (ValidationException $e) {
            return response()->json([
                'staust'=>false,
                'message'=>"حدث خطأ أثناء عمليةالتبرع",
                'errors' => $e->errors()], 422);
        } 
        catch (\Exception $e) {
            return response()->json(['errors' => $e,'message' => 'حدث خطأ أثناء عملية الحذف'], 500);
        }
    }
}