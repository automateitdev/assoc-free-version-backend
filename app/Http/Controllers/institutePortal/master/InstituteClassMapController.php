<?php

namespace App\Http\Controllers\institutePortal\master;

use App\Models\ClassDetails;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\CoreSubcategory;
use App\Models\InstituteClassMap;
use App\Models\CoreInstituteConfig;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\MasterClassShowResource;
use App\Http\Resources\InstituteClassMapResource;

class InstituteClassMapController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $classDetailsMap = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
        ->with('classDetails.shifts', 'classDetails.sections', 'classDetails.groups')
        ->get();

        $allshifts = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories.corecategory', function ($query) {
                $query->where('id', 3);
            })
            ->get();
        $allclasses = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories.corecategory', function ($query) {
                $query->where('id', 4);
            })
            ->get();
        $allsections = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories.corecategory', function ($query) {
                $query->where('id', 6);
            })
            ->get();
        $allgroups = CoreInstituteConfig::where('institute_details_id', Auth::user()->institute_details_id)
            ->whereHas('coresubcategories.corecategory', function ($query) {
                $query->where('id', 5);
            })
            ->get();
        return response()->json([
            'classDetailsMap' => InstituteClassMapResource::collection($classDetailsMap),
            'classes' => MasterClassShowResource::collection($allclasses),
            'shifts' => MasterClassShowResource::collection($allshifts),
            'sections' => MasterClassShowResource::collection($allsections),
            'groups' => MasterClassShowResource::collection($allgroups)
         ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'class_id' => 'required',
            'shift_id' => 'required',
            'group_id' => 'required|array',
            'section_id' => 'required|array'
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Validation passed, proceed to check for duplicates
        $class_id = $request->input('class_id');
        $shift_id = $request->input('shift_id');
        $group_id = $request->input('group_id');
        $section_id = $request->input('section_id');

        $className = CoreSubcategory::find($request->class_id);


        $combinations = [];

        foreach ($section_id as $sectionId) {
            foreach ($group_id as $groupId) {
                $combinations[] = [
                    'shift_id' => $shift_id,
                    'section_id' => $sectionId,
                    'group_id' => $groupId,
                ];
            }
        }

        $findClass = InstituteClassMap::where([
            ['institute_details_id', Auth::user()->institute_details_id],
            ['class_id', $class_id]
        ])->first();
        if(empty($findClass))
        {
            
            $input = new InstituteClassMap();
            $input->institute_details_id = Auth::user()->institute_details_id;
            $input->class_id = $class_id;
            $input->class_name = $className->core_subcategory_name;
            if($input->save())
            {
                $findClass = $input;
            }
        }

        foreach($combinations as $item)
        {
            $checkClassDetails = ClassDetails::where([
                ['shift_id', $item['shift_id']],
                ['group_id', $item['group_id']],
                ['section_id', $item['section_id']]
                ])->first();
                
                if(!empty($checkClassDetails))
                {
                    if(!$checkClassDetails->instituteClassMaps->contains($findClass)){

                        $checkClassDetails->instituteClassMaps()->attach($findClass);
                    }
                    
                }else{
                    $classDetails = new ClassDetails();
                    $classDetails->shift_id = $item['shift_id'];
                    $classDetails->group_id = $item['group_id'];
                    $classDetails->section_id = $item['section_id'];
                    if($classDetails->save())
                    {
                        if(!$classDetails->instituteClassMaps->contains($findClass)){

                            $classDetails->instituteClassMaps()->attach($findClass);
                        }
                    }
                }
        }
        return response()->json(['status' => 'success', 'message' => 'Record saved successfully'], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        $classMaps = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
            ->where('class_id', $request->id)
            ->first();
     

        $groupedData = [
            'shifts' => [],
            'groups' => [],
            'sections' => [],
        ];
    
        foreach ($classMaps->classDetails as $item) {
            // Group shifts
            $groupedData['shifts'][] = $item->shifts;
    
            // Group groups
            $groupedData['groups'][] = $item->groups;
    
            // Group sections
            $groupedData['sections'][] = $item->sections;
        }
    
        // Remove duplicates in each array
        $groupedData['shifts'] = array_unique($groupedData['shifts']);
        $groupedData['groups'] = array_unique($groupedData['groups']);
        $groupedData['sections'] = array_unique($groupedData['sections']);

        // Reset array keys after removing duplicates
        $groupedData['shifts'] = array_values($groupedData['shifts']);
        $groupedData['groups'] = array_values($groupedData['groups']);
        $groupedData['sections'] = array_values($groupedData['sections']);
        return response()->json($groupedData);
    
    }

    public function shift(Request $request)
    {
        // dd($request->all());
        $request->validate([
            'shift_id' => 'required|integer',
            'class_id' => 'required|integer'
        ]);
        $shift_id = $request->shift_id;
        $class_id = $request->class_id;

        $shiftWiseData = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('class_id', $class_id)
                    ->whereHas('classDetails', function($query) use ($shift_id) {
                        $query->where('shift_id', $shift_id);
                    })
                    ->with('classDetails')
                    ->get();

        return response()->json($shiftWiseData);

    }
    public function section(Request $request)
    {
        $request->validate([
            'section_id' => 'required|integer',
            'class_id' => 'required|integer'
        ]);
        $section_id = $request->section_id;
        $class_id = $request->class_id;

        $sectionWiseData = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('class_id', $class_id)
                    ->whereHas('classDetails', function($query) use ($section_id) {
                        $query->where('section_id', $section_id);
                    })
                    ->with('classDetails')
                    ->get();

        return response()->json($sectionWiseData);

    }


    public function group(Request $request)
    {
        $request->validate([
            'group_id' => 'required|integer',
            'class_id' => 'required|integer'
        ]);
        $group_id = $request->group_id;
        $class_id = $request->class_id;

        $groupWiseData = InstituteClassMap::where('institute_details_id', Auth::user()->institute_details_id)
                    ->where('class_id', $class_id)
                    ->whereHas('classDetails', function($query) use ($group_id) {
                        $query->where('group_id', $group_id);
                    })
                    ->with('classDetails')
                    ->get();

        return response()->json($groupWiseData);


    }
    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // $instituteClassMap = InstituteClassMap::find($id);

        // if (!$instituteClassMap) {
        //     return response()->json(['error' => 'Record not found'], 404);
        // }

        // $classDetails = $instituteClassMap->classDetails;
        // $pivotIds = $classDetails->pluck('pivot.id')->toArray();

        // $has_student = StudentAssign::whereIn('combinations_pivot_id', $pivotIds)->exists();

        // if ($has_student) {
        //     return response()->json(['error' => 'Can not be deleted! this Class has students assigned to it'], HttpFoundationResponse::HTTP_BAD_REQUEST);
        // }
    
        // $instituteClassMap->delete();
        
        // return response()->json(['status' => 'success', 'message' => 'Record deleted successfully'], 200);
    }
}
