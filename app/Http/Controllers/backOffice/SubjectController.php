<?php

namespace App\Http\Controllers\backOffice;

use App\Models\SubjectList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\SubjectListResource;

class SubjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $subjectList = SubjectList::all();
        return response()->json([
            'subjectList' => SubjectListResource::collection($subjectList),
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
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required',
            'subject_code' => 'nullable'
        ]);


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        $exists = SubjectList::where('subject_code', $request->subject_code)->first();
        if ($exists) {
            return response()->json(['status' => 'success', 'message' => 'Subject Already exists'], Response::HTTP_CREATED);
        }

        $subjectlist = new SubjectList();
        $subjectlist->subject_name = $request->subject_name;
        $subjectlist->subject_code = $request->subject_code;
        $subjectlist->save();

        return response()->json(['status' => 'success', 'message' => 'Subject add successfully'], Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
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
    public function update(Request $request, $id)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'subject_name' => 'required|string|max:255',
            'subject_code' => 'nullable|string|max:50'
        ]);

        // If validation fails, return a 400 Bad Request with errors
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], Response::HTTP_BAD_REQUEST);
        }

        // Find the subject by ID
        $subject = SubjectList::find($id);

        // If the subject does not exist, return a 404 Not Found
        if (!$subject) {
            return response()->json(['status' => 'error', 'message' => 'Subject not found'], Response::HTTP_NOT_FOUND);
        }

        // Check if the new subject code already exists (and it is not the same subject)
        if ($request->subject_code) {
            $exists = SubjectList::where('subject_code', $request->subject_code)
                ->where('id', '!=', $id)
                ->first();
            if ($exists) {
                return response()->json(['status' => 'error', 'message' => 'Subject code already exists'], Response::HTTP_CONFLICT);
            }
        }

        // Update the subject with new data
        $subject->subject_name = $request->subject_name;
        $subject->subject_code = $request->subject_code;
        $subject->save();

        // Return a 200 OK with a success message
        return response()->json(['status' => 'success', 'message' => 'Subject updated successfully'], Response::HTTP_OK);
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
