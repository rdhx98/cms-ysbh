<div class=" bg-forest text-white ">
    <div class="flex items-center justify-center">
        <div class="grid grid-cols-1 md:grid-cols-5 p-12 max-w-7xl">

            <div class="flex flex-col justify-center items-center text-center p-4 md:col-span-2">
                <div class="w-full flex flex-col justify-center md:items-start items-center text-left gap-2">
                    <img src="{{ asset('/ysbh.png') }}" alt="Logo YSBH" class="h-32 my-4">
                    <span class="font-black">Sinar Bhakti Husada</span>
                    {{-- <span>Berdedikasi untuk meningkatkan akses dan kualitas layanan kesehatan bagi masyarakat di seluruh Indonesia melalui bimbingan teknis dan program komprehensif</span> --}}
                    <span>Yayasan kesehatan yang mendampingi ibu, anak, dan komunitas di wilayah terpencil Indonesia sejak 2014.</span>


                </div>
            </div>
            <div class="flex flex-col justify-start items-start text-left p-4">
                <div class="text-2xl font-bold mb-3">
                    Program
                </div>
                <div class="flex flex-col justify-start items-start text-left gap-1">
                    <a href="#"  class="hover:opacity-80 transition-opacity cursor-pointer">
                        <span>Kesehatan Ibu & Anak</span>
                    </a>
                    <a href="#"  class="hover:opacity-80 transition-opacity cursor-pointer">
                        <span>Malaria</span>
                    </a>
                    <a href="#"  class="hover:opacity-80 transition-opacity cursor-pointer">
                        <span>Imunisasi</span>
                    </a>
                </div>
            </div>
            <div class="flex flex-col justify-start items-start text-left p-4">
                <div class="text-2xl font-bold mb-3">
                    Yayasan
                </div>
                <div class="flex flex-col justify-start items-start text-left gap-1">
                    <a href="#" class="hover:opacity-80 hover:text-goldy transition-opacity cursor-pointer capitalize">
                        <span>Donasi</span>
                    </a>
                    <a href="#"  class="hover:opacity-80 transition-opacity cursor-pointer capitalize">
                        <span>Jadi Relawan</span>
                    </a>
                    <a href="#"  class="hover:opacity-80 transition-opacity cursor-pointer capitalize">
                        <span>Kerja sama program</span>
                    </a>
                </div>
            </div>
            <div class="flex flex-col justify-start items-start text-left p-4">
                <div class="text-2xl font-bold mb-3">
                    Info Kontak
                </div>
                <div class="flex flex-col justify-start items-start text-left gap-2">
                    <a href="tel:+6282382295759" class="flex items-center gap-2 hover:opacity-80 transition-opacity cursor-pointer">
                        <div class="flex items-center justify-center w-7 h-7 bg-aurum rounded-full">
                            <x-dynamic-component :component="'lucide-phone'" class="w-4 h-4 text-forest" stroke-width="2"/>
                        </div>
                        <span>+62 823 8229 5759</span>
                    </a>
                    <a href="https://maps.app.goo.gl/TMx2eBRxoCpo5gkv9" target="_blank" rel="noopener noreferrer" class="flex items-center gap-2 hover:opacity-80 transition-opacity cursor-pointer">
                        <div class="flex items-center justify-center w-7 h-7 bg-aurum rounded-full">
                            <x-dynamic-component :component="'lucide-map-pin'" class="w-4 h-4 text-forest" stroke-width="2"/>
                        </div>

                        <span>
                            BTN Umum Blok I No. 155
                            {{-- , Awiyo, Kec. Abepura, Kota Jayapura, Papua 99225, Indonesia --}}
                        </span>
                    </a>
                    <a href="mailto:contact@ysbh.org" class="flex items-center gap-2 hover:opacity-80 transition-opacity cursor-pointer">
                        <div class="flex items-center justify-center w-7 h-7 bg-aurum rounded-full">
                            <x-dynamic-component :component="'lucide-mail'" class="w-4 h-4 text-forest" stroke-width="2"/>
                        </div>
                        <span>contact@ysbh.org</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <div class="border-t-2 border-aurum w-full flex justify-between items-center "></div>
    <div class="flex items-center justify-center">
        <div class="w-full py-4 px-16 flex md:flex-row flex-col justify-between items-center max-w-7xl ">
            <span>&copy; {{ date('Y') }} Yayasan Sinar Bhakti Husada</span>
            <span>Dibuat dengan bhakti, untuk sinar yang terus menyala.</span>
        </div>
    </div>
</div>
