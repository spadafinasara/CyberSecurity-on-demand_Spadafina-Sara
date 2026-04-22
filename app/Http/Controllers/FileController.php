<?php

namespace App\Http\Controllers;

use App\Models\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
        if(!$user = Auth::user()){
            return back()->with('message','Please Log In');
        }

        // 2. Controlla che il file sia stato inviato
        if (!$request->hasFile('file')) {
            return back()->withErrors("Forbidden Operation");
        }

        // 3. Definisce il path dove salvare il file
        // $path = storage_path("app/public/docs/users/".$user->id);

        // // 4. Crea la cartella se non esiste
        // if (!file_exists($path)) {
        //     mkdir($path, 0777, true);
        // }

        // // 5. Prende il file dalla request
        // $file = $request->file('file');

        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        if (!in_array($extension, $allowedExtensions) || !in_array($mimeType, $allowedMimeTypes)) {
            return back()->withErrors('File type not allowed');
        }

        $filename = $file->getClientOriginalName();
        // generate a unique id for the file
        $fileuid = uniqid().'.'.$extension;

        // save the file in a private folder (storage/app/private/docs/users/{$user->id})
        $path = $file->storeAs("docs/users/{$user->id}", $fileuid, 'local');

        // save the record in the DB
        File::create([
            'name' => $filename,
            'file' => $fileuid,
            'user_id' => $user->id
        ]);

        return back()->withMessage("Upload successful");
    }

    public function download($file)
    {
        // 1. Controlla che l'utente sia loggato
        if(!$user = Auth::user()){
            return back()->with('message', 'Please Log In');
        }

        // 2. Cerca il file nel DB verificando che appartenga all'utente loggato (evita IDOR)
        $fileRecord = File::where('file', $file)
                          ->where('user_id', $user->id)
                          ->firstOrFail();

        // 3. Costruisce il path fisico
        $path = "docs/users/{$user->id}/{$fileRecord->file}";

        // 4. Controlla che il file esista fisicamente sul server
        if (!Storage::disk('local')->exists($path)) {
            abort(404);
        }

        // 5. Scarica il file con il nome originale
        return Storage::disk('local')->download($path, $fileRecord->name);
    }
            
    

    /**
     * Display the specified resource.
     */
    public function show(File $file)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(File $file)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, File $file)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(File $file)
    {
        //
    }
}
