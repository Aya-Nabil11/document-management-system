<form action="{{ route('documents.searchHandle') }}" method="GET">
    <div style="display: flex; gap: 10px;">
        <input type="text" name="query" class="form-control" placeholder="Search..." value="{{ request('query') }}">

        <button type="submit" class="btn btn-primary">Search</button>
    </div>
    @if ($documents->count())
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Preview (matched text)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($documents as $document)
                            <tr>
                                <td>{{ $document->title }}</td>
                                
                                <td>{!! $document->highlighted_content ?? '' !!}</td>


                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- عرض روابط الصفحات --}}
        @if(method_exists($documents, 'links'))
            {{ $documents->withQueryString()->links() }}
        @endif

    @else
        <p>No documents found for this search.</p>
    @endif

</form>

@if (isset($timeTaken))
    <p><strong>Search took:</strong> {{ number_format($timeTaken, 3) }} seconds</p>
@endif