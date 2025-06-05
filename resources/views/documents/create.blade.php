<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Upload Document</title>
</head>

<body>
    <h1>Upload Document</h1>

    {{-- عرض الأخطاء إن وجدت --}}
    @if ($errors->any())
        <div style="color:red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- نموذج رفع المستند --}}
    <form action="{{ route('documents.store') }}" method="POST" enctype="multipart/form-data">
        @csrf

        <div>
            <label for="document">Select Document (PDF or DOCX):</label>
            <input type="file" name="document" accept=".pdf,.docx" required>
        </div>

        <br>

      


        <button type="submit">Upload</button>
    </form>
@if (session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
        @if (session('duration'))
            <br>
            <strong>{{ session('duration') }}</strong>
        @endif
    </div>
@endif

</body>

</html>