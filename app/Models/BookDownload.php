<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BookDownload extends Model
{
    use HasFactory;
    protected $table = 'book_downloads';
    protected $fillable=[
        'id',
        'todal_downloads',
        'boock_id'
    ];
    public $timestamps = false;

    public function book(){
        return $this->belongsTo(Book::class);
    }
}