<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    use HasFactory;
    protected $table='authors';
    protected $fillable=[
        'id',
        'name',
        'first_name',
        'second_surname'
    ];
    public $timestamps= false;


    public function books(){
        return $this->belongsToMany(
            Book::class,
            'authors_books',
            'authors_id',
            'books_id',
        );
    }
    //function show delete update downloads automaticamente y relaciones faltantes  

}
