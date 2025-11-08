<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload & Dekripsi File</title>
    <!-- 1. Muat Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- 2. Muat Font Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* 3. Terapkan Font dan Efek Glassmorphism */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111827; /* bg-gray-900 */
            background-image: radial-gradient(at 20% 0%, hsla(269, 70%, 25%, 0.3) 0px, transparent 50%),
                              radial-gradient(at 80% 100%, hsla(243, 80%, 30%, 0.3) 0px, transparent 50%);
            min-height: 100vh;
        }
        .glass-card {
            background-color: rgba(31, 41, 55, 0.5); /* bg-gray-800 opacity 50 */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-gradient-blue {
            background-image: linear-gradient(to right, #3B82F6 0%, #60A5FA 50%, #3B82F6 100%);
            background-size: 200% auto;
            transition: background-position 0.5s;
        }
        .btn-gradient-blue:hover {
            background-position: right center;
        }
        .form-input {
            background-color: rgba(55, 65, 81, 0.5); /* bg-gray-700 opacity 50 */
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="text-gray-200">

    <div class="max-w-xl mx-auto p-6 md:p-12 min-h-screen flex flex-col justify-center">

        <h1 class="text-3xl font-bold text-white mb-6 text-center">Dekripsi File dari .enc</h1>
        <p class="text-center mb-6 text-gray-400">
            <a href="{{ route('files.index') }}" class="hover:text-blue-400 transition-colors">&laquo; Kembali ke Vault</a>
        </p>

        <!-- NOTIFIKASI ERROR -->
        @if ($errors->any())
            <div class="bg-red-500 bg-opacity-20 border border-red-400 text-red-300 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <strong class="font-bold">Oops! Terjadi kesalahan:</strong>
                <ul class="mt-2 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        
        <!-- KARTU FORM -->
        <div class="glass-card rounded-xl shadow-2xl p-8 md:p-10">
            <form action="{{ route('files.decrypt.handle_upload') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <!-- Input File -->
                <div>
                    <label for="file" class="block text-sm font-medium text-gray-300 mb-2">Pilih File .enc Anda</label>
                    <input type="file" name="file" id="file" required
                           class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4
                                  file:rounded-lg file:border-0 file:text-sm file:font-semibold
                                  file:bg-purple-500 file:bg-opacity-20 file:text-purple-300
                                  hover:file:bg-opacity-30 transition-colors">
                </div>
                
                <!-- Input Passphrase -->
                <div>
                    <label for="passphrase" class="block text-sm font-medium text-gray-300 mb-2">Masukkan Passphrase</I></label>
                    <input type="password" name="passphrase" id="passphrase" required
                           class="form-input block w-full rounded-lg shadow-sm text-white px-4 py-3
                                  focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="••••••••••••••">
                </div>
                
                <!-- Tombol Submit -->
                <button type="submit" class="w-full btn-gradient-blue flex items-center justify-center text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v3m-6 4h12a2 2 0 002-2v-7a2 2 0 00-2-2H4a2 2 0 00-2 2v7a2 2 0 002 2z"></path></svg>
                    Buka Kunci & Download
                </button>
            </form>
        </div>

    </div>

</body>
</html>