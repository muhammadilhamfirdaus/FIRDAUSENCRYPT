<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dekripsi File</title>
    <style>body { font-family: sans-serif; margin: 2em; } div { margin-bottom: 1em; } label { display: block; margin-bottom: 5px; } input[type="password"] { width: 300px; padding: 8px; } button { padding: 10px 15px; background-color: #28a745; color: white; border: none; cursor: pointer; }</style>
</head>
<body>
    <h1>Dekripsi File:</h1>
    <h2 style="margin-top: -1em;">{{ $file->original_filename }}</h2>
    <p><a href="{{ route('files.index') }}">« Kembali ke Daftar</a></p>
    <hr>

    @if ($errors->any())
        <div style="color: red; border: 1px solid red; padding: 10px; margin-bottom: 1em;">
            <strong>Error!</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('files.decrypt.download', $file) }}" method="POST">
        @csrf
        <div>
            <label for="passphrase">Masukkan Passphrase untuk Download:</label>
            <input type="password" name="passphrase" id="passphrase" required>
        </div>

        <button type="submit">Dekripsi dan Download</button>
    </form>
</body>
</html>