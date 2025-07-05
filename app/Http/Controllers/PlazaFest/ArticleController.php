<?php

namespace App\Http\Controllers\PlazaFest;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class ArticleController extends Controller
{

    public function getArticleList(Request $request)
    {
        $articles = DB::table('posts')
            ->select('id', 'author_id', 'category_id', 'title', 'seo_title', 'excerpt', 'body', 'image', 'slug', 'meta_description', 'meta_keywords', 'status', 'featured', 'created_at', 'updated_at')
            ->where('status', 'PUBLISHED')
            ->orderByDesc('created_at')
            ->get();
        return response()->json($articles);
    }


    public function getDetailArticleById($id)
    {
        $article = DB::table('posts')
            ->select('id', 'author_id', 'category_id', 'title', 'seo_title', 'excerpt', 'body', 'image', 'slug', 'meta_description', 'meta_keywords', 'status', 'featured', 'created_at', 'updated_at')
            ->where('id', $id)
            ->where('status', 'PUBLISHED')
            ->first();
        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }
        return response()->json($article);
    }
}
