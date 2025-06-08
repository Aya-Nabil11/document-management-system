<?php

namespace App\Http\Controllers;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordReader;
use App\Models\Document;
use App\Models\Category;
use App\Jobs\ProcessDocumentJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class DocumentController extends Controller
{
    /**
     * Display a listing of the documents.
     */
   public function index()
{
    // بدء توقيت التنفيذ
    $startTime = microtime(true);

    // ترتيب المستندات حسب العنوان تصاعديًا
    $documents = Document::orderBy('title', 'asc')->paginate(10);

    // حساب الزمن المستغرق
    $timeTaken = microtime(true) - $startTime;
    $totalSize = Document::sum('file_size');


    // إرسال النتائج للواجهة
    return view('documents.index', compact('documents', 'timeTaken','totalSize'));
}


    
    public function create()
    {
        return view('documents.create');
    }
private function extractTitleFromContent($file, $extension)
{
    if ($extension === 'pdf') {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($file->getPathname());
            $text = $pdf->getText();
            $lines = explode("\n", trim($text));
            return $lines[0] ?? 'Untitled PDF';
        } catch (\Exception $e) {
            return 'Untitled PDF';
        }
    }

    if ($extension === 'docx') {
        try {
            $phpWord = WordReader::load($file->getPathname(), 'Word2007');
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text = trim($element->getText());
                        if (!empty($text)) {
                            return $text;
                        }
                    }
                }
            }
            return 'Untitled Word';
        } catch (\Exception $e) {
            return 'Untitled Word';
        }
    }

    return 'Untitled';
}
    /**
     * Store a newly created document in storage.
     */
  public function store(Request $request)
{
    $start = microtime(true);
    // 1. التحقق من صحة الملف (مطلوب - حجمه لا يتجاوز 10MB)
    $request->validate([
        'document' => 'required|file|max:10240',
    ]);

    // 2. التقاط الملف ومعلوماته الأصلية
    $file = $request->file('document');
    $originalFilename = $file->getClientOriginalName();
    $fileSize = $file->getSize();
    $extension = $file->getClientOriginalExtension();

    // 3. توليد اسم فريد للملف (UUID) بدون إضافة مجلد 'documents' يدويًا
    $uniqueFilename = Str::uuid() . '.' . $extension;
    $localPath = $uniqueFilename; // سيتم تخزينه داخل مجلد 'documents' تلقائيًا عبر disk

    // 4. تخزين الملف فعليًا في storage/app/public/documents
    Storage::disk('documents')->put($uniqueFilename, file_get_contents($file));

    // 5. استخراج عنوان المستند من المحتوى
    $title = $this->extractTitleFromContent($file, $extension);

    // 6. إنشاء سجل في قاعدة البيانات
    $document = Document::create([
        'title' => $title,
        'original_filename' => $originalFilename,
        'file_path' => $localPath, // بدون تكرار 'documents/'
        'file_type' => $extension,
        'file_size' => $fileSize,
        'category_id' => $request->input('category_id'), // قد يتم تحديثها لاحقًا من Job
    ]);

    // 7. إرسال الملف للمعالجة في الخلفية عبر الـ Job
    ProcessDocumentJob::dispatch($document);
     $end = microtime(true);
      $duration = number_format($end - $start, 3); // الزمن المستغرق بالثواني
    // 8. إرجاع رسالة نجاح للمستخدم
 return redirect()->route('documents.create')->with([
        'success' => 'Document uploaded successfully.',
        'duration' => "Upload, classification, save took {$duration} seconds.",
    ]);}



    /**
     * Display the specified document.
     */
  

  


public function search(Request $request)
{
    $query = strtolower(trim($request->input('query')));  // تنظيف الإدخال

    // بدء توقيت البحث
    $startTime = microtime(true);

    $results = [];

    if ($query) {
        $documents = Document::all(); // جلب كل المستندات

        foreach ($documents as $doc) {
            $content = strtolower(strip_tags($doc->content_preview)); // تنظيف النص

            // البحث عن العبارة داخل النص
            $position = stripos($content, $query);
            if ($position !== false) {
                // تحديد الحروف قبل وبعد العبارة
                $contextRadius = 50; // ← عدد الأحرف قبل وبعد الكلمة
                $start = max(0, $position - $contextRadius);
                $length = strlen($query) + 2 * $contextRadius;
                $snippet = substr($content, $start, $length);

                // تمييز الكلمة أو العبارة داخل المقطع
                $highlighted = str_ireplace($query, '<mark>' . e($query) . '</mark>', e($snippet));

                // ربط النص المعالج بالمستند
                $doc->highlighted_content = $highlighted;
                $results[] = $doc;
            }
        }
    }

    // حساب الزمن المستغرق
    $timeTaken = microtime(true) - $startTime;

    // إرسال النتائج للواجهة
    return view('documents.search', [
        'documents' => collect($results),
        'query' => $query,
        'timeTaken' => $timeTaken,
    ]);
}

public function searchView()
{
    return view('documents.search', [
        'documents' => collect(), // قائمة فاضية بالبداية
        'query' => '',
        'timeTaken' => 0
    ]);
}


    
}
