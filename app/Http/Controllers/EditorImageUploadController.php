<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EditorImageUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:15360', // 15MB, samakan dengan batas di JS
        ]);

        $file = $request->file('image');
        $extension = strtolower($file->getClientOriginalExtension());
        $filename = 'article-' . uniqid() . '.webp';
        $savePath = storage_path('app/public/articles/' . $filename);

        // Jalur GIF: sama seperti logika uploadImage() lama Anda
        if ($extension === 'gif' && class_exists('\Imagick')) {
            try {
                \Intervention\Image\Laravel\Facades\Image::withDriver(new \Intervention\Image\Drivers\Imagick\Driver())
                    ->read($file->getRealPath())
                    ->scale(width: 1000)
                    ->toWebp(70)
                    ->save($savePath);

                return response()->json([
                    'url' => asset('storage/articles/' . $filename),
                ]);
            } catch (\Exception $e) {
                Log::warning('Gagal kompresi GIF: ' . $e->getMessage());
            }
        }

        // Jalur aman (fallback): file WebP hasil konversi di browser tinggal disimpan
        $path = $file->store('articles', 'public');

        return response()->json([
            'url' => asset('storage/' . $path),
        ]);
    }
}
