<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class CMSdbSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $date = date('Y-m-d H:i:s');
         //buat user
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'job_title' =>'Gembel',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Alfrida Pabasi',
            'email' => 'midar@mail.com',
            'job_title' =>'Ketua Yayasan',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'ELizabeth Kristiani',
            'email' => 'liza@mail.com',
            'job_title' =>'Bendahara',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Ruth Charlota Yakoba Fouw',
            'email' => 'utha@example.com',
            'job_title' =>'Program Officer Kesehatan Ibu & Anak',
            'password' => Hash::make('husada'),
        ]);
        User::factory()->create([
            'name' => 'Leon Dolfus Mangonto',
            'email' => 'leon@mail.com',
            'job_title' =>'Program Officer KIA',
            'password' => Hash::make('husada'),
        ]);
        // Membuat role
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleEditor = Role::create(['name' => 'editor']);
        $roleWriter = Role::create(['name' => 'writer']);

        // Memberikan role ke user
        $user2 = User::find(2);
        $user2->assignRole('admin');

        // Memberikan multi-role
        $user3 = User::find(3);
        $user3->assignRole(['admin', 'editor']);

        // Memberikan multi-role
        $user4 = User::find(4);
        $user4->assignRole(['writer']);

        // Memberikan multi-role
        $user5 = User::find(5);
        $user5->assignRole(['writer']);

        // 2. Buat Kategori
        $category = Category::firstOrCreate(['name' => 'Berita'], ['slug' => 'berita']);
        $category = Category::firstOrCreate(['name' => 'Siaran Pers'], ['slug' => 'siaran-pers']);
        $category = Category::firstOrCreate(['name' => 'Pengumuman'], ['slug' => 'pengumuman']);
        $category = Category::firstOrCreate(['name' => 'Program & Kampanye'], ['slug' => 'program']);
        $category = Category::firstOrCreate(['name' => 'Kisah Inspiratif'], ['slug' => 'cerita']);
        $category = Category::firstOrCreate(['name' => 'Laporan & Transparansi'], ['slug' => 'laporan']);
        $category = Category::firstOrCreate(['name' => 'Tulisan Edukasi'], ['slug' => 'blog']);
        $category = Category::firstOrCreate(['name' => 'Acara & Kegiatan'], ['slug' => 'events']);
        //artikel
        //melaporkan [penelitian dan laporan]

        // 3. Buat beberapa Tag
        $tags = collect([
            'Nutrisi', 
            'Lansia', 
            'Posyandu', 
            'Kota Jayapura',
            'Kab. Jayapura',
            'Kuda Menjangan',
            'Aku Papua',
            'Edo Kondologit',
            ])->map(function ($tagName) {
            return Tag::firstOrCreate(['name' => $tagName], ['slug' => Str::slug($tagName)]);
        });
        
        // $post1 = Post::firstOrCreate([
        //     'user_id'=> '2',
        //     'category_id'=> '4',
        //     'title'=> 'Selamat datang di Halaman Program Imunisasi',
        //     'slug'=> 'selamat-datang-di-halaman-program-imunisasi',
        //     'content'=> '<h1 style="text-align: center;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Melindungi Masa Depan Generasi Papua Tengah</strong>&nbsp;</span></h1><p style="text-align: left;"><img src="https://cms-ysbh.test/storage/articles/1lFLSJSCzF00cFJAmYsMC1VDhG2Kx4d60fzeuLkR.webp" alt="pasted-inline-0.webp" title="pasted-inline-0.webp" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block" style="width: 100%; display: block !important; margin: 0.75rem auto !important; float: none !important;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Program Imunisasi Yayasan Sinar Bhakti Husada (YSBH) berkomitmen mendampingi pemerintah daerah dalam memastikan setiap anak di Papua Tengah memiliki benteng perlindungan terhadap penyakit yang dapat dicegah. Melalui pendekatan inovatif dan sensitif budaya, kami berupaya menurunkan angka anak <em>zero dose</em> demi memastikan keberlangsungan generasi masa depan Papua yang sehat dan tangguh.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Kami Bertindak di Tengah Krisis Imunisasi</strong>&nbsp;</span></p><p style="text-align: justify;"><img src="https://cms-ysbh.test/storage/articles/fygOIITKhx3yCP8HGPlaXfpIWwqmdLJaVLXwV8OF.webp" alt="pasted-inline-1.webp" title="pasted-inline-1.webp" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block" style="width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Situasi imunisasi di Provinsi Papua Tengah saat ini memerlukan perhatian mendesak dari seluruh pemangku kepentingan. Hingga tahun 2024, cakupan Imunisasi Dasar Lengkap (IDL) baru mencapai 12%, sebuah penurunan drastis dibandingkan tahun sebelumnya. Dampaknya adalah kemunculan kembali wabah penyakit mematikan seperti Campak, Polio, dan Difteri yang silih berganti menyerang anak-anak kita dalam beberapa tahun terakhir. Ketertinggalan ini bukan sekadar angka, melainkan ancaman nyata bagi keberlangsungan masyarakat Papua. Setiap wabah yang terjadi mengakibatkan duka mendalam bagi keluarga dan beban ekonomi yang besar. Tanpa intervensi yang sistematis dan berkelanjutan, kita berisiko kehilangan generasi emas yang akan membangun Tanah Papua di masa depan.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Lima Pilar Intervensi Program Imunisasi Yayasan Sinar Bhakti Husada</strong>&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Penguatan Regulasi dan Kebijakan Imunisasi</strong>&nbsp;</span></p><p style="text-align: justify;"><img src="https://cms-ysbh.test/storage/articles/RftBoS4ZtnJvfAGsmOo0C9hJblbSYqX5NlAj0Ccb.webp" alt="pasted-inline-2.webp" title="pasted-inline-2.webp" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block" style="width: 50%; float: left; margin-right: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Kami percaya bahwa keberlanjutan program imunisasi harus berakar pada komitmen politik dan hukum yang kuat di tingkat daerah. YSBH memberikan pendampingan teknis kepada pemerintah provinsi dan kabupaten untuk menyusun payung hukum yang kuat, termasuk Peraturan Gubernur Papua Tengah serta Peraturan Bupati di Kabupaten Nabire, Dogiyai, Deiyai, dan Puncak Jaya. Regulasi ini sangat krusial untuk memastikan adanya alokasi anggaran daerah yang pasti, tata kelola logistik vaksin yang lebih baik, serta integrasi layanan imunisasi ke dalam rencana pembangunan daerah.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Melalui regulasi yang jelas, imunisasi tidak lagi dipandang sebagai kegiatan kesehatan rutin semata, melainkan sebagai kewajiban pemerintah untuk melindungi hak asasi anak-anak Papua. Kebijakan ini juga bertujuan untuk mengunci dukungan jangka panjang dari para pengambil keputusan, sehingga program imunisasi tetap berjalan stabil meskipun terjadi pergantian kepemimpinan atau dinamika politik di daerah.&nbsp;</span></p><p style="text-align: justify;"></p><p style="text-align: justify;"></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Pengembangan Puskesmas Model OJT (<em>On-the-Job Training</em>) Center</strong>&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Tantangan geografis Papua membuat biaya pelatihan konvensional menjadi sangat mahal, sementara rotasi petugas kesehatan yang cepat seringkali meninggalkan kekosongan tenaga terlatih di daerah terpencil. Sebagai solusinya, YSBH mengembangkan Puskesmas Model OJT Center di lokasi strategis seperti Puskesmas Wanggar Sari dan Topo (Nabire), Puskesmas Waghete (Deiyai), Puskesmas Moanemani (Dogiyai), dan Puskesmas Mulia (Puncak Jaya). Pusat ini berfungsi sebagai laboratorium pembelajaran praktis bagi tenaga medis untuk memperdalam keterampilan imunisasi secara langsung di lapangan.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Model ini sangat efektif karena memanfaatkan pola mobilitas petugas kesehatan dari daerah terpencil yang sering berkunjung ke ibu kota kabupaten untuk urusan logistik atau pribadi. Saat mereka berada di ibu kota, mereka dapat mengikuti magang singkat dengan waktu fleksibel tanpa harus meninggalkan tugas mereka terlalu lama. Pendekatan ini tidak hanya menekan biaya transportasi secara signifikan, tetapi juga membangun keterikatan dan budaya saling belajar antar Puskesmas guna menciptakan sumber daya manusia yang kompeten secara berkelanjutan.&nbsp;</span></p><p style="text-align: justify;"><img src="https://cms-ysbh.test/storage/articles/kqRLu89IMFvPuNNLMpXcJ3AR1dA6OFQcj0g1TcLB.webp" alt="pasted-inline-4.webp" title="pasted-inline-4.webp" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block" style="width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Implementasi <em>Human Centered Design </em>(HCD)</strong>&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Program imunisasi seringkali menghadapi hambatan berupa keraguan atau ketakutan masyarakat terhadap vaksin. Melalui implementasi <em>Human-Centered Design</em> (HCD), YSBH berupaya memahami sisi kemanusiaan dan sosial-budaya di balik rendahnya cakupan imunisasi. Kami tidak hanya memberikan edukasi satu arah, melainkan duduk bersama orang tua, tokoh masyarakat, dan petugas kesehatan untuk mendengarkan kekhawatiran mereka dan bersama-sama merancang solusi yang sesuai dengan konteks lokal.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Pendekatan ini bertujuan untuk menciptakan layanan imunisasi yang lebih ramah, berempati, dan dapat dipercaya oleh masyarakat Papua. Dengan memahami hambatan dari sudut pandang pengguna layanan, kami dapat mengembangkan strategi komunikasi risiko yang menyentuh hati dan mengubah persepsi masyarakat terhadap vaksin. HCD memastikan bahwa setiap anak mendapatkan layanan yang tidak hanya berkualitas secara medis, tetapi juga dihargai secara budaya.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Penguatan Kolaborasi Lintas Sektor</strong>&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Imunisasi adalah tanggung jawab bersama yang tidak bisa hanya dipikul oleh sektor kesehatan sendirian. YSBH aktif menggerakkan kolaborasi lintas sektor yang melibatkan tokoh adat, pemuka agama, pemerintah desa, hingga sektor swasta di Papua Tengah. Kami percaya bahwa ketika seorang pendeta, kepala suku, atau kepala desa menyuarakan pentingnya imunisasi, pesan tersebut akan memiliki daya terima yang jauh lebih kuat di tengah masyarakat.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Kerja sama ini mencakup pemanfaatan dana desa untuk mendukung mobilisasi sasaran, keterlibatan tokoh agama dalam sosialisasi di rumah-rumah ibadah, hingga dukungan logistik dari sektor lain. Dengan memperkuat sinergi ini, kami membangun ekosistem pendukung yang solid untuk melindungi generasi penerus bangsa. Kolaborasi ini adalah kunci untuk mencapai setiap anak, bahkan di wilayah yang paling sulit dijangkau sekalipun.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Pembentukan dan Penguatan Tim Fasilitator Imunisasi Provinsi</strong>&nbsp;</span></p><p style="text-align: justify;"><img src="https://cms-ysbh.test/storage/articles/pmDGdW574eKN2MWU6Y6og3KxmGKjlJHL4IX5D5Zj.webp" alt="pasted-inline-6.webp" title="pasted-inline-6.webp" class="rounded-lg max-w-full my-2 transition-all cursor-pointer tiptap-uploaded-image inline-block" style="width: 25%; float: right; margin-left: 1rem; margin-top: 0.25rem; margin-bottom: 0.5rem; display: inline !important;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Untuk menjamin kualitas dan keberlanjutan pendampingan teknis, YSBH memfasilitasi pembentukan Tim Fasilitator Imunisasi di tingkat Provinsi Papua Tengah. Tim ini terdiri dari para tenaga ahli lokal yang telah dibekali dengan pengetahuan mendalam mengenai manajemen imunisasi, pemantauan kualitas, hingga teknik komunikasi persuasif. Peran mereka adalah menjadi penggerak utama dalam memberikan bimbingan teknis (<em>technical assistance</em>) yang berkelanjutan kepada kabupaten-kabupaten dampingan.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Keberadaan tim fasilitator ini bertujuan untuk menciptakan kemandirian daerah dalam jangka panjang. Dengan memiliki tim ahli yang siap sedia di tingkat provinsi, proses transfer pengetahuan ke Puskesmas-Puskesmas akan berjalan lebih cepat dan konsisten. Ini adalah upaya kami untuk meninggalkan warisan berupa sistem kesehatan yang tangguh dan SDM lokal yang mumpuni untuk menjaga kesehatan anak-anak Papua tanpa ketergantungan pada pihak luar di masa depan.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;"><strong>Investasi Kemanusiaan: Mengapa Anda Harus Peduli?</strong>&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Mendukung program imunisasi di Papua Tengah adalah investasi dengan dampak kemanusiaan dan ekonomi yang sangat besar. Ketika seorang anak jatuh sakit atau cacat akibat penyakit yang seharusnya bisa dicegah, dampaknya meluas ke seluruh aspek kehidupan: keluarga kehilangan waktu produktif, beban biaya pengobatan meningkat, dan yang paling menyedihkan adalah hilangnya potensi masa depan sang anak sebagai penerus masyarakat Papua.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">Setiap dukungan yang Anda berikan membantu kami memperkuat Puskesmas Model, melatih fasilitator lokal, dan memastikan vaksin sampai ke lengan anak-anak di kampung-kampung terpencil. Bersama-sama, kita bisa memastikan bahwa anak-anak Papua tumbuh sehat, cerdas, dan siap untuk memimpin masa depan mereka sendiri. Bergabunglah bersama kami untuk melindungi setiap nyawa, karena setiap anak Papua berhak atas masa depan yang sehat.&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: justify;"><span style="font-family: Arial, Arial_EmbeddedFont, Arial_MSFontService, sans-serif;">&nbsp;</span></p><p style="text-align: left;"><span style="font-family: Aptos, Aptos_EmbeddedFont, Aptos_MSFontService, sans-serif;">&nbsp;</span></p>', 
        //     'featured_image'=> 'default.webp',
        //     'status'=> 'published',
        //     'created_at'=> $date,
        //     ]);

    }
}
