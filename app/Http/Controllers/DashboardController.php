<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Category;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with system statistics.
     */
    public function index()
    {
        // Get total document count
        $totalDocuments = Document::count();
        
        // Get total storage size
        $totalSize = Document::sum('file_size');
        
       
        
        // Get document counts by category
        $documentsByCategory = Category::withCount('documents')
            ->orderBy('documents_count', 'desc')
            ->get();
        
        // Get recent documents
        $recentDocuments = Document::orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        return view('dashboard', compact(
            'totalDocuments', 
            'totalSize', 
            'documentsByCategory', 
            'recentDocuments'
        ));
    }
}
