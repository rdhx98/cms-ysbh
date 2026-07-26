<?php

use Livewire\Component;

new class extends Component
{
    public $url = '';
    public $jsonData = null;
    public $renderedUrl = '';
    public $errorMessage = '';

    // Properti ini untuk mencegah infinite loop (jika maju tapi 404 terus-menerus)
    public $autoIncrementCount = 0;
    public $maxAutoIncrement = 50; 

    public function updatedUrl()
    {
        // Reset counter setiap kali URL diinput manual
        $this->autoIncrementCount = 0; 
        $this->fetchData();
    }

    public function increment()
    {
        $this->autoIncrementCount = 0; // Reset counter jika ditekan manual
        $this->modifyUrlId(1);
    }

    public function decrement()
    {
        $this->autoIncrementCount = 0; // Reset counter jika ditekan manual
        $this->modifyUrlId(-1);
    }

    private function modifyUrlId($step)
    {
        if (preg_match('/^(.*?)(\d+)$/', $this->url, $matches)) {
            $baseUrl = $matches[1];
            $currentId = (int) $matches[2];
            
            $newId = $currentId + $step;
            
            $length = strlen($matches[2]);
            $formattedId = str_pad($newId, $length, '0', STR_PAD_LEFT);
            
            $this->url = $baseUrl . $formattedId;
            $this->fetchData();
        } else {
            $this->errorMessage = "URL harus diakhiri dengan angka.";
        }
    }

    public function fetchData()
    {
        $this->errorMessage = '';
        $this->jsonData = null;
        $this->renderedUrl = '';

        if (empty($this->url)) return;

        try {
            // Nonaktifkan exception otomatis agar tidak langsung masuk ke blok catch jika terjadi 404
            $response = Http::timeout(5)->get($this->url);
            
            if ($response->successful()) {
                $this->jsonData = $response->json();
                $this->renderedUrl = data_get($this->jsonData, 'guid.rendered', '');
                
                // Jika sukses, reset counter otomatis
                $this->autoIncrementCount = 0;
                
            } elseif ($response->status() === 404) {
                // LOGIKA AUTO INCREMENT
                if ($this->autoIncrementCount < $this->maxAutoIncrement) {
                    $this->autoIncrementCount++;
                    // Langsung panggil fungsi private untuk memajukan URL (step +1)
                    $this->modifyUrlId(1); 
                } else {
                    $this->errorMessage = "Berhenti mencari. Sudah melewati {$this->maxAutoIncrement} URL namun tetap 404 Not Found.";
                }
            } else {
                $this->errorMessage = "Gagal mengambil data. Status Code: " . $response->status();
            }
        } catch (\Exception $e) {
            $this->errorMessage = "Terjadi kesalahan: " . $e->getMessage();
        }
    }
};

?>

<div class="max-w-7xl mx-auto p-6 space-y-6 w-full" x-data="{ loading: false }">
    
    <!-- Bagian Form Input & Tombol Maju/Mundur -->
    <div class="flex items-center space-x-3">
        <button 
            wire:click="decrement" 
            wire:loading.attr="disabled"
            class="px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 disabled:opacity-50 transition">
            &laquo; Mundur
        </button>

        <input 
            type="text" 
            wire:model.blur="url" 
            wire:keydown.enter="fetchData"
            placeholder="Masukkan URL... contoh: https://web.com/wp-json/wp/v2/media/2222"
            class="flex-1 px-4 py-2 border border-gray-300 rounded focus:outline-none focus:border-blue-500"
        >

        <button 
            wire:click="increment" 
            wire:loading.attr="disabled"
            class="px-4 py-2 bg-blue-600 text-white font-semibold rounded hover:bg-blue-700 disabled:opacity-50 transition">
            Maju &raquo;
        </button>
    </div>

    <!-- Indikator Loading Bawaan Livewire -->
    <div wire:loading wire:target="fetchData, increment, decrement, url" class="text-blue-500 font-semibold">
        Sedang memuat data...
    </div>

    <!-- Pesan Error -->
    @if($errorMessage)
        <div class="p-4 bg-red-100 text-red-700 rounded border border-red-200">
            {{ $errorMessage }}
        </div>
    @endif

    <!-- Bingkai iframe untuk "guid.rendered" -->
    {{-- @if($renderedUrl)
        <div class="space-y-2">
            <h3 class="text-lg font-bold text-gray-800">Preview Media (guid.rendered):</h3>
            
            <div class="border border-gray-300 rounded overflow-hidden bg-gray-50 flex items-center justify-center p-2 h-[400px]">
                {{-- Karena datanya bisa berupa .mp4, kita buat pengecekan khusus video --
                @if(Str::endsWith(strtolower($renderedUrl), '.mp4'))
                    <video controls class="max-w-full max-h-full rounded">
                        <source src="{{ $renderedUrl }}" type="video/mp4">
                        Browser Anda tidak mendukung tag video.
                    </video>
                @else
                    <iframe src="{{ $renderedUrl }}" class="w-full h-full rounded" frameborder="0"></iframe>
                @endif
            </div>
            
        </div>
        @endif --}}
        
    @if ($renderedUrl)
        <p class="text-sm text-gray-500">
            URL Asli: <a href="{{ $url }}" target="_blank" class="text-blue-500 hover:underline">{{ $url }}</a>
        </p>
        
    @endif

    <!-- Hasil Pretty Print JSON -->
    @if($jsonData)
        <div class="space-y-2">
            <h3 class="text-lg font-bold text-gray-800">Pretty Print JSON:</h3>
            <div class="bg-gray-900 text-green-400 p-4 rounded overflow-x-auto shadow-inner text-sm font-mono">
                {{-- Gunakan json_encode dengan param JSON_PRETTY_PRINT --}}
                <pre><code>{{ json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </div>
    @endif

</div> 