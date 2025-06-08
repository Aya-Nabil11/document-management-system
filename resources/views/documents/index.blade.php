
    <div class="container">
        <h1>📄 All Documents</h1>

        {{-- أزرار التنقل --}}
        <div class="mb-4" style="display: flex; gap: 15px;">
            <a href="{{ route('documents.search') }}" class="btn btn-outline-primary">🔍 Go to Search</a>
            <a href="{{ route('documents.create') }}" class="btn btn-outline-success">⬆️ Upload Document</a>
        </div>

        {{-- إحصائيات --}}
        <div class="mb-3">
            <strong>Total Documents:</strong> {{ $documents->total() }}<br>
            <strong>Total Documents size:</strong> {{ $totalSize}} KB<br>
            <strong>Sorting Time:</strong> {{ number_format($timeTaken, 3) }} seconds
        </div>

        {{-- جدول المستندات --}}
        <table class="table table-bordered table-striped" border="1">
            <thead class="table-light">
                <tr>
                    <th>Title</th>
                    <th>Original Filename</th>
                    <th>Type</th>
                    <th>Size (KB)</th>
                    <th>Category</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($documents as $document)
                    <tr>
                        <td>{{ $document->title }}</td>
                        <td>{{ $document->original_filename }}</td>
                        <td>{{ strtoupper($document->file_type) }}</td>
                        <td>{{ number_format($document->file_size / 1024, 2) }}</td>
                        <td>{{ optional($document->category)->name ?? 'Uncategorized' }}</td>
                        <td>{{ $document->created_at->format('Y-m-d H:i') }}</td>
                       
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- روابط التصفح --}}
        <div class="mt-3">
            {{ $documents->links() }}
        </div>
       
    </div>
