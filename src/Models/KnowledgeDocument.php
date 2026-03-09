<?php

namespace Nomanur\Models;

use Illuminate\Database\Eloquent\Model;

class KnowledgeDocument extends Model
{
    protected $fillable = ['name', 'system_prompt', 'tool_description', 'path', 'metadata'];

    protected $casts = [
        'metadata' => 'array',
    ];

}
