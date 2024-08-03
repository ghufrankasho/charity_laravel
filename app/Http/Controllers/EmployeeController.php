<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Vacation;
use Illuminate\Http\Request;
use DateTime;
use App\Mail\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
class EmployeeController extends Controller
{
    public function index(){
        $employees=Employee::where('employed',true)->with('vacations')->latest()->get();
       
        foreach($employees as $employee){
            $date=  new DateTime($employee->created_at);
            $employee->date= $date->format('Y-m-d');
            $employee->number=count( $employees);
            if( count($employee->vacations)>0)
            {
                $vacation=Vacation::where('employee_id',$employee->id)->orderBy('updated_at', 'desc')->latest()->first();
                
                $start = new DateTime($vacation->start_date);
                $end = new DateTime($vacation->end_date);
                if(now() >$end  or now()<$start){
                    $employee->avalible=true; 
                }
                else{
                  $employee->avalible=false; 
                }    
            }
            else{
               $employee->avalible=true; 
            }
            
        }
        
        return response()->json(
            $employees
            ,200);
    }
    public function request(){
        
        $employees=Employee::where('employed',false)->latest()->get();
       
        return response()->json(
            $employees
            ,200);
    }
    public function store(Request $request){
        
        try{
            
              
            $validateaEmployee = Validator::make($request->all(), 
            [
               'name' => 'string|required',
               'email' => 'string|required|email|unique:employees',
               'address' => 'nullable|string',
               'phone' => 'string|required',
               'account_id' => 'integer|exists:accounts,id',
                'cv' => "file|required|mimetypes:application/pdf,application/txt|max:10000",
               // 'work_id' => 'nullable|integer|exists:works,id',
                // 'em_id' => 'nullable|integer|exists:projects,id',
                'image' => 'file|required|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',

            ]);
            $validateaEmployee->sometimes('image', 'required|mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });

            if($validateaEmployee->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateaEmployee->errors()
                ], 422);
            }

           
             
                
            if($request->hasFile('image') and $request->file('image')->isValid()){
                $image= $this->store_image($request->file('image')); 
            }
            if($request->hasFile('cv') and $request->file('cv')->isValid()){
                $cv= $this->store_cv($request->file('cv')); 
            }
            $employee=Employee::where('account_id',$request->account_id)->first();
            if($employee){
                
                return response()->json('حدث خطأ أثناء أضافة البيانات', 422);
                
            }
            $employee = Employee::create(array_merge(
                $validateaEmployee->validated()
                
                ));
            $employee->image=$image;
            $employee->cv=$cv;
            $account=Account::find($request->account_id);
            $employee->account()->associate($account);
            $result=$employee->save();
           if ($result){
               
            return response()->json(
                ['status' => true,
                'message' =>    'تم أضافة بيانات الموظف بنجاح',
                'data'=>null]
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
                ['id'=>'required|integer|exists:employees,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
          
            $employee=Employee::with('vacations','account')->findOrFail($request->id );
         
            $account=Account::with('employee')->findOrFail($employee->account_id);
           
          if($account and $employee){ 
            
                if($employee->image!=null){
                    $this->deleteImage($employee->image);
                } 
                if($employee->cv!=null){
                    $this->deleteCV($employee->cv);
                } 
                // delete department
                
                // delete vactions
                $employee->vacations()->delete();
                //delete account 
                 
                $account->employee()->delete();
                $result=  $account->delete();
               if($result){ 
                return response()->json(
                ['message'=>' تم حذف بيانات الموظف بنجاح']
                , 200);
            }
            }

            return response()->json(null, 422);
        }
        catch (ValidationException $employee) {
            return response()->json(['errors' => $employee->errors()], 422);
        } 
        catch (\Exception $employee) {
            return response()->json(['message' => $employee
            // 'An error occurred while deleting the E.'
            ]
            , 500);
        }
    }
    public function update(Request $request){
        try{
             
            
                
           
            
            $validateE = Validator::make($request->all(),
            [
                'id'=>'required|integer|exists:employees,id',
                'name' => 'nullable|string',
                'email' => 'nullable|string|email|unique:employees',
                'address' => 'nullable|string',
                'phone' => 'nullable|string',
                'salary' => 'nullable|integer|min:0|max:1000000',
                'cv' => "nullable|file|mimetypes:application/pdf,application/txt|max:10000",
               
                'department_id' => 'nullable|integer|exists:departments,id',
            
                 'image' => 'nullable|file|mimetypes:image/jpeg,image/png,image/gif,image/svg+xml,image/webp,application/wbmp',
            ]);
            $validateE->sometimes('image', 'mimetypes:image/vnd.wap.wbmp', function ($input) {
                return $input->file('image') !== null && $input->file('image')->getClientOriginalExtension() === 'wbmp';
            });
            
            if($validateE->fails()){
                return response()->json([
                    'status' => false,
                    'message' => 'خطأ في التحقق',
                    'errors' => $validateE->errors()->first()
                ], 422);
            }
            $employee=Employee::find($request->id);
            if($employee){
                  
                $employee->update($validateE->validated());
                if($request->hasFile('image') and $request->file('image')->isValid()){
                    if($employee->image !=null){
                        $this->deleteImage($employee->image);
                    }
                    $employee->image = $this->store_image($request->file('image')); 
                }
                if($request->hasFile('cv') and $request->file('cv')->isValid()){
                    if($employee->cv !=null){
                        $this->deleteCV($employee->cv);
                    }
                    $employee->cv = $this->store_cv($request->file('cv')); 
                }
                if($request->department_id != null){

                    $department=Department::find($request->department_id);
                    $employee->department()->associate($department);
                }
               
               
                
                $employee->save();
                
                return response()->json(
                  ['status'=>true,
                    'message'=>  'تم تعديل بيانات المستخدم بنجاح']
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
          
            $data = Employee::where('name','LIKE', '%' . $search .'%')
                ->orwhere('phone','LIKE', '%' . $search .'%')
                ->orwhere('email','LIKE', '%' . $search .'%')
                ->orwhere('address','LIKE', '%' . $search .'%')->get();      
              
            
            if(count($data)>0)
            {
                $result=array();
                
                foreach($data as $Employee){
                    
                    if(! in_array($Employee,$result)  ){
                        array_push($result , $Employee);
                        
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
    public function show(Request $request){
        try {  
            
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:employees,id']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
            $employee=employee::with('department')->find($request->id);
            $employee->depart=null;
            if($employee->department !=null){
               $depart=Department::with('branch','projects')->find($employee['department']->id);
               $employee->depart=$depart;
            }
         
          if($employee){ 
            return response()->json(
                $employee
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
        $employeextension = $file->getClientOriginalExtension();
           
        $imageName = uniqid() . '.' .$employeextension;
        $file->move(public_path('employee'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('employee/' . $imageName);
                
         
       
       return $imagePath;

    }
    public function store_cv( $file){
        $employeextension = $file->getClientOriginalExtension();
           
        $imageName = uniqid() . '.' .$employeextension;
        $file->move(public_path('employee_cv'), $imageName);

        // Get the full path to the saved image
        $imagePath = asset('employee_cv/' . $imageName);
                
         
       
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
    public function accept(Request $request){
        try {  
            
            
            
            $validate = Validator::make( $request->all(),
                ['id'=>'required|integer|exists:employees,id',
                'accept'=>'required|bool']);
            if($validate->fails()){
            return response()->json([
               'status' => false,
               'message' => 'خطأ في التحقق',
               'errors' => $validate->errors()
            ], 422);}
        //   branch relishen shipe
        
            $employee=employee:: find($request->id);
            $employee->employed=$request->accept;
            $employee->save();
           
         
          if($employee){ 
            
            return response()->json(
                ['status' => true,
                'message' =>    'تم العملية  بنجاح',
                'data'=>$employee]
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
    public function sendEmail($data){
        $email = 'ghufrankasho2@gmail.com';
        $data = [
            'data' => 'تم قبولك للعمل معنا   :)',
            'branch_name'=>$data->branch_name,
            'depatment_name'=>$data->department_name
        ];

       $result= Mail::to($email)->send(new Notification($data,'emails.employee'));
        if($result)
        return response()->json([
            'message' =>'تم إرسال معلوماتك  بالبريد الالكتروني بنجاح']);
   
  
    }

}