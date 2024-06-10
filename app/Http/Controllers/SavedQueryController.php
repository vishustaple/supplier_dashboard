<?php

namespace App\Http\Controllers;

use App\Models\SavedQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SavedQueryController extends Controller
{
    public function index(){
        $queries = SavedQuery::all();
        $pageTitle = "Save SQL Queries";

        return view('admin.queries.index', compact('queries', 'pageTitle'));
    }

    public function create(){
        $pageTitle = "Save SQL Queries";

        return view('admin.queries.create', compact('pageTitle'));
    }

    public function store(Request $request){
        $query = new SavedQuery();
        $query->query = $request->input('query');
        $query->title = $request->input('title');
        $query->created_by = Auth::id();
        $query->save();

        return redirect()->route('queries.index');
    }

    public function show($id){
        $query = SavedQuery::where(['id' => $id, 'created_by' => Auth::id()])->first();
        $pageTitle = "Save SQL Queries";

        return view('admin.queries.show', compact('query', 'pageTitle'));
    }

    public function edit($id){
        $query = SavedQuery::find($id);
        $pageTitle = "Save SQL Queries";

        return view('admin.queries.edit', compact('query', 'pageTitle'));
    }

    public function update(Request $request, $id){
        $query = SavedQuery::find($id);
        $query->query = $request->input('query');
        $query->title = $request->input('title');
        $query->save();

        return redirect()->route('queries.index');
    }

    public function destroy($id){
        $query = SavedQuery::find($id);
        $query->delete();

        return redirect()->route('queries.index');
    }
}
