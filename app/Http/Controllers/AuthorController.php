<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Author;

class AuthorController extends Controller
{
    public function index()
    {  
        $authors = Author::with('books')->orderBy('first_surname', 'asc')->get();
        return $this->getResponse200($authors);
    }
    public function store(Request $request)
    {
        try {
            $author = new Author();
               
                $author->name = $request->name;
                $author->first_surname = $request->first_surname;
                $author->second_surname = $request->second_surname;
                
                $author->save();
                
                return $this->getResponse201('author', 'created', $author);
            
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }
    public function show(Request $request,$id)
    {
        $existAuthor = Author::where("id", $id)->exists();
        try {
            if(!$existAuthor){
                return $this->getResponse404();

            }else{
                
                $author = Author::with('books')->find($id);
                 return $this->getResponse200($author);

            }
           
        
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
        

        
    }

    public function destroy(Request $request,$id)
    {
        $existAuthor = Author::where("id", $id)->exists();
        try {
            if(!$existAuthor){
                return $this->getResponse404();

            }else{
                $author = Author::find($id);
                $author->books()->detach();
                $author->delete();
                return $this->getResponseDelete200('author');
          
            }
           
        
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
        
    }

    public function update(Request $request,$id)
    {
        
        try {
            
            $author = Author::find($id);
            
            if ($author) { 
                if($request->name)
                    $author->name = $request->name;
                if($request->first_surname)
                    $author->first_surname = $request->first_surname;
                if($request->second_surname)
                    $author->second_surname = $request->second_surname;
                $author->update();
                
                return $this->getResponse201('author', 'update', $author);
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }
}
