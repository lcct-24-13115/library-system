<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'accession_number',
        'title',
        'author',
        'isbn',
        'category',
        'total_copies',
        'available_copies',
    ];
}