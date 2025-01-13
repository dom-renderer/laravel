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

    public static function buildTreeFromIds(array $categoryIds)
    {
        $categories = self::whereIn('id', $categoryIds)
            ->with('parent')
            ->get();

        $parentIds = $categories->pluck('parent_id')
            ->filter()
            ->diff($categoryIds)
            ->unique();

        if ($parentIds->isNotEmpty()) {
            $parents = self::whereIn('id', $parentIds)->get();
            $categories = $categories->merge($parents);
        }

        return self::buildTree($categories);
    }

    private static function buildTree(\Illuminate\Support\Collection $categories, $parentId = null)
    {
        $tree = [];

        $nodes = $categories->where('parent_id', $parentId);

        foreach ($nodes as $node) {
            $children = self::buildTree($categories, $node->id);
            
            $tree[] = [
                'id' => $node->id,
                'name' => $node->name,
                'children' => $children,
                'parent_id' => $node->parent_id,
                'has_children' => count($children) > 0,
                'level' => self::calculateLevel($categories, $node)
            ];
        }

        return $tree;
    }

    private static function calculateLevel(\Illuminate\Support\Collection $categories, Category $node)
    {
        $level = 0;
        $current = $node;

        while ($current->parent_id !== null && $current->parent_id !== 0) {
            $level++;
            $current = $categories->firstWhere('id', $current->parent_id);
            if (!$current) break;
        }

        return $level;
    }
}
