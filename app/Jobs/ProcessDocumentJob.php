<?php

namespace App\Jobs;

use App\Models\Document;
use App\Models\Category;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as WordReader;
use Str;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $document;

    public function __construct(Document $document)
    {
        $this->document = $document;
    }

 public function handle(): void
{
    try {
        $fullPath = Storage::disk('documents')->path($this->document->file_path);
        $extension = pathinfo($fullPath, PATHINFO_EXTENSION);
        $text = '';

        if ($extension === 'pdf') {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
        } elseif ($extension === 'docx') {
            $phpWord = WordReader::load($fullPath, 'Word2007');
            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= ' ' . $element->getText();
                    }
                }
            }
        }

        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text); // إزالة الرموز الغريبة

        Log::info('🧾 Extracted text (first 200 chars): ' . substr($text, 0, 200));

        $category = $this->matchCategory($text);

        // فقط content_preview الآن بدون content_text
        $this->document->update([
            'category_id' => optional($category)->id,
            'content_preview' => $text, // ← هذا بيحمل كامل النص
        ]);

        if ($category) {
            Log::info('🎯 Matched category: ' . $category->name);
        } else {
            Log::warning('❌ No matching category found for document ID ' . $this->document->id);
        }

    } catch (\Exception $e) {
        Log::error('🚨 Document processing failed', [
            'document_id' => $this->document->id,
            'error' => $e->getMessage()
        ]);
    }
}



  private function matchCategory(string $text): ?Category
{
    // جلب كل التصنيفات بما فيهم المحذوفين (soft-deleted)
    $categories = Category::withTrashed()->get();
    
    // تجهيز النص للمقارنة
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text); // إزالة الرموز غير المفيدة
    $bestMatch = null;
    $maxMatches = 0;

    foreach ($categories as $category) {
        // فصل الكلمات المفتاحية وتحضيرها للمقارنة
        $keywords = explode(',', strtolower($category->keywords ?? ''));
        $matches = 0;

        foreach ($keywords as $keyword) {
            $cleanKeyword = trim($keyword);
            if (!empty($cleanKeyword) && str_contains($text, $cleanKeyword)) {
                $matches++;
            }
        }

        // اختيار التصنيف الذي حصل على أكبر عدد من الكلمات المطابقة
        if ($matches > $maxMatches) {
            $maxMatches = $matches;
            $bestMatch = $category;
        }
    }

    return $bestMatch;
}

}
