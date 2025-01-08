<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'parent_id'];

    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function descendants()
    {
        return $this->children()->with('descendants');
    }

    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function ancestors()
    {
        return $this->parent()->with('ancestors');
    }

    public static function getCategoryHierarchy($categoryId)
    {
        return self::where('id', $categoryId)
            ->with(['ancestors', 'descendants'])
            ->first();
    }

    public function getAllDescendants()
    {
        $descendants = collect();
        
        foreach ($this->descendants as $child) {
            $descendants->push($child);
            $descendants = $descendants->merge($child->getAllDescendants());
        }
        
        return $descendants;
    }

    public function getAllAncestors()
    {
        $ancestors = collect();
        
        if ($this->parent) {
            $ancestors->push($this->parent);
            $ancestors = $ancestors->merge($this->parent->getAllAncestors());
        }
        
        return $ancestors;
    }

    public static function getHierarchyAsTree($categoryId)
    {
        $category = self::getCategoryHierarchy($categoryId);
        
        return [
            'current' => [
                'id' => $category->id,
                'name' => $category->name
            ],
            'ancestors' => $category->getAllAncestors()->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name
                ];
            })->values(),
            'descendants' => $category->getAllDescendants()->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name
                ];
            })->values()
        ];
    }
}
