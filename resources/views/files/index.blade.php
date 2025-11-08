<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure File Vault</title>
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
            /* Efek background gradien gelap */
            background-color: #111827; /* bg-gray-900 */
            background-image: radial-gradient(at 80% 0%, hsla(269, 70%, 25%, 0.3) 0px, transparent 50%),
                              radial-gradient(at 0% 100%, hsla(243, 80%, 30%, 0.3) 0px, transparent 50%);
            min-height: 100vh;
        }
        .glass-card {
            /* Efek Glassmorphism */
            background-color: rgba(31, 41, 55, 0.5); /* bg-gray-800 opacity 50 */
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .btn-gradient-green {
            background-image: linear-gradient(to right, #10B981 0%, #34D399 50%, #10B981 100%);
            background-size: 200% auto;
            transition: background-position 0.5s;
        }
        .btn-gradient-green:hover {
            background-position: right center;
        }
        .btn-gradient-blue {
            background-image: linear-gradient(to right, #3B82F6 0%, #60A5FA 50%, #3B82F6 100%);
            background-size: 200% auto;
            transition: background-position 0.5s;
        }
        .btn-gradient-blue:hover {
            background-position: right center;
        }
    </style>
</head>
<body class="text-gray-200">

    <div class="max-w-6xl mx-auto p-6 md:p-12">
        
        <!-- HEADER -->
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-white mb-2">
                Secure File Vault
            </h1>
            <p class="text-lg text-gray-400">Enkripsi & kelola file Anda dengan aman.</p>
        </header>

        <!-- TOMBOL AKSI UTAMA -->
        <div class="flex flex-col sm:flex-row space-y-4 sm:space-y-0 sm:space-x-4 mb-8">
            <!-- Tombol Upload Baru -->
            <a href="{{ route('files.create') }}" class="btn-gradient-green flex items-center justify-center text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1h16v-1M4 12l8-8 8 8M12 4v12"></path></svg>
                Upload File Baru
            </a>
            
            <!-- Tombol Dekripsi dari File -->
            <a href="{{ route('files.decrypt.upload_form') }}" class="btn-gradient-blue flex items-center justify-center text-white font-semibold py-3 px-6 rounded-lg shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                Dekripsi dari File .enc
            </a>
        </div>
        
        <!-- NOTIFIKASI SUKSES -->
        @if (session('success'))
            <div class="bg-green-500 bg-opacity-20 border border-green-400 text-green-300 px-4 py-3 rounded-lg relative mb-6" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        <!-- KARTU DAFTAR FILE (Menggantikan <table>) -->
        <div class="glass-card rounded-xl shadow-2xl overflow-hidden">
            
            <!-- Header Tabel (Hanya di Desktop) -->
            <div class="hidden md:grid grid-cols-10 gap-4 px-6 py-4 border-b border-gray-700 bg-gray-700 bg-opacity-30">
                <div class="col-span-4 font-semibold text-gray-300 text-sm uppercase">Nama File</div>
                <div class="col-span-2 font-semibold text-gray-300 text-sm uppercase">Ukuran</div>
                <div class="col-span-2 font-semibold text-gray-300 text-sm uppercase">Tgl Upload</div>
                <div class="col-span-2 font-semibold text-gray-300 text-sm uppercase">Aksi</div>
            </div>
            
            <!-- Daftar File -->
            <div class="divide-y divide-gray-700">
                @forelse ($files as $file)
                    <!-- Baris File (Responsif) -->
                    <div class="grid grid-cols-1 md:grid-cols-10 gap-x-4 gap-y-2 p-6 hover:bg-gray-700 hover:bg-opacity-50 transition-colors duration-200">
                        
                        <!-- Nama File -->
                        <div class="md:col-span-4 flex items-center">
                            <span class="md:hidden text-gray-400 text-sm uppercase font-semibold mr-2">File: </span>
                            <span class="font-medium text-white break-all">{{ $file->original_filename }}</span>
                        </div>
                        
                        <!-- Ukuran -->
                        <div class="md:col-span-2 flex items-center">
                            <span class="md:hidden text-gray-400 text-sm uppercase font-semibold mr-2">Ukuran: </span>
                            <span class="text-gray-300 text-sm">{{ number_format($file->original_size_bytes) }} bytes</span>
                        </div>

                        <!-- Tanggal -->
                        <div class="md:col-span-2 flex items-center">
                            <span class="md:hidden text-gray-400 text-sm uppercase font-semibold mr-2">Tanggal: </span>
                            <span class="text-gray-300 text-sm">{{ $file->created_at->format('Y-m-d H:i') }}</span>
                        </div>
                        
                        <!-- Aksi -->
                        <div class="md:col-span-2 flex flex-col space-y-2 mt-2 md:mt-0">
                            
                            <!-- Link 2: Download .enc -->
                            <a href="{{ route('files.download.encrypted', $file) }}" class="flex items-center text-purple-400 hover:text-purple-300 transition-colors text-sm font-medium">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1h16v-1M4 12l8-8 8 8M12 4v12"></path></svg>
                                Download (.enc)
                            </a>
                        </div>
                    </div>
                @empty
                    <!-- Status Kosong -->
                    <div class="p-8 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6M3 17V7a2 2 0 012-2h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path></svg>
                        <p class="font-medium">Vault Anda masih kosong.</p>
                        <p class="text-sm">Klik "Upload File Baru" untuk memulai.</p>
                    </div>
                @endforelse
            </div>
        </div> <!-- end glass-card -->

    </div> <!-- end container -->

</body>
</html>