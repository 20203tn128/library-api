<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Book;
use App\Models\BookDownload;
use App\Models\BookReview;


use Illuminate\Support\Facades\DB;

use Illuminate\Support\Facades\Validator;
class BookController extends Controller
{
    
    public function index()
    {   
        $books = Book::with('editorial','category','bookDownload','authors')->orderBy('title', 'asc')->get();
        return $this->getResponse200($books);
    }
    public function store(Request $request)
    {
        try {
            $isbn = preg_replace('/\s+/', '\u0020', $request->isbn); //Remove blank spaces from ISBN
            $existIsbn = Book::where("isbn", $isbn)->exists(); //Check if a registered book exists (duplicate ISBN)
            if (!$existIsbn) { //ISBN not registered
                $book = new Book();
                $book->isbn = $isbn;
                $book->title = $request->title;
                $book->description = $request->description;
                $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                $book->category_id = $request->category["id"];
                $book->editorial_id = $request->editorial["id"];
                $book->save();
                $bookDownload =new BookDownload();
                $bookDownload->book_id=$book->id;
                $bookDownload->save();
                foreach ($request->authors as $item) { //Associate authors to book (N:M relationship)
                    $book->authors()->attach($item);
                }
                return $this->getResponse201('book', 'created', $book);
            } else {
                return $this->getResponse500(['The isbn field must be unique']);
            }
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
    }


    public function destroy(Request $request,$id)
    {
        $existBook = Book::where("id", $id)->exists();
        try {
            if(!$existBook){
                return $this->getResponse404();

            }else{
                $book = Book::find($id);
                $book->bookDownload()->delete();
                $book->authors()->detach();
                
                
                $book->delete();
                return $this->getResponseDelete200('book');
                
               
            }
           
        
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
        
    }

    public function show(Request $request,$id)
    {  
        $existBook = Book::where("id", $id)->exists();
       
        try {
            if(!$existBook){
                return $this->getResponse404();

            }else{
                
                $book = Book::with('editorial','category','bookDownload','authors')->find($id);
                 return $this->getResponse200($book);

            }
           
        
        } catch (Exception $e) {
            return $this->getResponse500([]);
        }
        
    }

    public function update(Request $request,$id)
    {
        DB::beginTransaction();
        try {
            
            $book = Book::find($id);
            
            if ($book==true) { 
                $isbn = trim($request->isbn);
                $isbnOwner = Book::where("isbn",$isbn)->first();
                if(!$isbnOwner ||$isbnOwner->id==$book->id){
                    if($request->isbn)
                $book->isbn =$isbn;
                if($request->title)
                $book->title = $request->title;
                if($request->description)
                    $book->description = $request->description;
                if($request->published_date)
                    $book->published_date = date('y-m-d h:i:s'); //Temporarily assign the current date
                if($request->category)
                    $book->category_id = $request->category["id"];
                if($request->editorial)
                    $book->editorial_id = $request->editorial["id"];
                $book->update();
                
                //add new authors
                if($request->authors){
                    //delete
                    foreach($book->authors as $item){
                        $book->authors()->detach($item->id);
                    }
                    foreach ($request->authors as $item) { 
                        $book->authors()->attach($item);
                    }
                }
                DB::commit();
                return $this->getResponse201('book', 'update', $book);

                }else{

                    $response["message"]="ISBN dubplicated";
                }
            } else {
                return $this->getResponse404();
            }
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }
    }

    
    public function addBookReview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
            'book' => 'required'
        ]);
        if ($validator->fails()) return $this->getResponse500([$validator->errors()]);
        DB::beginTransaction();
        try {
            if (
                BookReview::where('book_id', $request->book['id'])
                    ->where('user_id', $request->user()->id)
                    ->exists()
            )
                return $this->getResponse500(['You have already written a review for this book']);
            if (!Book::where('id', $request->book['id'])->exists())
                return $this->getResponse500(['The entered book does not exists']);
                // return $this->getResponse404();
            $bookReview = new BookReview();
            $bookReview->comment = $request->comment;
            $bookReview->book_id = $request->book['id'];
            $bookReview->user_id = $request->user()->id;
            $bookReview->save();
            DB::commit();
            return $this->getResponse201('book review', 'created', $bookReview);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }  
    }
    
    public function updateBookReview(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required',
        ]);
        if ($validator->fails()) return $this->getResponse500([$validator->errors()]);
        if (!BookReview::where('id', $id)->exists()) return $this->getResponse404();
        $bookReview = BookReview::with('user', 'book')
            ->where('id', $id)
            ->first();
        if ($bookReview->user->id != $request->user()->id ) return $this->getResponse403();
        DB::beginTransaction();
        try {
            $bookReview->comment = $request->comment;
            $bookReview->edited = true;
            $bookReview->save();
            DB::commit();
            return $this->getResponse201('book review', 'updated', $bookReview);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->getResponse500([$e->getMessage()]);
        }  
    }
}
