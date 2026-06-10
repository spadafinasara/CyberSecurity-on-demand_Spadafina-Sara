<?php 
namespace App\Http\Controllers;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Services\HtmlFilterService;
use Illuminate\Support\Facades\Auth;
class ArticleController extends Controller
{
    public function index(Request $request, HtmlFilterService $htmlFilterService)
    {
        $articles = Article::latest()->where('published',true)->take(6)->get();
        // FIX 1: filtro XSS attivato
        $articles = $htmlFilterService->filterHtmlCollectionByField($articles,'content');
        if ($request->wantsJson()) {
            return response()->json($articles);
        }
        return view('articles.index', compact('articles'));
    }

    public function search(Request $request){
        $articles = Article::where('title', 'LIKE', "%{$request->search}%")
                            ->orWhere('content', 'LIKE', "%{$request->search}%")
                            ->get();
        return view('articles.index',compact('articles'));
    }
    
    // FIX 2: versione SECURE di show() con HtmlFilterService
    public function show(Article $article, Request $request, HtmlFilterService $htmlFilterService)
    {
        $article->content = $htmlFilterService->filterHtml($article->content);
        if ($request->wantsJson()) {
            return response()->json($article);
        }
        return view('articles.show', compact('article'));
    }
    
    public function create()
    {
        return view('articles.create');
    }
    
    // FIX 3: parametro HtmlFilterService aggiunto + ordine corretto
    public function store(Request $request, HtmlFilterService $htmlFilterService)
    {
        $articleData = $request->only(['title', 'content', 'image']);
        $articleData['content'] = $htmlFilterService->filterHtml($articleData['content']);
        $articleData['user_id'] = Auth::id();
        
        $article = Article::create($articleData);
        
        if ($request->wantsJson()) {
            return response()->json($article, 201);
        }
        return redirect()->route('articles.index');
    }

    public function edit(Article $article)
    {
        if(Auth::id() !== $article->user_id && !Auth::user()->isAdmin()){
             return redirect()->route('articles.index')->with('message','Not authorized');
        }
    }

    //  FIX 4: parametro HtmlFilterService aggiunto + ordine corretto
    public function update(Request $request, Article $article, HtmlFilterService $htmlFilterService)
    {
        if(Auth::id() !== $article->user_id && !Auth::user()->isAdmin()){
             return redirect()->route('articles.index')->with('message','Not authorized');
        }
        $articleData = $request->only(['title', 'content', 'image']);
        $articleData['content'] = $htmlFilterService->filterHtml($articleData['content']);
        $articleData['user_id'] = Auth::id();
        $article->update($articleData);
        
        if ($request->wantsJson()) {
            return response()->json($article, 200);
        }
        return redirect()->route('articles.show', $article);
    }
    
    public function destroy(Article $article, Request $request)
    {
        if(Auth::id() !== $article->user_id){
            return redirect()->route('articles.show', $article)->with('message','Not authorized');
        }
        $article->delete();
        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }
        return redirect()->route('articles.index')->with('message','Article deleted successfully');
    }
}